<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Expense;
use App\Models\Accounting\ExpenseCategory;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Branch;
use App\Services\Accounting\AutomatedAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    protected AutomatedAccountingService $accountingService;

    public function __construct(AutomatedAccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index(Request $request)
    {
        $status = $request->get('status');
        $categoryId = $request->get('category_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Expense::with(['category', 'account', 'paymentAccount', 'createdBy', 'branch'])
            ->orderBy('expense_date', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('expense_date', [$startDate, $endDate]);
        }

        $expenses = $query->paginate(25);
        $categories = ExpenseCategory::active()->get();
        $statuses = Expense::getStatuses();

        return view('accounting.expenses.index', compact('expenses', 'categories', 'statuses', 'status', 'categoryId', 'startDate', 'endDate'));
    }

    public function create()
    {
        $categories = ExpenseCategory::active()->get();
        $expenseAccounts = ChartOfAccount::active()->expenses()->orderBy('account_code')->get();
        $paymentAccounts = ChartOfAccount::active()
            ->where(function ($q) {
                $q->where('is_cash_account', true)->orWhere('is_bank_account', true);
            })
            ->orderBy('account_code')
            ->get();
        $branches = Branch::all();
        $paymentMethods = Expense::getPaymentMethods();

        return view('accounting.expenses.create', compact('categories', 'expenseAccounts', 'paymentAccounts', 'branches', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'account_id' => 'required|exists:chart_of_accounts,id',
            'payment_account_id' => 'required|exists:chart_of_accounts,id',
            'expense_date' => 'required|date',
            'payee' => 'nullable|string|max:150',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'payment_reference' => 'nullable|string|max:100',
            'receipt_number' => 'nullable|string|max:100',
            'branch_id' => 'nullable|exists:branches,id',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $validated['expense_number'] = Expense::generateExpenseNumber();
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'pending_approval';

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('expenses', 'public');
        }

        $expense = Expense::create($validated);

        return redirect()->route('accounting.expenses.show', $expense)
            ->with('success', 'Expense recorded and submitted for approval.');
    }

    public function show(Expense $expense)
    {
        $expense->load(['category', 'account', 'paymentAccount', 'createdBy', 'approvedBy', 'branch', 'journalEntry']);

        return view('accounting.expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        if (!in_array($expense->status, ['draft', 'rejected'])) {
            return redirect()->route('accounting.expenses.show', $expense)
                ->with('error', 'Only draft or rejected expenses can be edited.');
        }

        $categories = ExpenseCategory::active()->get();
        $expenseAccounts = ChartOfAccount::active()->expenses()->orderBy('account_code')->get();
        $paymentAccounts = ChartOfAccount::active()
            ->where(function ($q) {
                $q->where('is_cash_account', true)->orWhere('is_bank_account', true);
            })
            ->orderBy('account_code')
            ->get();
        $branches = Branch::all();
        $paymentMethods = Expense::getPaymentMethods();

        return view('accounting.expenses.edit', compact('expense', 'categories', 'expenseAccounts', 'paymentAccounts', 'branches', 'paymentMethods'));
    }

    public function update(Request $request, Expense $expense)
    {
        if (!in_array($expense->status, ['draft', 'rejected'])) {
            return redirect()->route('accounting.expenses.show', $expense)
                ->with('error', 'Only draft or rejected expenses can be edited.');
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'account_id' => 'required|exists:chart_of_accounts,id',
            'payment_account_id' => 'required|exists:chart_of_accounts,id',
            'expense_date' => 'required|date',
            'payee' => 'nullable|string|max:150',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'payment_reference' => 'nullable|string|max:100',
            'receipt_number' => 'nullable|string|max:100',
            'branch_id' => 'nullable|exists:branches,id',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $validated['status'] = 'pending_approval';

        if ($request->hasFile('attachment')) {
            if ($expense->attachment) {
                Storage::disk('public')->delete($expense->attachment);
            }
            $validated['attachment'] = $request->file('attachment')->store('expenses', 'public');
        }

        $expense->update($validated);

        return redirect()->route('accounting.expenses.show', $expense)
            ->with('success', 'Expense updated and resubmitted for approval.');
    }

    public function approve(Expense $expense)
    {
        if ($expense->status !== 'pending_approval') {
            return back()->with('error', 'Only pending expenses can be approved.');
        }

        try {
            $expense->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->accountingService->recordExpense($expense);

            $expense->update(['status' => 'paid']);

            return back()->with('success', 'Expense approved and posted to accounting.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error processing expense: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, Expense $expense)
    {
        if ($expense->status !== 'pending_approval') {
            return back()->with('error', 'Only pending expenses can be rejected.');
        }

        $expense->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Expense rejected.');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->journal_entry_id) {
            return back()->with('error', 'Cannot delete expense with posted journal entry.');
        }

        if ($expense->attachment) {
            Storage::disk('public')->delete($expense->attachment);
        }

        $expense->delete();

        return redirect()->route('accounting.expenses.index')
            ->with('success', 'Expense deleted.');
    }

    public function pending()
    {
        $expenses = Expense::with(['category', 'createdBy'])
            ->where('status', 'pending_approval')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('accounting.expenses.pending', compact('expenses'));
    }

    public function categories()
    {
        $categories = ExpenseCategory::with('account')->get();

        return view('accounting.expenses.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:expense_categories,name,NULL,id,tenant_id,' . session('tenant_id'),
            'account_id' => 'nullable|exists:chart_of_accounts,id',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        ExpenseCategory::create($validated);

        return redirect()->route('accounting.expenses.categories')
            ->with('success', 'Expense category created.');
    }

    public function quickAddCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;

        $exists = ExpenseCategory::where('tenant_id', $tenantId)
            ->where('name', $validated['name'])
            ->first();

        if ($exists) {
            return response()->json(['id' => $exists->id, 'name' => $exists->name]);
        }

        $category = ExpenseCategory::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'is_active' => true,
        ]);

        return response()->json(['id' => $category->id, 'name' => $category->name]);
    }

    public function destroyCategory(ExpenseCategory $category)
    {
        if ($category->expenses()->exists()) {
            return back()->with('error', 'Cannot delete category with existing expenses.');
        }

        $category->delete();

        return redirect()->route('accounting.expenses.categories')
            ->with('success', 'Expense category deleted.');
    }
}
