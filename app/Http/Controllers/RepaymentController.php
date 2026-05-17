<?php

namespace App\Http\Controllers;

use App\Models\Repayment;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\SelcomTransaction;
use App\Services\NotificationSmsService;
use App\Services\SelcomPaymentService;
use App\Services\Accounting\AutomatedAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepaymentController extends Controller
{
    /**
     * Display a listing of the repayments.
     */
    public function index(Request $request)
    {
        $query = Repayment::with(['loan.client', 'user'])
            ->whereHas('loan', function ($q) {
                $q->whereIn('status', ['disbursed', 'active']);
            });

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhereHas('loan', function ($loanQuery) use ($search) {
                      $loanQuery->where('loan_number', 'like', "%{$search}%")
                               ->orWhereHas('client', function ($clientQuery) use ($search) {
                                   $clientQuery->where('first_name', 'like', "%{$search}%")
                                              ->orWhere('last_name', 'like', "%{$search}%");
                               });
                  });
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('payment_date', '<=', $request->date_to);
        }

        // Payment method filter
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $repayments = $query->latest('payment_date')->paginate(15);

        return view('repayments.index', compact('repayments'));
    }

    /**
     * Display repayment history grouped by period (daily/weekly/monthly).
     */
    public function history(Request $request)
    {
        $period   = $request->get('period', 'daily');
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->get('date_to', now()->format('Y-m-d'));

        // Handle CSV export
        if ($request->get('export') === 'csv') {
            return $this->exportHistoryCsv($dateFrom, $dateTo);
        }

        // BaseModel global scope already filters by tenant_id automatically
        $repayments = Repayment::with(['loan.client', 'user'])
            ->whereHas('loan', function ($q) {
                $q->whereIn('status', ['disbursed', 'active']);
            })
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo)
            ->orderBy('payment_date')
            ->get();

        // Group by period
        $summary = $repayments->groupBy(function ($r) use ($period) {
            $date = \Carbon\Carbon::parse($r->payment_date);
            if ($period === 'weekly') {
                return $date->startOfWeek()->format('Y-m-d');
            } elseif ($period === 'monthly') {
                return $date->format('Y-m');
            } else {
                return $date->format('Y-m-d');
            }
        })->map(function ($group) {
            return [
                'items' => $group,
                'count' => $group->count(),
                'total' => $group->sum('amount'),
            ];
        });

        $grandTotal  = $repayments->sum('amount');
        $totalCount  = $repayments->count();

        return view('repayments.history', compact(
            'summary', 'period', 'dateFrom', 'dateTo', 'grandTotal', 'totalCount'
        ));
    }

    protected function exportHistoryCsv(string $dateFrom, string $dateTo)
    {
        // BaseModel global scope already filters by tenant_id automatically
        $repayments = Repayment::with(['loan.client', 'user'])
            ->whereHas('loan', function ($q) {
                $q->whereIn('status', ['disbursed', 'active']);
            })
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo)
            ->orderBy('payment_date')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"repayment-history-{$dateFrom}-to-{$dateTo}.csv\"",
        ];

        $callback = function () use ($repayments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Receipt No', 'Borrower', 'Loan Number', 'Payment Date', 'Amount', 'Method', 'Paid By']);
            foreach ($repayments as $r) {
                $client = optional(optional($r->loan)->client);
                fputcsv($handle, [
                    $r->receipt_number,
                    trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')) ?: ($client->full_name ?? '—'),
                    optional($r->loan)->loan_number ?? '—',
                    $r->payment_date,
                    $r->amount,
                    $r->payment_method ?? 'cash',
                    optional($r->user)->name ?? '—',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show the form for creating a new repayment.
     */
    public function create(Request $request)
    {
        $tenantId = auth()->user()->tenant_id ?? session('tenant_id');
        
        $loan = null;
        $schedules = collect();

        // Get only loans that are truly disbursed AND have actual unpaid schedules
        $loans = Loan::where('tenant_id', $tenantId)
            ->whereIn('status', ['disbursed', 'active', 'partially_paid'])
            ->whereHas('schedules', function ($q) {
                $q->whereIn('status', ['pending', 'partial']);
            })
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->filled('loan_id')) {
            $loan = Loan::with(['client', 'schedules' => function ($query) {
                $query->orderBy('due_date');
            }])->where('tenant_id', $tenantId)->findOrFail($request->loan_id);
            
            $schedules = $loan->schedules;
        }

        return view('repayments.create', compact('loan', 'schedules', 'loans'));
    }

    /**
     * Store a newly created repayment in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'schedule_id' => 'nullable|exists:loan_schedules,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money,cheque,selcom_till,selcom_wallet,selcom_qr',
            'reference' => 'nullable|string|max:100',
            'payment_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:500',
            'phone_number' => 'required_if:payment_method,selcom_till,selcom_wallet|nullable|string|max:15',
            'email' => 'nullable|email|max:100',
        ]);

        $loan = Loan::findOrFail($validated['loan_id']);

        // Validate that loan can accept repayments
        if (!in_array($loan->status, ['pending', 'approved', 'disbursed', 'active', 'partially_paid'])) {
            return back()->withErrors([
                'loan_id' => 'Repayments can only be made for approved or active loans.'
            ])->withInput();
        }

        // Validate repayment amount against outstanding balance
        if ($validated['amount'] > $loan->outstanding_balance) {
            return back()->withErrors([
                'amount' => 'Repayment amount cannot exceed outstanding balance of TZS ' . number_format($loan->outstanding_balance, 2) . '.'
            ])->withInput();
        }

        // If a specific schedule is selected, also validate against that installment's unpaid amount
        if (!empty($validated['schedule_id'])) {
            $schedule = \App\Models\LoanSchedule::find($validated['schedule_id']);
            if ($schedule) {
                $unpaidForSchedule = $schedule->total_amount - $schedule->paid_amount;
                if ($validated['amount'] > $unpaidForSchedule) {
                    return back()->withErrors([
                        'amount' => 'Repayment amount cannot exceed installment due amount of TZS ' . number_format($unpaidForSchedule, 2) . ' for this schedule.'
                    ])->withInput();
                }
            }
        }

        // Handle Selcom payments
        if (in_array($validated['payment_method'], ['selcom_till', 'selcom_wallet', 'selcom_qr'])) {
            return $this->processSelcomPayment($validated, $loan);
        }

        DB::transaction(function () use ($validated, $loan) {
            // Create repayment
            $repayment = Repayment::create([
                'tenant_id' => session('tenant_id') ?? $loan->tenant_id,
                'loan_id' => $validated['loan_id'],
                'schedule_id' => $validated['schedule_id'] ?? null,
                'user_id' => auth()->id(),
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'payment_date' => $validated['payment_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update loan schedule status
            $remainingAmount = $validated['amount'];
            $schedules = $loan->schedules()->where('status', '!=', 'paid')->orderBy('due_date')->get();
            
            foreach ($schedules as $schedule) {
                if ($remainingAmount <= 0) break;
                
                $unpaidAmount = $schedule->total_amount - $schedule->paid_amount;
                $paymentForSchedule = min($remainingAmount, $unpaidAmount);
                
                $schedule->paid_amount += $paymentForSchedule;
                $remainingAmount -= $paymentForSchedule;
                
                if ($schedule->paid_amount >= $schedule->total_amount) {
                    $schedule->status = 'paid';
                    $schedule->paid_date = $validated['payment_date'];
                } elseif ($schedule->paid_amount > 0) {
                    $schedule->status = 'partial';
                }
                
                $schedule->save();
            }

            // Update loan status: completed only when ALL schedules are paid
            $loan->refresh();
            $unpaidCount = $loan->schedules()->whereNotIn('status', ['paid'])->count();
            if ($unpaidCount === 0) {
                $loan->update(['status' => 'completed']);
            } elseif ($loan->status === 'disbursed') {
                $loan->update(['status' => 'active']);
            }
        });

        // Send repayment confirmation SMS (non-critical, outside transaction)
        $latest = null;
        try {
            $latest = Repayment::where('loan_id', $validated['loan_id'])
                ->latest()->first();
            if ($latest) {
                app(NotificationSmsService::class)->sendRepaymentConfirmationSms($latest->load('loan.client'));
            }
        } catch (\Throwable $e) {
            Log::warning('Repayment SMS failed silently', ['loan_id' => $validated['loan_id'], 'error' => $e->getMessage()]);
        }

        // Record accounting journal entry for repayment (non-critical)
        try {
            $repaymentForAccounting = $latest ?? Repayment::where('loan_id', $validated['loan_id'])->latest()->first();
            if ($repaymentForAccounting) {
                app(AutomatedAccountingService::class)->recordLoanRepayment($repaymentForAccounting->loadMissing(['loan', 'schedule']));
            }
        } catch (\Throwable $e) {
            Log::warning('Accounting entry for repayment failed silently', ['loan_id' => $validated['loan_id'], 'error' => $e->getMessage()]);
        }

        return redirect()->route('repayments.index')
                        ->with('success', 'Repayment recorded successfully.');
    }

    /**
     * Display the specified repayment.
     */
    public function show(Repayment $repayment)
    {
        $repayment->load(['loan.client', 'schedule', 'user']);
        return view('repayments.show', compact('repayment'));
    }

    /**
     * Show the form for editing the specified repayment.
     */
    public function edit(Repayment $repayment)
    {
        // Admins can edit any repayment; others only within 24 hours
        if (!auth()->user()->isAdmin() && $repayment->created_at->diffInHours(now()) > 24) {
            return redirect()->route('repayments.show', $repayment)
                           ->with('error', 'Repayments can only be edited within 24 hours of creation.');
        }
        return view('repayments.edit', compact('repayment'));
    }

    /**
     * Update the specified repayment in storage.
     */
    public function update(Request $request, Repayment $repayment)
    {
        // Admins can edit any repayment; others only within 24 hours
        if (!auth()->user()->isAdmin() && $repayment->created_at->diffInHours(now()) > 24) {
            return redirect()->route('repayments.show', $repayment)
                           ->with('error', 'Repayments can only be edited within 24 hours of creation.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money,cheque',
            'reference' => 'nullable|string|max:100',
            'payment_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($validated, $repayment) {
            $oldAmount = $repayment->amount;
            
            // Update repayment
            $repayment->update($validated);

            // If amount changed, update schedule
            if ($oldAmount != $validated['amount'] && $repayment->schedule_id) {
                $schedule = $repayment->schedule;
                $schedule->paid_amount = $schedule->paid_amount - $oldAmount + $validated['amount'];
                $schedule->status = $schedule->isFullyPaid() ? 'paid' : 'partial';
                $schedule->save();
            }

            // Update loan status
            $loan = $repayment->loan;
            if ($loan->outstanding_balance <= 0) {
                $loan->update(['status' => 'completed']);
            } elseif ($loan->status === 'completed' && $loan->outstanding_balance > 0) {
                $loan->update(['status' => 'active']);
            }
        });

        return redirect()->route('repayments.show', $repayment)
                        ->with('success', 'Repayment updated successfully.');
    }

    /**
     * Remove the specified repayment from storage.
     */
    public function destroy(Repayment $repayment)
    {
        // Admins can delete any repayment; others only within 24 hours
        if (!auth()->user()->isAdmin() && $repayment->created_at->diffInHours(now()) > 24) {
            return redirect()->route('repayments.show', $repayment)
                           ->with('error', 'Repayments can only be deleted within 24 hours of creation.');
        }

        DB::transaction(function () use ($repayment) {
            // Update schedule if applicable
            if ($repayment->schedule_id) {
                $schedule = $repayment->schedule;
                $schedule->paid_amount -= $repayment->amount;
                $schedule->status = $schedule->paid_amount > 0 ? 'partial' : 'pending';
                $schedule->save();
            }

            // Update loan status
            $loan = $repayment->loan;
            $repayment->delete();
            
            $loan = $loan->fresh();
            if ($loan->outstanding_balance > 0 && $loan->status === 'completed') {
                $loan->update(['status' => 'active']);
            }
        });

        return redirect()->route('repayments.index')
                        ->with('success', 'Repayment deleted successfully.');
    }

    /**
     * Display repayments for a specific loan.
     */
    public function loanRepayments(Request $request, Loan $loan)
    {
        $loan->load(['client', 'repayments.user', 'schedules']);
        $repayments = $loan->repayments()->with('user')->latest('payment_date')->get();
        $schedules  = $loan->schedules()->orderBy('due_date')->get();
        return view('repayments.loan', compact('loan', 'repayments', 'schedules'));
    }

    /**
     * Record a payment (alias for store, used by some routes).
     */
    public function recordPayment(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Search for loans to make repayment.
     */
    public function searchLoans(Request $request)
    {
        $search = $request->get('q');
        
        $loans = Loan::with('client')
                    ->whereIn('status', ['disbursed', 'active', 'partially_paid'])
                    ->whereHas('schedules', function ($q) {
                        $q->whereIn('status', ['pending', 'partial']);
                    })
                    ->where(function ($query) use ($search) {
                        $query->where('loan_number', 'like', "%{$search}%")
                              ->orWhereHas('client', function ($clientQuery) use ($search) {
                                  $clientQuery->where('first_name', 'like', "%{$search}%")
                                             ->orWhere('last_name', 'like', "%{$search}%")
                                             ->orWhere('phone', 'like', "%{$search}%");
                              });
                    })
                    ->limit(10)
                    ->get();

        return response()->json($loans->map(function ($loan) {
            return [
                'id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'client_name' => $loan->client->full_name,
                'outstanding_balance' => $loan->outstanding_balance,
            ];
        }));
    }

    /**
     * Get repayment statistics for the repayments dashboard cards.
     * Keys must match what the view expects: today_collections, month_collections, due_today, overdue_amount
     */
    public function statistics()
    {
        $activeLoanIds = Loan::whereIn('status', ['disbursed', 'active'])->pluck('id');
        $today = now()->toDateString();

        // TODAY'S COLLECTIONS = actual payments recorded today
        $todayCollections = (float) Repayment::whereIn('loan_id', $activeLoanIds)
            ->whereDate('payment_date', today())
            ->sum('amount');

        // THIS MONTH COLLECTIONS = actual payments recorded this month
        $monthCollections = (float) Repayment::whereIn('loan_id', $activeLoanIds)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        // DUE TODAY = sum of (total_amount - paid_amount) on schedules due exactly today
        $dueToday = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', $today)
            ->selectRaw('SUM(total_amount - paid_amount) as due_remaining')
            ->value('due_remaining') ?? 0.0;
        $dueToday = max(0.0, $dueToday);

        // OVERDUE AMOUNT = sum of (total_amount - paid_amount) on past-due schedules
        $overdueAmount = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', $today)
            ->selectRaw('SUM(total_amount - paid_amount) as overdue_remaining')
            ->value('overdue_remaining') ?? 0.0;
        $overdueAmount = max(0.0, $overdueAmount);

        return response()->json([
            'today_collections'      => $todayCollections,
            'month_collections'      => $monthCollections,
            'due_today'              => $dueToday,
            'overdue_amount'         => $overdueAmount,
            'total_repayments'       => Repayment::whereIn('loan_id', $activeLoanIds)->count(),
            'total_amount_collected' => Repayment::whereIn('loan_id', $activeLoanIds)->sum('amount'),
        ]);
    }

    /**
     * Process Selcom payment
     */
    private function processSelcomPayment(array $validated, Loan $loan)
    {
        try {
            $selcomService = new SelcomPaymentService();

            // Check if Selcom is properly configured
            $configCheck = $selcomService->checkConfiguration();
            if (!$configCheck['configured']) {
                Log::error('Selcom payment gateway not configured', [
                    'errors' => $configCheck['errors'],
                    'loan_id' => $loan->id,
                ]);
                
                return back()->withErrors([
                    'payment_method' => 'Payment service is not properly configured. Please contact support.'
                ])->withInput();
            }

            // Validate Selcom service is available
            if (!$selcomService->isServiceAvailable()) {
                return back()->withErrors([
                    'payment_method' => 'Selcom payment service is currently unavailable. Please try again later.'
                ])->withInput();
            }

            // Validate payment data
            $paymentData = [
                'amount' => $validated['amount'],
                'phone_number' => $validated['phone_number'] ?? '',
                'reference' => $validated['reference'] ?? "LOAN_{$loan->loan_number}_" . time(),
            ];

            $validationErrors = $selcomService->validatePayment($paymentData);
            if (!empty($validationErrors)) {
                return back()->withErrors([
                    'phone_number' => implode(', ', $validationErrors)
                ])->withInput();
            }

            // Create Selcom transaction record
            $selcomTransaction = SelcomTransaction::createTransaction([
                'reference' => $paymentData['reference'],
                'till_number' => config('services.selcom.till_number'),
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'customer_phone' => $selcomService->formatPhoneNumber($validated['phone_number']),
                'customer_email' => $validated['email'] ?? null,
                'description' => "Loan repayment for {$loan->loan_number}",
                'loan_id' => $loan->id,
                'user_id' => auth()->id(),
            ]);

            // Prepare payment data for Selcom API
            $paymentRequestData = [
                'reference' => $paymentData['reference'],
                'amount' => $validated['amount'],
                'phone_number' => $validated['phone_number'],
                'email' => $validated['email'] ?? null,
                'description' => "Loan repayment for {$loan->loan_number}",
                'loan_number' => $loan->loan_number,
                'transaction_id' => $selcomTransaction->transaction_id,
            ];

            // Process payment based on method
            if ($validated['payment_method'] === 'selcom_till') {
                $response = $selcomService->processTillPayment($paymentRequestData);
            } elseif ($validated['payment_method'] === 'selcom_wallet') {
                $response = $selcomService->processPayment($paymentRequestData);
            } elseif ($validated['payment_method'] === 'selcom_qr') {
                $response = $selcomService->generatePaymentQR($paymentRequestData);
            } else {
                throw new \Exception('Invalid Selcom payment method');
            }

            // Update transaction with response
            $selcomTransaction->update([
                'request_payload' => $paymentRequestData,
                'response_payload' => $response,
                'selcom_order_id' => $response['order_id'] ?? null,
                'selcom_transaction_id' => $response['transid'] ?? null,
                'status' => $response['result'] === 'SUCCESS' ? SelcomTransaction::STATUS_PROCESSING : SelcomTransaction::STATUS_FAILED,
            ]);

            if (isset($response['result']) && $response['result'] === 'SUCCESS') {
                return redirect()->route('repayments.index')
                    ->with('success', 'Selcom payment initiated successfully. You will receive a payment prompt on your phone.')
                    ->with('selcom_transaction_id', $selcomTransaction->id);
            } else {
                $selcomTransaction->markAsFailed($response['message'] ?? 'Payment initiation failed');
                return back()->withErrors([
                    'payment_method' => 'Failed to initiate Selcom payment: ' . ($response['message'] ?? 'Unknown error')
                ])->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Selcom payment processing error', [
                'loan_id' => $loan->id,
                'amount' => $validated['amount'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'payment_method' => 'An error occurred while processing Selcom payment. Please try again.'
            ])->withInput();
        }
    }

    /**
     * Handle Selcom payment callback
     */
    public function selcomCallback(Request $request)
    {
        try {
            $selcomService = new SelcomPaymentService();
            $callbackData = $request->all();

            Log::info('Selcom callback received', $callbackData);

            // Verify callback signature
            if (!$selcomService->verifyCallback($callbackData)) {
                Log::warning('Invalid Selcom callback signature', $callbackData);
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
            }

            // Find transaction
            $transactionId = $callbackData['reference'] ?? null;
            $selcomTransaction = SelcomTransaction::where('reference', $transactionId)
                ->orWhere('selcom_transaction_id', $callbackData['transid'] ?? null)
                ->first();

            if (!$selcomTransaction) {
                Log::warning('Selcom transaction not found', $callbackData);
                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            // Update transaction with callback data
            $selcomTransaction->updateCallbackData($callbackData);

            // Process payment based on status
            if (isset($callbackData['result']) && $callbackData['result'] === 'SUCCESS') {
                $this->completeSelcomPayment($selcomTransaction, $callbackData);
            } else {
                $selcomTransaction->markAsFailed($callbackData['message'] ?? 'Payment failed');
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Selcom callback processing error', [
                'callback_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['status' => 'error', 'message' => 'Callback processing failed'], 500);
        }
    }

    /**
     * Complete Selcom payment and create repayment record
     */
    private function completeSelcomPayment(SelcomTransaction $selcomTransaction, array $callbackData)
    {
        DB::transaction(function () use ($selcomTransaction, $callbackData) {
            // Mark transaction as completed
            $selcomTransaction->markAsCompleted($callbackData['payment_date'] ?? now());

            // Create repayment record
            $repayment = Repayment::create([
                'loan_id' => $selcomTransaction->loan_id,
                'user_id' => $selcomTransaction->user_id,
                'amount' => $selcomTransaction->amount,
                'payment_method' => $selcomTransaction->payment_method,
                'reference' => $selcomTransaction->reference,
                'payment_date' => $selcomTransaction->payment_date,
                'notes' => "Selcom payment - Transaction ID: {$selcomTransaction->transaction_id}",
            ]);

            // Update Selcom transaction with repayment ID
            $selcomTransaction->update(['repayment_id' => $repayment->id]);

            // Update loan status
            $loan = $selcomTransaction->loan;
            if ($loan->fresh()->outstanding_balance <= 0) {
                $loan->update(['status' => 'completed']);
            } elseif ($loan->status === 'disbursed') {
                $loan->update(['status' => 'active']);
            }

            Log::info('Selcom payment completed successfully', [
                'transaction_id' => $selcomTransaction->transaction_id,
                'repayment_id' => $repayment->id,
                'loan_id' => $loan->id,
                'amount' => $selcomTransaction->amount
            ]);
        });
    }

    /**
     * API endpoint to initiate Selcom payment
     */
    public function initiateSelcomPayment(Request $request)
    {
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:selcom_till,selcom_wallet,selcom_qr',
            'phone_number' => 'required_if:payment_method,selcom_till,selcom_wallet|string',
            'email' => 'nullable|email',
            'till_number' => 'nullable|string',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $loan = Loan::findOrFail($validated['loan_id']);
            
            // Check if loan can accept payments
            if (!in_array($loan->status, ['active', 'partially_paid'])) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'This loan cannot accept payments in its current status'
                ], 400);
            }

            $selcomService = new SelcomPaymentService();
            
            // Generate unique reference
            $reference = 'REP_' . time() . '_' . $loan->id;
            
            // Create transaction record
            $selcomTransaction = SelcomTransaction::createTransaction([
                'reference' => $reference,
                'till_number' => $validated['till_number'] ?? config('services.selcom.till_number'),
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'customer_phone' => $selcomService->formatPhoneNumber($validated['phone_number']),
                'customer_email' => $validated['email'] ?? null,
                'description' => "Loan repayment for {$loan->loan_number}",
                'loan_id' => $loan->id,
                'user_id' => auth()->id(),
            ]);

            // Prepare payment data
            $paymentData = [
                'reference' => $reference,
                'amount' => $validated['amount'],
                'phone_number' => $validated['phone_number'],
                'email' => $validated['email'] ?? null,
                'description' => "Loan repayment for {$loan->loan_number}",
                'loan_number' => $loan->loan_number,
                'transaction_id' => $selcomTransaction->transaction_id,
            ];

            // Process payment based on method
            if ($validated['payment_method'] === 'selcom_till') {
                $response = $selcomService->processTillPayment($paymentData);
            } elseif ($validated['payment_method'] === 'selcom_wallet') {
                $response = $selcomService->processPayment($paymentData);
            } elseif ($validated['payment_method'] === 'selcom_qr') {
                $response = $selcomService->generatePaymentQR($paymentData);
            } else {
                throw new \Exception('Invalid Selcom payment method');
            }

            if ($response['status'] === 'success') {
                // Update transaction with Selcom response
                $selcomTransaction->update([
                    'selcom_transaction_id' => $response['transaction_id'] ?? null,
                    'selcom_reference' => $response['reference'] ?? null,
                    'payload' => $response,
                    'status' => 'pending'
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment initiated successfully',
                    'transaction_id' => $selcomTransaction->transaction_id,
                    'payment_data' => $response
                ]);
            } else {
                $selcomTransaction->markAsFailed($response['message'] ?? 'Payment initiation failed');
                
                return response()->json([
                    'status' => 'error',
                    'message' => $response['message'] ?? 'Failed to initiate payment'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error initiating Selcom payment', [
                'loan_id' => $validated['loan_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initiate payment'
            ], 500);
        }
    }

    /**
     * API endpoint to check Selcom payment status
     */
    public function checkSelcomPaymentStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'transaction_id' => 'required|string'
            ]);

            $transaction = SelcomTransaction::where('transaction_id', $validated['transaction_id'])
                ->with('loan')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaction not found'
                ], 404);
            }

            // If transaction has a repayment ID, also check repayment status
            $repaymentStatus = null;
            if ($transaction->repayment_id) {
                $repayment = Repayment::find($transaction->repayment_id);
                if ($repayment) {
                    $repaymentStatus = [
                        'repayment_id' => $repayment->id,
                        'receipt_number' => $repayment->receipt_number,
                        'payment_date' => $repayment->payment_date,
                        'amount' => $repayment->amount,
                        'payment_method' => $repayment->payment_method,
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'transaction' => [
                    'transaction_id' => $transaction->transaction_id,
                    'reference' => $transaction->reference,
                    'amount' => $transaction->amount,
                    'payment_method' => $transaction->payment_method,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                    'loan_number' => optional($transaction->loan)->loan_number,
                    'repayment' => $repaymentStatus
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking Selcom payment status', [
                'transaction_id' => $request->transaction_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check payment status'
            ], 500);
        }
    }
}
