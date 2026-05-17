<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountCategory;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\JournalEntry;
use App\Models\Loan;
use App\Models\Repayment;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\JournalEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsController extends Controller
{
    protected ChartOfAccountsService $accountsService;

    public function __construct(ChartOfAccountsService $accountsService)
    {
        $this->accountsService = $accountsService;
    }

    public function index(Request $request)
    {
        $type   = $request->get('type');
        $search = $request->get('search');
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;

        $query = ChartOfAccount::with('category')
            ->orderBy('account_code');

        if ($type) {
            $query->where('account_type', $type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%");
            });
        }

        $accounts = $query->paginate(50);

        // Compute live loan portfolio balance matching the dashboard calculation exactly:
        // sum(total_amount) for disbursed/active loans minus all repayments on those loans
        $activeLoanIds = Loan::where('tenant_id', $tenantId)
            ->whereIn('status', ['disbursed', 'active'])
            ->pluck('id');

        $totalActiveToPay    = (float) Loan::where('tenant_id', $tenantId)
            ->whereIn('status', ['disbursed', 'active'])
            ->sum('total_amount');
        $totalRepaidForActive = (float) Repayment::whereIn('loan_id', $activeLoanIds)->sum('amount');
        $livePortfolioBalance = $totalActiveToPay - $totalRepaidForActive;

        // Update the Loan Receivables / Loan Portfolio account balance live
        $loanPortfolioAccount = ChartOfAccount::whereIn('account_code', ['1140', '1400'])
            ->where('tenant_id', $tenantId)
            ->first();

        if ($loanPortfolioAccount && abs((float)$loanPortfolioAccount->current_balance - $livePortfolioBalance) > 0.001) {
            $loanPortfolioAccount->update(['current_balance' => $livePortfolioBalance]);
            // Refresh paginated collection so the view shows the updated value
            $accounts = $query->paginate(50);
        }

        $categories   = AccountCategory::orderBy('sort_order')->get();
        $accountTypes = ChartOfAccount::getAccountTypes();

        return view('accounting.chart-of-accounts.index', compact('accounts', 'categories', 'accountTypes', 'type', 'search'));
    }

    public function create()
    {
        $categories = AccountCategory::orderBy('sort_order')->get();
        $parentAccounts = ChartOfAccount::active()->orderBy('account_code')->get();
        $accountTypes = ChartOfAccount::getAccountTypes();

        return view('accounting.chart-of-accounts.create', compact('categories', 'parentAccounts', 'accountTypes'));
    }

    public function store(Request $request)
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;

        $request->validate([
            'account_name'   => 'required|string|max:150',
            'account_type'   => 'required|in:asset,liability,equity,income,expense',
            'opening_balance'=> 'nullable|numeric',
            'description'    => 'nullable|string',
        ]);

        // Auto-determine normal_balance from type
        $normalBalance = in_array($request->account_type, ['asset', 'expense']) ? 'debit' : 'credit';

        // Auto-generate account code: prefix by type + next sequence
        $prefixes = ['asset' => '1', 'liability' => '2', 'equity' => '3', 'income' => '4', 'expense' => '5'];
        $prefix = $prefixes[$request->account_type] ?? '9';
        $last = ChartOfAccount::where('tenant_id', $tenantId)
            ->where('account_code', 'like', $prefix . '%')
            ->orderByRaw('CAST(account_code AS UNSIGNED) DESC')
            ->value('account_code');
        $next = $last ? ((int)$last + 1) : ((int)($prefix . '100'));
        $accountCode = (string)$next;
        // Ensure uniqueness
        while (ChartOfAccount::where('tenant_id', $tenantId)->where('account_code', $accountCode)->exists()) {
            $accountCode = (string)((int)$accountCode + 1);
        }

        // Auto-assign category: find matching category by account_type
        $category = AccountCategory::where('account_type', $request->account_type)->first()
            ?? AccountCategory::first();

        ChartOfAccount::create([
            'tenant_id'          => $tenantId,
            'account_code'       => $accountCode,
            'account_name'       => $request->account_name,
            'account_type'       => $request->account_type,
            'normal_balance'     => $normalBalance,
            'category_id'        => $category?->id,
            'description'        => $request->description,
            'opening_balance'    => $request->opening_balance ?? 0,
            'current_balance'    => $request->opening_balance ?? 0,
            'is_active'          => true,
            'allow_manual_entry' => true,
            'is_bank_account'    => false,
            'is_cash_account'    => false,
        ]);

        return redirect()->route('accounting.chart-of-accounts.index', ['type' => $request->account_type])
            ->with('success', 'Account "' . $request->account_name . '" added successfully.');
    }

    public function edit(ChartOfAccount $chartOfAccount)
    {
        $categories = AccountCategory::orderBy('sort_order')->get();
        $parentAccounts = ChartOfAccount::active()
            ->where('id', '!=', $chartOfAccount->id)
            ->orderBy('account_code')
            ->get();
        $accountTypes = ChartOfAccount::getAccountTypes();

        return view('accounting.chart-of-accounts.edit', compact('chartOfAccount', 'categories', 'parentAccounts', 'accountTypes'));
    }

    public function update(Request $request, ChartOfAccount $chartOfAccount)
    {
        if ($chartOfAccount->is_system) {
            // System accounts: only allow name, description, opening_balance, current_balance updates
            $validated = $request->validate([
                'account_name'    => 'required|string|max:150',
                'description'     => 'nullable|string',
                'opening_balance' => 'nullable|numeric',
                'current_balance' => 'nullable|numeric',
            ]);

            $chartOfAccount->update($validated);

            return redirect()->route('accounting.chart-of-accounts.index')
                ->with('success', 'System account updated successfully.');
        }

        $validated = $request->validate([
            'category_id'     => 'required|exists:account_categories,id',
            'parent_id'       => 'nullable|exists:chart_of_accounts,id',
            'account_code'    => 'required|string|max:20|unique:chart_of_accounts,account_code,' . $chartOfAccount->id . ',id,tenant_id,' . session('tenant_id'),
            'account_name'    => 'required|string|max:150',
            'description'     => 'nullable|string',
            'account_type'    => 'required|in:asset,liability,equity,income,expense',
            'normal_balance'  => 'required|in:debit,credit',
            'is_active'       => 'boolean',
            'allow_manual_entry' => 'boolean',
            'is_bank_account' => 'boolean',
            'is_cash_account' => 'boolean',
            'opening_balance' => 'nullable|numeric',
            'current_balance' => 'nullable|numeric',
        ]);

        $validated['is_active']          = $request->boolean('is_active', true);
        $validated['allow_manual_entry'] = $request->boolean('allow_manual_entry', true);
        $validated['is_bank_account']    = $request->boolean('is_bank_account', false);
        $validated['is_cash_account']    = $request->boolean('is_cash_account', false);

        $chartOfAccount->update($validated);

        return redirect()->route('accounting.chart-of-accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    public function destroy(ChartOfAccount $chartOfAccount)
    {
        if ($chartOfAccount->is_system) {
            return redirect()->route('accounting.chart-of-accounts.index')
                ->with('error', 'System accounts cannot be deleted.');
        }

        if ($chartOfAccount->ledgerEntries()->exists()) {
            return redirect()->route('accounting.chart-of-accounts.index')
                ->with('error', 'Cannot delete account with existing transactions.');
        }

        $chartOfAccount->delete();

        return redirect()->route('accounting.chart-of-accounts.index')
            ->with('success', 'Account deleted successfully.');
    }

    public function setupDefaults()
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        
        $this->accountsService->setupDefaultAccounts($tenantId);

        return redirect()->route('accounting.chart-of-accounts.index')
            ->with('success', 'Default chart of accounts has been set up successfully.');
    }

    public function resetBalances()
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;

        ChartOfAccount::where('tenant_id', $tenantId)
            ->where('is_system', false)
            ->update(['opening_balance' => 0, 'current_balance' => 0]);

        // Also reset system accounts that aren't loan-related (cash, bank)
        ChartOfAccount::where('tenant_id', $tenantId)
            ->where('is_system', true)
            ->whereNotIn('account_code', ['1140', '1400'])
            ->update(['opening_balance' => 0, 'current_balance' => 0]);

        return redirect()->route('accounting.chart-of-accounts.index')
            ->with('success', 'All account balances have been reset to zero.');
    }

    public function openingBalance()
    {
        $cashAccounts  = ChartOfAccount::active()->where(function ($q) {
            $q->where('is_cash_account', true)->orWhere('is_bank_account', true);
        })->orderBy('account_code')->get();

        $capitalAccounts = ChartOfAccount::active()
            ->whereIn('account_type', ['equity', 'liability'])
            ->where('allow_manual_entry', true)
            ->orderBy('account_code')->get();

        return view('accounting.chart-of-accounts.opening-balance', compact('cashAccounts', 'capitalAccounts'));
    }

    public function storeOpeningBalance(Request $request)
    {
        $validated = $request->validate([
            'balance_entry_type' => 'required|in:opening_balance,capital_injection',
            'amount'             => 'required|numeric|min:0.01',
            'cash_account_id'    => 'required|exists:chart_of_accounts,id',
            'equity_account_id'  => 'required|exists:chart_of_accounts,id',
            'entry_date'         => 'required|date',
            'description'        => 'nullable|string|max:500',
        ]);

        $cashAccount   = ChartOfAccount::findOrFail($validated['cash_account_id']);
        $equityAccount = ChartOfAccount::findOrFail($validated['equity_account_id']);

        $isCapitalInjection = $validated['balance_entry_type'] === 'capital_injection';

        $defaultDesc = $isCapitalInjection
            ? 'Capital injected by owner / shareholder'
            : 'Opening balance - initial funds on hand';
        $description = $validated['description'] ?: $defaultDesc;

        $entryType = $isCapitalInjection ? 'capital_injection' : 'opening_balance';
        $prefix    = $isCapitalInjection ? 'CI' : 'OB';
        $label     = $isCapitalInjection ? 'Capital injection' : 'Opening balance';

        DB::transaction(function () use ($validated, $cashAccount, $equityAccount, $description, $entryType, $prefix) {
            $service = app(JournalEntryService::class);

            $lines = [
                [
                    'account_id'   => $cashAccount->id,
                    'description'  => $description,
                    'debit_amount' => $validated['amount'],
                    'credit_amount'=> 0,
                ],
                [
                    'account_id'   => $equityAccount->id,
                    'description'  => $description,
                    'debit_amount' => 0,
                    'credit_amount'=> $validated['amount'],
                ],
            ];

            $entry = $service->createEntry([
                'entry_date'        => $validated['entry_date'],
                'entry_type'        => $entryType,
                'description'       => $description,
                'status'            => 'posted',
                'is_auto_generated' => false,
                'prefix'            => $prefix,
            ], $lines);

            $service->postEntry($entry, auth()->id());

            $cashAccount->increment('opening_balance', $validated['amount']);
            $cashAccount->increment('current_balance', $validated['amount']);
        });

        return redirect()->route('accounting.chart-of-accounts.index')
            ->with('success', $label . ' of TZS ' . number_format($validated['amount'], 2) . ' recorded successfully.');
    }
}
