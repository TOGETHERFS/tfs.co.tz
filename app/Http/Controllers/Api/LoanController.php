<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Client;
use App\Models\LoanProduct;
use App\Models\LoanSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoanController extends Controller
{
    /**
     * Get all loans.
     */
    public function index(Request $request)
    {
        // Use default tenant global scope to ensure consistency with dashboard.
        // This guarantees the same set of loans shown in the dashboard
        // also appear in the All Loans API list.
        $query = Loan::query()
            ->with(['client', 'product', 'schedules', 'repayments']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($clientQuery) use ($search) {
                      $clientQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->has('status') && $request->get('status') !== '') {
            $query->where('status', $request->get('status'));
        }

        // Client filter
        if ($request->has('client_id') && $request->get('client_id') !== '') {
            $query->where('client_id', $request->get('client_id'));
        }

        // Product filter
        if ($request->has('product_id') && $request->get('product_id') !== '') {
            $query->where('product_id', $request->get('product_id'));
        }

        // Period filter (daily/weekly/monthly/yearly/custom)
        $period   = $request->get('period', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo   = $request->get('date_to', '');
        if ($period && $period !== 'all') {
            $now = Carbon::now();
            switch ($period) {
                case 'daily':
                    $dateFrom = $now->copy()->startOfDay()->toDateTimeString();
                    $dateTo   = $now->copy()->endOfDay()->toDateTimeString();
                    break;
                case 'weekly':
                    $dateFrom = $now->copy()->startOfWeek()->toDateTimeString();
                    $dateTo   = $now->copy()->endOfWeek()->toDateTimeString();
                    break;
                case 'monthly':
                    $dateFrom = $now->copy()->startOfMonth()->toDateTimeString();
                    $dateTo   = $now->copy()->endOfMonth()->toDateTimeString();
                    break;
                case 'yearly':
                    $dateFrom = $now->copy()->startOfYear()->toDateTimeString();
                    $dateTo   = $now->copy()->endOfYear()->toDateTimeString();
                    break;
                case 'custom':
                    // use date_from / date_to as supplied
                    $dateFrom = $dateFrom ? Carbon::parse($dateFrom)->startOfDay()->toDateTimeString() : null;
                    $dateTo   = $dateTo   ? Carbon::parse($dateTo)->endOfDay()->toDateTimeString()   : null;
                    break;
            }
            if ($dateFrom && $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            }
        } elseif ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        // Sort
        $sort = $request->get('sort', 'created_at_desc');
        switch ($sort) {
            case 'created_at_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'amount_desc':
                $query->orderBy('principal', 'desc');
                break;
            case 'amount_asc':
                $query->orderBy('principal', 'asc');
                break;
            case 'due_date_asc':
                $query->orderBy('first_payment_date', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        // Build a summary query (same filters, no pagination)
        $summaryQuery = clone $query;
        $summaryRows = $summaryQuery->select('status', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(principal) as total_principal'), DB::raw('SUM(total_amount) as total_repayable'))
            ->groupBy('status')
            ->get();

        $summary = [
            'total_count'     => 0,
            'total_principal' => 0,
            'total_repayable' => 0,
            'by_status'       => [],
        ];
        foreach ($summaryRows as $row) {
            $summary['total_count']     += $row->cnt;
            $summary['total_principal'] += (float) $row->total_principal;
            $summary['total_repayable'] += (float) $row->total_repayable;
            $summary['by_status'][$row->status] = [
                'count'           => (int) $row->cnt,
                'total_principal' => (float) $row->total_principal,
                'total_repayable' => (float) $row->total_repayable,
            ];
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $loans = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $loans,
            'summary' => $summary,
        ]);
    }

    /**
     * Get a specific loan.
     */
    public function show($id)
    {
        $loan = Loan::with(['client', 'product', 'schedules', 'repayments', 'documents'])->find($id);

        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $loan
        ]);
    }

    /**
     * Create a new loan.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'product_id' => 'required|exists:loan_products,id',
            'principal' => 'required|numeric|min:1',
            'term' => 'required|integer|min:1',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'first_payment_date' => 'required|date|after:today',
            'purpose' => 'nullable|string|max:500',
            'collateral' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate client and product
        $client = Client::find($request->client_id);
        $product = LoanProduct::find($request->product_id);

        if ($client->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Client is not active'
            ], 400);
        }

        // Validate loan amount against product limits
        if ($request->principal < $product->min_amount || $request->principal > $product->max_amount) {
            return response()->json([
                'success' => false,
                'message' => "Loan amount must be between {$product->min_amount} and {$product->max_amount}"
            ], 400);
        }

        // Validate term against product limits
        if ($request->term < $product->min_term || $request->term > $product->max_term) {
            return response()->json([
                'success' => false,
                'message' => "Loan term must be between {$product->min_term} and {$product->max_term} months"
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Calculate loan details using product's method (supports percentage and fixed)
            $processingFee = $product->calculateProcessingFee($request->principal);
            $rMonthly = 0.10; // fixed 10% monthly rate
            $interestType = $product->interest_type ?? 'flat';
            
            if ($interestType === 'reducing' && $rMonthly > 0) {
                $monthlyPayment = round($request->principal * $rMonthly / (1 - pow(1 + $rMonthly, -$request->term)), 2);
            } else {
                $monthlyInterest = round($request->principal * $rMonthly, 2);
                $principalPerPayment = round($request->principal / $request->term, 2);
                $monthlyPayment = $principalPerPayment + $monthlyInterest;
            }
            $totalAmount = round($monthlyPayment * $request->term, 2);
            // Create loan (ensure required columns exist)
            $loan = Loan::create([
                'loan_number' => $this->generateLoanNumber(),
                'client_id' => $request->client_id,
                'product_id' => $request->product_id,
                'user_id' => $request->user()->id,
                'principal' => $request->principal,
                'interest_rate' => 10,
                'term' => $request->term,
                'processing_fee' => $processingFee,
                'total_amount' => $totalAmount,
                'monthly_payment' => $monthlyPayment,
                'first_payment_date' => $request->first_payment_date,
                'status' => 'pending',
            ]);

            // Generate loan schedule
            $this->generateLoanSchedule($loan);

            DB::commit();

            // Log the activity
            activity()
                ->causedBy($request->user())
                ->performedOn($loan)
                ->log('Loan application created via API');

            return response()->json([
                'success' => true,
                'message' => 'Loan application created successfully',
                'data' => $loan->load(['client', 'product'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            // Add diagnostic logging for easier troubleshooting
            logger()->error('Loan creation failed (api)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tenant_id' => session('tenant_id'),
                'request' => $request->all(),
                'user_id' => optional($request->user())->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create loan application'
            ], 500);
        }
    }

    /**
     * Update a loan.
     */
    public function update(Request $request, $id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found'
            ], 404);
        }

        // Only allow updates for pending loans
        if ($loan->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending loans can be updated'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'principal' => 'required|numeric|min:1',
            'term' => 'required|integer|min:1',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'first_payment_date' => 'required|date|after:today',
            'purpose' => 'nullable|string|max:500',
            'collateral' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $product = $loan->product;

            // Validate loan amount against product limits
            if ($request->principal < $product->min_amount || $request->principal > $product->max_amount) {
                return response()->json([
                    'success' => false,
                    'message' => "Loan amount must be between {$product->min_amount} and {$product->max_amount}"
                ], 400);
            }

            // Recalculate loan details using product's method (supports percentage and fixed)
            $processingFee = $product->calculateProcessingFee($request->principal);
            $rMonthly = 0.10; // fixed 10% monthly rate
            $interestType = $product->interest_type ?? 'flat';

            if ($interestType === 'reducing' && $rMonthly > 0) {
                $monthlyPayment = round($request->principal * $rMonthly / (1 - pow(1 + $rMonthly, -$request->term)), 2);
            } else {
                $monthlyInterest = round($request->principal * $rMonthly, 2);
                $principalPerPayment = round($request->principal / $request->term, 2);
                $monthlyPayment = $principalPerPayment + $monthlyInterest;
            }
            $totalAmount = round($monthlyPayment * $request->term, 2);
            // Update loan
            $loan->update([
                'principal' => $request->principal,
                'interest_rate' => 10,
                'term' => $request->term,
                'processing_fee' => $processingFee,
                'total_amount' => $totalAmount,
                'monthly_payment' => $monthlyPayment,
                'first_payment_date' => $request->first_payment_date,
            ]);

            // Regenerate loan schedule
            $loan->schedules()->delete();
            $this->generateLoanSchedule($loan);

            DB::commit();

            // Log the activity
            activity()
                ->causedBy($request->user())
                ->performedOn($loan)
                ->log('Loan updated via API');

            return response()->json([
                'success' => true,
                'message' => 'Loan updated successfully',
                'data' => $loan->load(['client', 'product'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update loan'
            ], 500);
        }
    }

    /**
     * Approve a loan.
     */
    public function approve(Request $request, $id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found'
            ], 404);
        }

        if ($loan->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending loans can be approved'
            ], 400);
        }

        $loan->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        // Log the activity
        activity()
            ->causedBy($request->user())
            ->performedOn($loan)
            ->log('Loan approved via API');

        return response()->json([
            'success' => true,
            'message' => 'Loan approved successfully',
            'data' => $loan
        ]);
    }

    /**
     * Disburse a loan.
     */
    public function disburse(Request $request, $id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found'
            ], 404);
        }

        $user = $request->user() ?? auth()->user();

        // Loans created before the workflow system have no workflow state — use direct update fallback
        if (!$loan->workflowState) {
            if (!in_array($loan->status, ['approved', 'active'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved loans can be disbursed.',
                ], 422);
            }

            $disbursedAt      = $request->input('disbursed_at')
                ? \Carbon\Carbon::parse($request->input('disbursed_at'))->toDateString()
                : now()->toDateString();
            $firstPaymentDate = $request->input('first_payment_date')
                ? \Carbon\Carbon::parse($request->input('first_payment_date'))->toDateString()
                : null;

            $updateData = [
                'status'      => 'disbursed',
                'disbursed_at' => $disbursedAt,
                'disbursed_by' => $user->id ?? null,
            ];
            if ($firstPaymentDate) {
                $updateData['first_payment_date'] = $firstPaymentDate;
            }
            $loan->update($updateData);

            // Always regenerate schedule with the new first_payment_date
            try {
                $loan->load('product');
                $loan->schedules()->delete();
                $loan->generateSchedule();
            } catch (\Throwable $se) {
                \Log::warning('Schedule generation failed after disburse', [
                    'loan_id' => $loan->id,
                    'error'   => $se->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Loan disbursed successfully',
                'data'    => $loan->fresh(['client', 'product']),
            ]);
        }

        try {
            $workflowEngine = app(\App\Services\WorkflowEngine::class);
            $result = $workflowEngine->disburse(
                $loan,
                $user,
                $request->input('comment'),
                [
                    'method'    => $request->input('disbursement_method'),
                    'reference' => $request->input('disbursement_reference'),
                    'account'   => $request->input('disbursement_account'),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Loan disbursed successfully',
                'data'    => $loan->fresh(['client', 'product']),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Loan disburse failed', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get loan statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_loans' => Loan::count(),
            'pending_loans' => Loan::where('status', 'pending')->count(),
            'approved_loans' => Loan::where('status', 'approved')->count(),
            'disbursed_loans' => Loan::where('status', 'disbursed')->count(),
            'active_loans' => Loan::whereIn('status', ['disbursed', 'active'])->count(),
            'completed_loans' => Loan::where('status', 'completed')->count(),
            'overdue_loans' => Loan::overdue()->count(),
            'total_disbursed' => Loan::whereIn('status', ['disbursed', 'active', 'completed'])->sum('principal'),
            'total_outstanding' => Loan::whereIn('status', ['disbursed', 'active'])->sum('outstanding_balance'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Generate loan number.
     */
    private function generateLoanNumber()
    {
        $prefix = 'LN';
        $year = date('Y');
        $month = date('m');
        
        $lastLoan = Loan::whereYear('created_at', $year)
                       ->whereMonth('created_at', $month)
                       ->orderBy('id', 'desc')
                       ->first();
        
        $sequence = $lastLoan ? (int)substr($lastLoan->loan_number, -4) + 1 : 1;
        
        return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate loan schedule.
     */
    private function generateLoanSchedule($loan)
    {
        $schedules = [];
        $paymentDate = Carbon::parse($loan->first_payment_date);
        $tenantId = session('tenant_id') ?? $loan->tenant_id;

        $rMonthly = ($loan->interest_rate / 100);
        $product = $loan->product ?? \App\Models\LoanProduct::find($loan->product_id);
        $interestType = optional($product)->interest_type ?? 'flat';
        $repaymentType = optional($product)->repayment_type ?? 'amortized';
        $repaymentSchedule = $loan->repayment_schedule ?? 'monthly';

        $principalBalance = $loan->principal;

        // Interest-only: pay interest each period, full principal on last installment.
        if ($repaymentType === 'interest_only') {
            $interestPerInstallment = round($loan->principal * $rMonthly, 2);

            for ($i = 1; $i <= $loan->term; $i++) {
                $isLast = ($i === $loan->term);
                $principalAmount = $isLast ? round($loan->principal, 2) : 0.00;
                $interestAmount  = $interestPerInstallment;
                $totalPayment    = round($principalAmount + $interestAmount, 2);
                $balanceAfter    = $isLast ? 0.00 : round($loan->principal, 2);

                $schedules[] = [
                    'tenant_id'          => $tenantId,
                    'loan_id'            => $loan->id,
                    'installment_number' => $i,
                    'due_date'           => $paymentDate->copy(),
                    'principal_amount'   => $principalAmount,
                    'interest_amount'    => $interestAmount,
                    'total_amount'       => $totalPayment,
                    'paid_amount'        => 0,
                    'balance'            => $balanceAfter,
                    'status'             => 'pending',
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];

                switch ($repaymentSchedule) {
                    case 'daily':    $paymentDate->addDay();     break;
                    case 'weekly':   $paymentDate->addWeek();    break;
                    case 'biweekly': $paymentDate->addDays(14);  break;
                    default:         $paymentDate->addMonth();   break;
                }
            }

            LoanSchedule::insert($schedules);
            return;
        }

        for ($i = 1; $i <= $loan->term; $i++) {
            if (in_array(strtolower((string) $interestType), ['reducing', 'reducing_balance', 'reducing-balance'], true) && $rMonthly > 0) {
                $interestAmount = round($principalBalance * $rMonthly, 2);
                $principalAmount = round($loan->monthly_payment - $interestAmount, 2);
                if ($principalAmount < 0) {
                    $principalAmount = 0;
                }
            } else {
                $interestAmount = round($loan->principal * $rMonthly, 2);
                $principalAmount = round($loan->principal / $loan->term, 2);
            }

            $totalPayment = round($principalAmount + $interestAmount, 2);
            $principalBalance = max(0, round($principalBalance - $principalAmount, 2));

            $schedules[] = [
                'tenant_id'          => $tenantId,
                'loan_id'            => $loan->id,
                'installment_number' => $i,
                'due_date'           => $paymentDate->copy(),
                'principal_amount'   => $principalAmount,
                'interest_amount'    => $interestAmount,
                'total_amount'       => $totalPayment,
                'paid_amount'        => 0,
                'balance'            => $principalBalance,
                'status'             => 'pending',
                'created_at'         => now(),
                'updated_at'         => now(),
            ];

            switch ($repaymentSchedule) {
                case 'daily':    $paymentDate->addDay();     break;
                case 'weekly':   $paymentDate->addWeek();    break;
                case 'biweekly': $paymentDate->addDays(14);  break;
                default:         $paymentDate->addMonth();   break;
            }
        }

        LoanSchedule::insert($schedules);
    }

    /**
     * Admin: Re-sync repayments onto schedules for a loan.
     * Fixes cases where repayments were recorded but schedule paid_amounts were not updated.
     */
    public function syncSchedules($id)
    {
        $user = auth()->user();
        $user->loadMissing('roles');
        $adminSlugs = ['admin', 'administrator'];
        $isAdmin = $user->isAdmin()
            || $user->hasPermission('loans.edit')
            || $user->roles->pluck('slug')->map(fn($s) => strtolower($s))->intersect($adminSlugs)->isNotEmpty();

        if (!$isAdmin) {
            return response()->json(['success' => false, 'message' => 'Admin access required.'], 403);
        }

        // Bypass global scope to get loan and its schedules reliably
        $loan = Loan::withoutGlobalScopes()->with(['schedules', 'repayments'])->find($id);
        if (!$loan) {
            return response()->json(['success' => false, 'message' => 'Loan not found.'], 404);
        }

        DB::beginTransaction();
        try {
            // Reset all schedule paid amounts
            $schedules = $loan->schedules->sortBy('due_date');
            foreach ($schedules as $sch) {
                $sch->paid_amount = 0;
                $sch->status      = 'pending';
                $sch->paid_date   = null;
                $sch->save();
            }

            // Re-apply all repayments in payment_date order
            $repayments = $loan->repayments->sortBy('payment_date');
            $remainingBySchedule = $schedules->mapWithKeys(fn($s) => [$s->id => (float)$s->total_amount]);
            $paidBySchedule      = $schedules->mapWithKeys(fn($s) => [$s->id => 0.0]);

            foreach ($repayments as $rep) {
                $remaining = (float) $rep->amount;
                foreach ($schedules as $sch) {
                    if ($remaining <= 0) break;
                    $unpaid = $remainingBySchedule[$sch->id] - $paidBySchedule[$sch->id];
                    if ($unpaid <= 0) continue;
                    $pay = min($remaining, $unpaid);
                    $paidBySchedule[$sch->id] += $pay;
                    $remaining -= $pay;
                }
            }

            // Write updated paid amounts back to schedules
            foreach ($schedules as $sch) {
                $paid = round($paidBySchedule[$sch->id], 2);
                $sch->paid_amount = $paid;
                if ($paid >= (float)$sch->total_amount) {
                    $sch->status    = 'paid';
                    $sch->paid_date = $repayments->last()?->payment_date ?? now()->toDateString();
                } elseif ($paid > 0) {
                    $sch->status = 'partial';
                } else {
                    $sch->status    = 'pending';
                    $sch->paid_date = null;
                }
                $sch->save();
            }

            // Recalculate loan status
            $loan->refresh();
            $unpaidCount = $loan->schedules()->withoutGlobalScopes()->whereNotIn('status', ['paid'])->count();
            if ($unpaidCount === 0 && $schedules->count() > 0) {
                $loan->status       = 'completed';
                $loan->completed_at = now();
            } elseif ($loan->status === 'completed' && $unpaidCount > 0) {
                $loan->status       = 'active';
                $loan->completed_at = null;
            }
            $loan->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Schedules synced successfully. ' . $schedules->count() . ' installment(s) updated.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Sync failed: ' . $e->getMessage()], 500);
        }
    }
}