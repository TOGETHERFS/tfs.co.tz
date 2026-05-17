<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repayment;
use App\Models\Loan;
use App\Models\LoanSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RepaymentController extends Controller
{
    /**
     * Get all loan schedules (installments) for disbursed/active loans.
     */
    public function index(Request $request)
    {
        $query = LoanSchedule::with(['loan.client', 'loan.product', 'latestRepayment'])
            ->whereHas('loan', function ($q) {
                $q->whereIn('status', ['disbursed', 'active', 'partially_paid']);
            });

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('loan', function ($loanQuery) use ($search) {
                    $loanQuery->where('loan_number', 'like', "%{$search}%")
                              ->orWhereHas('client', function ($clientQuery) use ($search) {
                                  $clientQuery->where('first_name', 'like', "%{$search}%")
                                              ->orWhere('last_name', 'like', "%{$search}%");
                              });
                });
            });
        }

        // Loan status filter (filter schedules by their loan's status)
        if ($request->filled('loan_status')) {
            $loanStatus = $request->get('loan_status');
            $query->whereHas('loan', function ($q) use ($loanStatus) {
                $q->where('status', $loanStatus);
            });
        }

        // Date from filter
        if ($request->filled('date_from')) {
            $query->where('due_date', '>=', $request->get('date_from'));
        }

        // Date to filter
        if ($request->filled('date_to')) {
            $query->where('due_date', '<=', $request->get('date_to'));
        }

        // Sorting
        $sort = $request->get('sort', 'recent_first');
        switch ($sort) {
            case 'recent_first':
                // pending/partial: earliest due_date first (most overdue/nearest at top)
                // paid: most recently paid last
                $query->orderByRaw("CASE WHEN status = 'paid' THEN 2 ELSE 1 END")
                      ->orderByRaw("CASE WHEN status = 'paid' THEN paid_date ELSE due_date END ASC");
                break;
            case 'paid_first':
                $query->orderByRaw("CASE WHEN status = 'paid' THEN 1 WHEN status = 'partial' THEN 2 ELSE 3 END")
                      ->orderBy('paid_date', 'desc')
                      ->orderBy('due_date', 'desc');
                break;
            case 'month_asc':
                $query->orderByRaw('MONTH(due_date) asc')
                      ->orderBy('due_date', 'asc');
                break;
            case 'month_desc':
                $query->orderByRaw('MONTH(due_date) desc')
                      ->orderBy('due_date', 'desc');
                break;
            case 'due_date_desc':
                $query->orderBy('due_date', 'desc');
                break;
            case 'due_date_asc':
                $query->orderBy('due_date', 'asc');
                break;
            case 'amount_desc':
                $query->orderBy('total_amount', 'desc');
                break;
            case 'amount_asc':
                $query->orderBy('total_amount', 'asc');
                break;
            case 'created_at_desc':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderByRaw("CASE WHEN status = 'paid' THEN 1 WHEN status = 'partial' THEN 2 ELSE 3 END")
                      ->orderByRaw("COALESCE(paid_date, due_date) DESC");
                break;
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $schedules = $query->paginate($perPage);

        return response()->json($schedules);
    }

    /**
     * Get a specific repayment.
     */
    public function show($id)
    {
        $repayment = Repayment::with(['loan.client', 'schedule'])->find($id);

        if (!$repayment) {
            return response()->json([
                'success' => false,
                'message' => 'Repayment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $repayment
        ]);
    }

    /**
     * Create a new repayment.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:loans,id',
            'schedule_id' => 'nullable|exists:loan_schedules,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money,cheque,selcom_till,selcom_wallet,selcom_qr',
            'reference_number' => 'nullable|string|max:255',
            'payment_date' => 'required|date|before_or_equal:' . now()->timezone('Africa/Dar_es_Salaam')->toDateString(),
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $loan = Loan::find($request->loan_id);

        // Validate loan status
        if (!in_array($loan->status, ['disbursed', 'active', 'partially_paid'])) {
            return response()->json([
                'success' => false,
                'message' => 'Repayments can only be made for disbursed, active, or partially paid loans'
            ], 400);
        }

        // Round to 2dp to avoid floating point precision issues
        $requestedAmount   = round((float) $request->amount, 2);
        $outstandingBalance = round((float) $loan->outstanding_balance, 2);

        // Validate repayment amount against outstanding balance
        if ($requestedAmount > $outstandingBalance) {
            return response()->json([
                'success' => false,
                'message' => 'Repayment amount cannot exceed outstanding balance of TZS ' . number_format($outstandingBalance, 2)
            ], 400);
        }

        // If a specific schedule is selected, validate against that installment's unpaid amount
        if ($request->schedule_id) {
            $schedule = \App\Models\LoanSchedule::find($request->schedule_id);
            if ($schedule) {
                $unpaidForSchedule = round((float) $schedule->total_amount - (float) $schedule->paid_amount, 2);
                if ($requestedAmount > $unpaidForSchedule) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Repayment amount cannot exceed the installment due amount of TZS ' . number_format($unpaidForSchedule, 2) . ' for this schedule.'
                    ], 400);
                }
            }
        }

        DB::beginTransaction();

        try {
            // Create repayment
            $repayment = Repayment::create([
                'tenant_id' => $loan->tenant_id,
                'loan_id' => $request->loan_id,
                'schedule_id' => $request->schedule_id ?? null,
                'user_id' => auth()->id(),
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference' => $request->reference_number ?? null,
                'payment_date' => $request->payment_date ?? now()->toDateString(),
                'notes' => $request->notes ?? null,
            ]);

            // Update loan schedule paid_amounts so accessor reflects new balance
            $remainingAmount = (float) $request->amount;
            $schedules = $loan->schedules()->where('status', '!=', 'paid')->orderBy('due_date')->get();
            foreach ($schedules as $schedule) {
                if ($remainingAmount <= 0) break;
                $unpaid = $schedule->total_amount - $schedule->paid_amount;
                $pay = min($remainingAmount, $unpaid);
                $schedule->paid_amount += $pay;
                $remainingAmount -= $pay;
                if ($schedule->paid_amount >= $schedule->total_amount) {
                    $schedule->status = 'paid';
                    $schedule->paid_date = $request->payment_date;
                } elseif ($schedule->paid_amount > 0) {
                    $schedule->status = 'partial';
                }
                $schedule->save();
            }

            // Update loan status based on whether ALL schedule installments are paid
            $loan->refresh();
            $unpaidSchedules = $loan->schedules()->whereNotIn('status', ['paid'])->count();
            if ($unpaidSchedules === 0) {
                $loan->status = 'completed';
                $loan->completed_at = now();
                $loan->save();
            } elseif ($loan->status === 'disbursed') {
                $loan->status = 'active';
                $loan->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Repayment recorded successfully',
                'data' => $repayment->load(['loan.client', 'schedule'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record repayment'
            ], 500);
        }
    }

    /**
     * Admin: directly correct a paid schedule's amount/date/method.
     * Works regardless of whether a repayment record is linked via schedule_id.
     */
    public function correctPayment(Request $request, $scheduleId)
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

        $schedule = LoanSchedule::with('loan')->find($scheduleId);
        if (!$schedule) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount'           => 'required|numeric|min:0',
            'payment_method'   => 'required|in:cash,bank_transfer,mobile_money,cheque',
            'payment_date'     => 'required|date|before_or_equal:today',
            'reference_number' => 'nullable|string|max:255',
            'notes'            => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $newAmount = (float) $request->amount;

            // Determine new schedule status
            if ($newAmount >= (float) $schedule->total_amount) {
                $newStatus   = 'paid';
                $newPaidDate = $request->payment_date;
            } elseif ($newAmount > 0) {
                $newStatus   = 'partial';
                $newPaidDate = $request->payment_date;
            } else {
                $newStatus   = 'pending';
                $newPaidDate = null;
            }

            $schedule->update([
                'paid_amount' => $newAmount,
                'paid_date'   => $newPaidDate,
                'status'      => $newStatus,
            ]);

            // Also update the latest linked repayment if one exists
            $linked = $schedule->latestRepayment;
            if ($linked) {
                $linked->update([
                    'amount'         => $newAmount,
                    'payment_method' => $request->payment_method,
                    'payment_date'   => $request->payment_date,
                    'reference'      => $request->reference_number,
                    'notes'          => $request->notes,
                ]);
            }

            // Recalculate loan status
            $loan = $schedule->loan->fresh();
            $unpaidCount = $loan->schedules()->whereNotIn('status', ['paid'])->count();
            if ($unpaidCount === 0) {
                $loan->update(['status' => 'completed', 'completed_at' => now()]);
            } elseif ($loan->status === 'completed') {
                $loan->update(['status' => 'active', 'completed_at' => null]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Payment corrected successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update a repayment.
     */
    public function update(Request $request, $id)
    {
        $repayment = Repayment::find($id);

        if (!$repayment) {
            return response()->json([
                'success' => false,
                'message' => 'Repayment not found'
            ], 404);
        }

        // Check admin access (role column OR RBAC slug)
        $user = auth()->user();
        $user->loadMissing('roles');
        $adminSlugs = ['admin', 'administrator'];
        $isAdmin = $user->isAdmin()
            || $user->roles->pluck('slug')->map(fn($s) => strtolower($s))->intersect($adminSlugs)->isNotEmpty();

        if (!$isAdmin && $repayment->created_at->diffInHours(now()) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Repayments can only be edited within 24 hours of creation. Contact an admin to correct older records.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money,cheque,selcom_till,selcom_wallet,selcom_qr',
            'reference_number' => 'nullable|string|max:255',
            'payment_date' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:500',
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
            $oldAmount = $repayment->amount;
            $newAmount = $request->amount;
            $amountDifference = $newAmount - $oldAmount;

            // Update schedule if applicable
            if ($repayment->schedule_id) {
                $schedule = $repayment->schedule;
                $schedule->paid_amount += $amountDifference;

                if ($schedule->paid_amount >= $schedule->total_amount) {
                    $schedule->status = 'paid';
                    $schedule->paid_date = $request->payment_date ?? $repayment->payment_date;
                } else {
                    $schedule->status = $schedule->paid_amount > 0 ? 'partial' : 'pending';
                }

                $schedule->save();
            }

            // Update repayment record
            $repayment->update([
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference' => $request->reference_number,
                'payment_date' => $request->payment_date ?? $repayment->payment_date,
                'notes' => $request->notes,
            ]);

            // Refresh loan and update status
            $loan = $repayment->loan->fresh();
            if ($loan->outstanding_balance <= 0) {
                $loan->status = 'completed';
                $loan->completed_at = now();
            } elseif ($loan->status === 'completed') {
                $loan->status = 'active';
                $loan->completed_at = null;
            }
            $loan->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Repayment updated successfully',
                'data' => $repayment->load(['loan.client', 'schedule'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update repayment'
            ], 500);
        }
    }

    /**
     * Delete a repayment.
     */
    public function destroy(Request $request, $id)
    {
        $repayment = Repayment::find($id);

        if (!$repayment) {
            return response()->json([
                'success' => false,
                'message' => 'Repayment not found'
            ], 404);
        }

        // Admins can delete any repayment; others only within 24 hours
        if (!auth()->user()->isAdmin() && $repayment->created_at->diffInHours(now()) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Repayments can only be deleted within 24 hours of creation'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Reverse schedule paid_amount
            if ($repayment->schedule_id) {
                $schedule = $repayment->schedule;
                $schedule->paid_amount -= $repayment->amount;

                if ($schedule->paid_amount <= 0) {
                    $schedule->status = 'pending';
                    $schedule->paid_date = null;
                } else {
                    $schedule->status = 'partial';
                }

                $schedule->save();
            }

            $loan = $repayment->loan;
            $repayment->delete();

            // Refresh loan status
            $loan = $loan->fresh();
            if ($loan->outstanding_balance > 0 && $loan->status === 'completed') {
                $loan->status = 'active';
                $loan->completed_at = null;
                $loan->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Repayment deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete repayment'
            ], 500);
        }
    }

    /**
     * Get repayment statistics.
     */
    public function statistics()
    {
        $today = today();
        $dueToday = LoanSchedule::where('due_date', $today)
                                ->whereIn('status', ['pending', 'partial'])
                                ->sum(DB::raw('total_amount - paid_amount'));

        $overdueAmount = LoanSchedule::where('due_date', '<', $today)
                                    ->whereIn('status', ['pending', 'partial'])
                                    ->sum(DB::raw('total_amount - paid_amount'));

        $stats = [
            'total_repayments' => Repayment::count(),
            'total_amount_collected' => Repayment::sum('amount'),
            'today_collections' => Repayment::whereDate('payment_date', $today)->sum('amount'),
            'month_collections' => Repayment::whereMonth('payment_date', now()->month)
                                           ->whereYear('payment_date', now()->year)
                                           ->sum('amount'),
            'due_today' => $dueToday,
            'overdue_amount' => $overdueAmount,
        ];

        return response()->json($stats);
    }

    /**
     * Search loans for repayment.
     */
    public function searchLoans(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->get('query');
        $loans = Loan::with('client')
                     ->whereIn('status', ['disbursed', 'active', 'partially_paid'])
                     ->where(function ($q) use ($query) {
                         $q->where('loan_number', 'like', "%{$query}%")
                           ->orWhereHas('client', function ($clientQuery) use ($query) {
                               $clientQuery->where('first_name', 'like', "%{$query}%")
                                           ->orWhere('last_name', 'like', "%{$query}%")
                                           ->orWhere('phone', 'like', "%{$query}%");
                           });
                     })
                     ->limit(10)
                     ->get(['id', 'loan_number', 'client_id', 'outstanding_balance']);

        return response()->json([
            'success' => true,
            'data' => $loans
        ]);
    }

    /**
     * Generate reference number.
     */
    private function generateReferenceNumber()
    {
        $prefix = 'REP';
        $timestamp = now()->format('YmdHis');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $timestamp . $random;
    }
}