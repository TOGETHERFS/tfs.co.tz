<?php

namespace App\Http\Controllers;

use App\Models\LoanProduct;
use Illuminate\Http\Request;

class LoanProductController extends Controller
{
    /**
     * Display a listing of loan products.
     */
    public function index(Request $request)
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        
        // Seed default loan products if none exist
        $existingCount = LoanProduct::where('tenant_id', $tenantId)->count();
        if ($existingCount === 0) {
            $this->seedDefaultLoanProducts($tenantId);
        }

        $query = LoanProduct::where('tenant_id', $tenantId);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $products = $query->withCount('loans')->latest()->paginate(15);

        return view('loan-products.index', compact('products'));
    }

    /**
     * Show the form for creating a new loan product.
     */
    public function create()
    {
        return view('loan-products.create');
    }

    /**
     * Store a newly created loan product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'min_amount' => 'required|numeric|min:1',
            'max_amount' => 'required|numeric|gt:min_amount',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'interest_type' => 'required|in:flat,reducing_balance',
            'repayment_type' => 'nullable|in:amortized,interest_only',
            'min_term' => 'required|integer|min:1',
            'max_term' => 'required|integer|gt:min_term',
            'processing_fee' => 'required|numeric|min:0',
            'processing_fee_type' => 'nullable|in:percentage,fixed',
            'penalty_type' => 'nullable|in:none,percentage,fixed',
            'penalty_value' => 'nullable|numeric|min:0',
            'penalty_frequency' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['processing_fee_type'] = $request->input('processing_fee_type', 'percentage');
        $validated['repayment_type'] = $request->input('repayment_type', 'amortized');
        $validated['penalty_type'] = $request->input('penalty_type', 'none');
        $validated['penalty_value'] = $request->input('penalty_value', 0);
        $validated['penalty_frequency'] = $request->input('penalty_frequency', 30);

        LoanProduct::create($validated);

        return redirect()->route('loan-products.index')
                        ->with('success', 'Loan product created successfully.');
    }

    /**
     * Display the specified loan product.
     */
    public function show(LoanProduct $loanProduct)
    {
        $loanProduct->loadCount(['loans', 'loans as active_loans_count' => function ($query) {
            $query->whereIn('status', ['disbursed', 'active']);
        }]);

        // Get recent loans for this product
        $recentLoans = $loanProduct->loans()
                                  ->with('client')
                                  ->latest()
                                  ->take(10)
                                  ->get();

        // Calculate statistics
        $stats = [
            'total_loans' => $loanProduct->loans_count,
            'active_loans' => $loanProduct->active_loans_count,
            'total_disbursed' => $loanProduct->loans()
                                            ->whereIn('status', ['disbursed', 'active', 'completed'])
                                            ->sum('principal'),
            'total_outstanding' => $loanProduct->loans()
                                              ->whereIn('status', ['disbursed', 'active'])
                                              ->sum('outstanding_balance'),
        ];

        return view('loan-products.show', compact('loanProduct', 'recentLoans', 'stats'));
    }

    /**
     * Show the form for editing the specified loan product.
     */
    public function edit(LoanProduct $loanProduct)
    {
        return view('loan-products.edit', compact('loanProduct'));
    }

    /**
     * Update the specified loan product in storage.
     */
    public function update(Request $request, LoanProduct $loanProduct)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'min_amount' => 'required|numeric|min:1',
            'max_amount' => 'required|numeric|gt:min_amount',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'interest_type' => 'required|in:flat,reducing_balance',
            'repayment_type' => 'nullable|in:amortized,interest_only',
            'min_term' => 'required|integer|min:1',
            'max_term' => 'required|integer|gt:min_term',
            'processing_fee' => 'required|numeric|min:0',
            'processing_fee_type' => 'nullable|in:percentage,fixed',
            'penalty_type' => 'nullable|in:none,percentage,fixed',
            'penalty_value' => 'nullable|numeric|min:0',
            'penalty_frequency' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['processing_fee_type'] = $request->input('processing_fee_type', $loanProduct->processing_fee_type ?? 'percentage');
        $validated['repayment_type'] = $request->input('repayment_type', $loanProduct->repayment_type ?? 'amortized');
        $validated['penalty_type'] = $request->input('penalty_type', $loanProduct->penalty_type ?? 'none');
        $validated['penalty_value'] = $request->input('penalty_value', $loanProduct->penalty_value ?? 0);
        $validated['penalty_frequency'] = $request->input('penalty_frequency', $loanProduct->penalty_frequency ?? 30);

        $loanProduct->update($validated);

        return redirect()->route('loan-products.show', $loanProduct)
                        ->with('success', 'Loan product updated successfully.');
    }

    /**
     * Remove the specified loan product from storage.
     */
    public function destroy(LoanProduct $loanProduct)
    {
        // Check if product has any loans
        if ($loanProduct->loans()->exists()) {
            return back()->with('error', 'Cannot delete loan product that has associated loans.');
        }

        $loanProduct->delete();

        return redirect()->route('loan-products.index')
                        ->with('success', 'Loan product deleted successfully.');
    }

    /**
     * Toggle the active status of a loan product.
     */
    public function toggleStatus(LoanProduct $loanProduct)
    {
        $loanProduct->update([
            'is_active' => !$loanProduct->is_active
        ]);

        $status = $loanProduct->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Loan product {$status} successfully.");
    }

    /**
     * Calculate loan details for a given product and parameters.
     */
    public function calculate(Request $request, LoanProduct $loanProduct)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:' . $loanProduct->min_amount . '|max:' . $loanProduct->max_amount,
            'term' => 'required|integer|min:' . $loanProduct->min_term . '|max:' . $loanProduct->max_term,
        ]);

        $amount = $validated['amount'];
        $term = $validated['term'];

        $monthlyPayment = $loanProduct->calculateMonthlyPayment($amount, $term);
        $totalAmount = $loanProduct->calculateTotalAmount($amount, $term);
        $processingFee = $loanProduct->calculateProcessingFee($amount);

        return response()->json([
            'monthly_payment' => $monthlyPayment,
            'total_amount' => $totalAmount,
            'processing_fee' => $processingFee,
            'total_interest' => $totalAmount - $amount,
        ]);
    }

    /**
     * Get loan products for API (used in loan creation).
     */
    public function apiIndex()
    {
        $products = LoanProduct::active()->get([
            'id', 'name', 'min_amount', 'max_amount',
            'interest_rate', 'interest_type',
            'repayment_type',
            'processing_fee', 'processing_fee_type',
            'penalty_type', 'penalty_value', 'penalty_frequency',
            'min_term', 'max_term',
        ]);

        return response()->json($products);
    }

    /**
     * Get loan product details for API.
     */
    public function apiShow(LoanProduct $loanProduct)
    {
        if (!$loanProduct->is_active) {
            return response()->json(['error' => 'Product is not active'], 400);
        }

        return response()->json($loanProduct);
    }

    /**
     * Get loan product statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_products' => LoanProduct::count(),
            'active_products' => LoanProduct::where('is_active', true)->count(),
            'inactive_products' => LoanProduct::where('is_active', false)->count(),
            'most_popular' => LoanProduct::withCount('loans')
                                        ->orderBy('loans_count', 'desc')
                                        ->first(),
        ];

        return response()->json($stats);
    }

    /**
     * Duplicate a loan product.
     */
    public function duplicate(LoanProduct $loanProduct)
    {
        $newProduct = $loanProduct->replicate();
        $newProduct->name = $loanProduct->name . ' (Copy)';
        $newProduct->is_active = false;
        $newProduct->save();

        return redirect()->route('loan-products.edit', $newProduct)
                        ->with('success', 'Loan product duplicated successfully. Please review and update the details.');
    }
}