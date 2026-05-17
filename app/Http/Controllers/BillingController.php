<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\BillingAuditLog;
use App\Services\NotificationSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BillingController extends Controller
{
    /**
     * Resolve the current tenant ID from session/app/user or abort.
     * Returns null for superadmin (system-wide access).
     */
    private function tenantIdOrAbort()
    {
        $user = auth()->user();
        
        // Check if user is superadmin
        if ($user) {
            $superAliases = ['super_admin', 'superadmin', 'super-admin', 'super admin'];
            $isSuper = in_array(strtolower($user->role ?? ''), array_map('strtolower', $superAliases))
                || (method_exists($user, 'hasRole') && (
                    $user->hasRole('super_admin') ||
                    $user->hasRole('superadmin') ||
                    $user->hasRole('super-admin') ||
                    $user->hasRole('super admin')
                ));
            
            if ($isSuper) {
                return null; // Superadmin has system-wide access
            }
        }
        
        $tenantId = session('tenant_id');
        if ($tenantId) {
            return $tenantId;
        }

        $tenant = session('tenant');
        if (is_object($tenant) && isset($tenant->id)) {
            return $tenant->id;
        }
        if (is_array($tenant) && isset($tenant['id'])) {
            return $tenant['id'];
        }

        if (app()->bound('tenant')) {
            $appTenant = app('tenant');
            if ($appTenant && isset($appTenant->id)) {
                return $appTenant->id;
            }
        }

        if ($user && $user->tenant_id) {
            // Cache in session for future requests
            session(['tenant_id' => $user->tenant_id]);
            return $user->tenant_id;
        }

        abort(403, 'Tenant context not resolved.');
    }

    /**
     * Display billing dashboard.
     */
    public function index()
    {
        $tenantId = $this->tenantIdOrAbort();
        
        // If superadmin (tenantId is null), show system-wide billing data
        if ($tenantId === null) {
            $invoices = Invoice::with(['payments', 'tenant'])
                              ->latest()
                              ->take(10)
                              ->get();

            // Core stats (system-wide)
            $stats = [
                'total_invoices' => Invoice::count(),
                'pending_invoices' => Invoice::pending()->count(),
                'overdue_invoices' => Invoice::overdue()->count(),
                'total_paid' => Payment::completed()->sum('amount'),
            ];

            // Collections MTD (TSHS)
            $collections_mtd = Payment::completed()
                                      ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                                      ->sum('amount');

            // Transaction status metrics (system-wide)
            $txn_metrics = [
                'successful' => [
                    'count' => Payment::where('status', 'success')->count(),
                    'amount' => Payment::where('status', 'success')->sum('amount'),
                ],
                'pending' => [
                    'count' => Payment::where('status', 'pending')->count(),
                    'amount' => Payment::where('status', 'pending')->sum('amount'),
                ],
                'failed' => [
                    'count' => Payment::where('status', 'failed')->count(),
                    'amount' => Payment::where('status', 'failed')->sum('amount'),
                ],
                'refunded' => [
                    'count' => Payment::where('status', 'refunded')->count(),
                    'amount' => Payment::where('status', 'refunded')->sum('amount'),
                ],
                'chargeback' => [
                    'count' => Payment::where('status', 'chargeback')->count(),
                    'amount' => Payment::where('status', 'chargeback')->sum('amount'),
                ],
            ];

            // Revenue by plan (system-wide)
            $revenue_by_plan_raw = DB::table('payments')
                ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->join('plans', 'invoices.plan_id', '=', 'plans.id')
                ->where('payments.status', 'success')
                ->select('plans.name as plan_name', DB::raw('SUM(payments.amount) as total'))
                ->groupBy('plans.name')
                ->get();
            $revenue_by_plan = [
                'Starter' => 0,
                'Pro' => 0,
                'Enterprise' => 0,
            ];
            foreach ($revenue_by_plan_raw as $row) {
                $name = $row->plan_name;
                if (isset($revenue_by_plan[$name])) {
                    $revenue_by_plan[$name] = (int) $row->total;
                } else {
                    $revenue_by_plan[$name] = (int) $row->total;
                }
            }

            // A/R Aging buckets (system-wide)
            $pendingInvoices = Invoice::pending()->get();
            $ar_aging = [
                '0_30' => ['count' => 0, 'amount' => 0],
                '31_60' => ['count' => 0, 'amount' => 0],
                '61_90' => ['count' => 0, 'amount' => 0],
                '90_plus' => ['count' => 0, 'amount' => 0],
            ];
            foreach ($pendingInvoices as $inv) {
                $days = $inv->due_date ? now()->diffInDays($inv->due_date, false) * -1 : 0;
                $outstanding = (int) $inv->remaining_balance;
                if ($days <= 30) {
                    $ar_aging['0_30']['count']++;
                    $ar_aging['0_30']['amount'] += $outstanding;
                } elseif ($days <= 60) {
                    $ar_aging['31_60']['count']++;
                    $ar_aging['31_60']['amount'] += $outstanding;
                } elseif ($days <= 90) {
                    $ar_aging['61_90']['count']++;
                    $ar_aging['61_90']['amount'] += $outstanding;
                } else {
                    $ar_aging['90_plus']['count']++;
                    $ar_aging['90_plus']['amount'] += $outstanding;
                }
            }

            // Selcom ledger (system-wide)
            $selcom_ledger = Payment::where('provider', 'selcom')
                                     ->latest()
                                     ->take(10)
                                     ->get();

            return view('billing.index', compact(
                'invoices',
                'stats',
                'collections_mtd',
                'txn_metrics',
                'revenue_by_plan',
                'ar_aging',
                'selcom_ledger'
            ))->with('subscription', null)->with('branch_count', 0);
        }
        
        // Tenant-specific billing data
        $subscription = Subscription::where('tenant_id', $tenantId)
                                   ->with('plan')
                                   ->first();

        $invoices = Invoice::where('tenant_id', $tenantId)
                          ->with('payments')
                          ->latest()
                          ->take(5)
                          ->get();

        // Core stats
        $stats = [
            'total_invoices' => Invoice::where('tenant_id', $tenantId)->count(),
            'pending_invoices' => Invoice::where('tenant_id', $tenantId)->pending()->count(),
            'overdue_invoices' => Invoice::where('tenant_id', $tenantId)->overdue()->count(),
            'total_paid' => Payment::where('tenant_id', $tenantId)->completed()->sum('amount'),
        ];

        // Collections MTD (TSHS)
        $collections_mtd = Payment::where('tenant_id', $tenantId)
                                  ->completed()
                                  ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                                  ->sum('amount');

        // Transaction status metrics
        $txn_metrics = [
            'successful' => [
                'count' => Payment::where('tenant_id', $tenantId)->where('status', 'success')->count(),
                'amount' => Payment::where('tenant_id', $tenantId)->where('status', 'success')->sum('amount'),
            ],
            'pending' => [
                'count' => Payment::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
                'amount' => Payment::where('tenant_id', $tenantId)->where('status', 'pending')->sum('amount'),
            ],
            'failed' => [
                'count' => Payment::where('tenant_id', $tenantId)->where('status', 'failed')->count(),
                'amount' => Payment::where('tenant_id', $tenantId)->where('status', 'failed')->sum('amount'),
            ],
            'refunded' => [
                'count' => Payment::where('tenant_id', $tenantId)->where('status', 'refunded')->count(),
                'amount' => Payment::where('tenant_id', $tenantId)->where('status', 'refunded')->sum('amount'),
            ],
            'chargeback' => [
                'count' => Payment::where('tenant_id', $tenantId)->where('status', 'chargeback')->count(),
                'amount' => Payment::where('tenant_id', $tenantId)->where('status', 'chargeback')->sum('amount'),
            ],
        ];

        // Revenue by plan (Starter, Pro, Enterprise)
        $revenue_by_plan_raw = DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->join('plans', 'invoices.plan_id', '=', 'plans.id')
            ->where('payments.tenant_id', $tenantId)
            ->where('payments.status', 'success')
            ->select('plans.name as plan_name', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('plans.name')
            ->get();
        $revenue_by_plan = [
            'Starter' => 0,
            'Pro' => 0,
            'Enterprise' => 0,
        ];
        foreach ($revenue_by_plan_raw as $row) {
            $name = $row->plan_name;
            if (isset($revenue_by_plan[$name])) {
                $revenue_by_plan[$name] = (int) $row->total;
            } else {
                // Any other plan name goes under its own key
                $revenue_by_plan[$name] = (int) $row->total;
            }
        }

        // A/R Aging buckets based on pending invoices remaining balance
        $pendingInvoices = Invoice::where('tenant_id', $tenantId)->pending()->get();
        $ar_aging = [
            '0_30' => ['count' => 0, 'amount' => 0],
            '31_60' => ['count' => 0, 'amount' => 0],
            '61_90' => ['count' => 0, 'amount' => 0],
            '90_plus' => ['count' => 0, 'amount' => 0],
        ];
        foreach ($pendingInvoices as $inv) {
            $days = $inv->due_date ? now()->diffInDays($inv->due_date, false) * -1 : 0; // overdue days (negative becomes positive when inverted)
            $outstanding = (int) $inv->remaining_balance;
            if ($days <= 30) {
                $ar_aging['0_30']['count']++;
                $ar_aging['0_30']['amount'] += $outstanding;
            } elseif ($days <= 60) {
                $ar_aging['31_60']['count']++;
                $ar_aging['31_60']['amount'] += $outstanding;
            } elseif ($days <= 90) {
                $ar_aging['61_90']['count']++;
                $ar_aging['61_90']['amount'] += $outstanding;
            } else {
                $ar_aging['90_plus']['count']++;
                $ar_aging['90_plus']['amount'] += $outstanding;
            }
        }

        // Selcom top-up/payment ledger (recent)
        $selcom_ledger = Payment::where('tenant_id', $tenantId)
                                 ->where('provider', 'selcom')
                                 ->latest()
                                 ->take(10)
                                 ->get();

        // Branch count from cached settings (shared across app)
        $branches = cache()->get('setting_branches', []);
        if (is_string($branches)) {
            $decoded = json_decode($branches, true);
            $branches = is_array($decoded) ? $decoded : [];
        }
        $branch_count = max(1, (is_array($branches) ? count($branches) : 0));

        return view('billing.index', compact(
            'subscription',
            'invoices',
            'stats',
            'collections_mtd',
            'txn_metrics',
            'revenue_by_plan',
            'ar_aging',
            'selcom_ledger',
            'branch_count'
        ));
    }

    /**
     * Display all invoices.
     */
    public function invoices(Request $request)
    {
        $tenantId = $this->tenantIdOrAbort();
        
        $query = Invoice::where('tenant_id', $tenantId)->with(['plan', 'payments']);

        // Status filter
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'pending':
                    $query->pending();
                    break;
                case 'paid':
                    $query->paid();
                    break;
                case 'overdue':
                    $query->overdue();
                    break;
            }
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $invoices = $query->latest()->paginate(15);

        return view('billing.invoices', compact('invoices'));
    }

    /**
     * Display a specific invoice.
     */
    public function showInvoice(Invoice $invoice)
    {
        $tenantId = $this->tenantIdOrAbort();
        if ((int) $invoice->tenant_id !== (int) $tenantId) {
            abort(403, 'Unauthorized to view this invoice');
        }
        
        $invoice->load(['plan', 'payments']);
        
        return view('billing.invoice', compact('invoice'));
    }

    /**
     * Display all payments.
     */
    public function payments(Request $request)
    {
        $tenantId = $this->tenantIdOrAbort();
        
        $query = Payment::where('tenant_id', $tenantId)->with('invoice');

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Provider filter
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $payments = $query->latest()->paginate(15);

        return view('billing.payments', compact('payments'));
    }

    /**
     * Display a specific payment.
     */
    public function showPayment(Payment $payment)
    {
        $tenantId = $this->tenantIdOrAbort();
        if ((int) $payment->tenant_id !== (int) $tenantId) {
            abort(403, 'Unauthorized to view this payment');
        }
        
        $payment->load('invoice');
        
        return view('billing.payment', compact('payment'));
    }

    /**
     * Display available subscription plans.
     */
    public function plans()
    {
        $tenantId = $this->tenantIdOrAbort();

        $subscription = Subscription::where('tenant_id', $tenantId)
                                    ->with('plan')
                                    ->first();

        $plans = Plan::where('is_active', true)
            ->whereIn('code', ['starter','growth','enterprise'])
            ->get();

        $branches = cache()->get('setting_branches', []);
        if (is_string($branches)) {
            $decoded = json_decode($branches, true);
            $branches = is_array($decoded) ? $decoded : [];
        }
        $branch_count = max(1, (is_array($branches) ? count($branches) : 0));

        return view('billing.plans', compact('subscription', 'plans', 'branch_count'));
    }

    /**
     * Change subscription plan.
     */
    public function changePlan(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $tenantId = $this->tenantIdOrAbort();
        $newPlan = Plan::findOrFail($validated['plan_id']);
        
        $subscription = Subscription::where('tenant_id', $tenantId)->first();

        if (!$subscription) {
            return back()->with('error', 'No active subscription found.');
        }

        if ($subscription->plan_id == $newPlan->id) {
            return back()->with('error', 'You are already on this plan.');
        }

        DB::transaction(function () use ($subscription, $newPlan, $tenantId) {
            // Update subscription
            $subscription->update([
                'plan_id' => $newPlan->id,
                'current_period_end' => now()->addMonth(),
            ]);

            // Create prorated invoice if upgrading
            $currentPlan = $subscription->plan;
            if ($newPlan && $currentPlan) {
                // Determine monthly prices per branch
                $periodToMonthly = function ($plan) {
                    $period = strtolower((string) ($plan->period ?? 'month'));
                    $price = (float) ($plan->price ?? 0);
                    return in_array($period, ['year', 'annual', 'yearly']) ? ($price / 12.0) : $price;
                };
                $newMonthlyPerBranch = $periodToMonthly($newPlan);
                $currentMonthlyPerBranch = $periodToMonthly($currentPlan);

                // Branch count from cached settings
                $branches = cache()->get('setting_branches', []);
                if (is_string($branches)) {
                    $decoded = json_decode($branches, true);
                    $branches = is_array($decoded) ? $decoded : [];
                }
                $branchCount = max(1, (is_array($branches) ? count($branches) : 0));

                $daysRemaining = now()->diffInDays($subscription->current_period_end);
                $daysInMonth = now()->daysInMonth;
                $diffMonthly = $newMonthlyPerBranch - $currentMonthlyPerBranch;
                $proratedAmount = $diffMonthly * $branchCount * ($daysRemaining / $daysInMonth);

                if ($proratedAmount > 0) {
                    $invoice = Invoice::create([
                        'tenant_id' => $tenantId,
                        'plan_id' => $newPlan->id,
                        'number' => 'INV-' . strtoupper(uniqid()),
                        'amount' => $proratedAmount,
                        'currency' => 'TZS',
                        'due_date' => now()->addDays(7),
                        'status' => 'pending',
                    ]);

                    // Auto-link pending Selcom payment for the prorated invoice
                    Payment::create([
                        'tenant_id' => $tenantId,
                        'invoice_id' => $invoice->id,
                        'provider' => 'selcom',
                        'reference' => 'PAY-' . strtoupper(uniqid()),
                        'amount' => $invoice->remaining_balance,
                        'status' => 'pending',
                    ]);
                }
            }
        });

        return back()->with('success', 'Plan changed successfully.');
    }

    /**
     * Cancel subscription.
     */
    public function cancelSubscription(Request $request)
    {
        $tenantId = $this->tenantIdOrAbort();
        
        $subscription = Subscription::where('tenant_id', $tenantId)->first();

        if (!$subscription) {
            return back()->with('error', 'No active subscription found.');
        }

        $subscription->update([
            'status' => 'cancelled',
        ]);

        return back()->with('success', 'Subscription cancelled successfully. Access will continue until the end of the current billing period.');
    }

    /**
     * Reactivate subscription.
     */
    public function reactivateSubscription()
    {
        $tenantId = $this->tenantIdOrAbort();
        
        $subscription = Subscription::where('tenant_id', $tenantId)->first();

        if (!$subscription) {
            return back()->with('error', 'No subscription found.');
        }

        $subscription->update([
            'status' => 'active',
        ]);

        return back()->with('success', 'Subscription reactivated successfully.');
    }

    /**
     * Download invoice PDF.
     */
    public function downloadInvoice(Invoice $invoice)
    {
        $tenantId = $this->tenantIdOrAbort();
        if ((int) $invoice->tenant_id !== (int) $tenantId) {
            abort(403, 'Unauthorized to download this invoice');
        }
        
        // Here you would generate and return a PDF
        // For now, we'll just redirect back
        return back()->with('info', 'PDF download feature will be implemented soon.');
    }

    /**
     * Edit invoice (admin)
     */
    public function editInvoice(Invoice $invoice)
    {
        $tenantId = $this->tenantIdOrAbort();
        if ((int) $invoice->tenant_id !== (int) $tenantId) {
            abort(403, 'Unauthorized to edit this invoice');
        }

        $invoice->load(['plan', 'payments']);
        return view('billing.invoice-edit', compact('invoice'));
    }

    /**
     * Update invoice (admin)
     */
    public function updateInvoice(Request $request, Invoice $invoice)
    {
        $tenantId = $this->tenantIdOrAbort();
        if ((int) $invoice->tenant_id !== (int) $tenantId) {
            abort(403, 'Unauthorized to update this invoice');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'status' => 'required|in:pending,paid,failed',
        ]);

        $invoice->update([
            'amount' => (int) $validated['amount'],
            'due_date' => $validated['due_date'] ?? $invoice->due_date,
            'status' => $validated['status'],
        ]);

        // If status set to paid, mark the latest Selcom payment as success
        if ($invoice->status === 'paid') {
            $payment = $invoice->payments()->where('provider', 'selcom')->latest()->first();
            if ($payment && $payment->status !== 'success') {
                $payment->update(['status' => 'success', 'paid_at' => now()]);
            }
        }

        return redirect()->route('billing.invoices.show', $invoice)->with('success', 'Invoice updated successfully.');
    }

    /**
     * Initiate payment for an invoice.
     */
    public function payInvoice(Request $request, Invoice $invoice)
    {
        $tenantId = $this->tenantIdOrAbort();
        if ((int) $invoice->tenant_id !== (int) $tenantId) {
            abort(403, 'Unauthorized to pay this invoice');
        }

        $validated = $request->validate([
            // Selcom-only payments per requirements
            'provider' => 'required|in:selcom',
        ]);

        if ($invoice->isPaid()) {
            return back()->with('error', 'Invoice is already paid.');
        }

        // Create pending Selcom payment record (always link to Selcom)
        $payment = Payment::create([
            'tenant_id' => $invoice->tenant_id,
            'invoice_id' => $invoice->id,
            'provider' => 'selcom',
            'reference' => 'PAY-' . strtoupper(uniqid()),
            'amount' => $invoice->remaining_balance,
            'status' => 'pending',
        ]);

        // Integrate with actual payment providers
        if ($validated['provider'] === 'selcom') {
            // Minimal Selcom integration: mark pending and rely on webhook
            // In production, you would call Selcom API to initiate payment
            // and redirect the user to checkout or trigger STK push
            // We'll keep it pending and show a message
        }

        return back()->with('success', 'Selcom payment initiated. Awaiting confirmation.');
    }

    /**
     * Handle payment webhook.
     */
    public function webhook(Request $request, $provider)
    {
        // This would handle webhooks from payment providers
        // Implementation depends on the specific provider
        
        switch ($provider) {
            case 'stripe':
                return $this->handleStripeWebhook($request);
            case 'paypal':
                return $this->handlePaypalWebhook($request);
            case 'selcom':
                return $this->handleSelcomWebhook($request);
            default:
                return response()->json(['error' => 'Unknown provider'], 400);
        }
    }

    /**
     * Handle Stripe webhook.
     */
    private function handleStripeWebhook(Request $request)
    {
        // Stripe webhook handling logic
        return response()->json(['status' => 'success']);
    }

    /**
     * Handle PayPal webhook.
     */
    private function handlePaypalWebhook(Request $request)
    {
        // PayPal webhook handling logic
        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Selcom webhook.
     * Webhook payload format from Selcom:
     * {
     *   "result": "SUCCESS",
     *   "resultcode": "000",
     *   "order_id": "602021152",
     *   "transid": "7945454515",
     *   "reference": "856266164161",
     *   "channel": "TIGOPESATZ",
     *   "amount": "10000",
     *   "phone": "255000000001",
     *   "payment_status": "COMPLETED"
     * }
     */
    private function handleSelcomWebhook(Request $request)
    {
        $payload = $request->all();
        
        Log::info('Selcom Webhook Received', ['payload' => $payload]);
        
        // Extract Selcom response fields
        $orderId = $payload['order_id'] ?? null;
        $reference = $payload['reference'] ?? $payload['transid'] ?? null;
        $result = strtoupper($payload['result'] ?? '');
        $resultCode = $payload['resultcode'] ?? '';
        $paymentStatus = strtoupper($payload['payment_status'] ?? '');

        // Try to find payment by order_id first, then by reference
        $payment = null;
        if ($orderId) {
            $payment = Payment::where('reference', $orderId)->first();
        }
        if (!$payment && $reference) {
            $payment = Payment::where('reference', $reference)->first();
        }
        
        if (!$payment) {
            Log::warning('Selcom Webhook: Payment not found', [
                'order_id' => $orderId,
                'reference' => $reference
            ]);
            return response()->json(['error' => 'Payment not found'], 404);
        }

        // Persist payload for audit/troubleshooting
        $payment->update(['payload' => $payload]);

        // Determine status from Selcom response
        $isSuccess = ($result === 'SUCCESS' && $resultCode === '000') || $paymentStatus === 'COMPLETED';
        $isFailed = $result === 'FAIL' || in_array($paymentStatus, ['FAILED', 'DECLINED', 'ERROR']);

        if ($isSuccess) {
            $payment->markAsCompleted();

            // Update associated invoice if fully paid
            $invoice = $payment->invoice()->first();
            if ($invoice && $invoice->fresh()->remaining_balance <= 0) {
                $invoice->update(['status' => 'paid']);

                // Activate or extend subscription for the tenant based on invoice plan
                if ($invoice->plan_id) {
                    $tenantId = $payment->tenant_id;
                    $plan = Plan::find($invoice->plan_id);
                    if ($plan) {
                        $period = strtolower((string) ($plan->period ?? 'monthly'));
                        $months = match ($period) {
                            'monthly', 'month' => 1,
                            'semiannual', 'semi_annual', 'semiannualy', 'halfyear', 'half_year' => 6,
                            'annual', 'year', 'yearly' => 12,
                            default => 1,
                        };

                        $subscription = Subscription::firstOrCreate(
                            ['tenant_id' => $tenantId],
                            [
                                'plan_id' => $plan->id,
                                'status' => 'active',
                                'current_period_start' => now(),
                                'current_period_end' => now()->copy()->addMonths($months),
                                'grace_days' => 7,
                                'cancel_at_period_end' => false,
                            ]
                        );

                        if ($subscription->exists) {
                            $start = ($subscription->current_period_end && $subscription->current_period_end->isFuture())
                                ? $subscription->current_period_end
                                : now();
                            $subscription->update([
                                'plan_id' => $plan->id,
                                'status' => 'active',
                                'current_period_start' => $start,
                                'current_period_end' => $start->copy()->addMonths($months),
                                'cancel_at_period_end' => false,
                            ]);
                        }

                        // Update tenant plan_id and status when Selcom payment succeeds
                        $tenant = \App\Models\Tenant::find($tenantId);
                        if ($tenant) {
                            $tenant->update([
                                'plan_id' => $plan->id,
                                'plan_slug' => $plan->code,
                                'status' => 'active',
                                'plan_renews_at' => $subscription->current_period_end,
                            ]);
                        }

                        BillingAuditLog::create([
                            'tenant_id' => $tenantId,
                            'user_id' => null,
                            'action' => 'subscription_activated',
                            'meta' => [
                                'plan_code' => $plan->code,
                                'months_added' => $months,
                                'invoice_id' => $invoice->id,
                                'payment_reference' => $payment->reference,
                            ],
                        ]);

                        // Send subscription payment confirmation SMS to tenant admin
                        try {
                            if ($tenant) {
                                $renewsUntil = $subscription->current_period_end
                                    ? \Carbon\Carbon::parse($subscription->current_period_end)->format('M d, Y')
                                    : 'N/A';
                                app(NotificationSmsService::class)->sendSubscriptionPaymentSms(
                                    $tenant,
                                    (float) $payment->amount,
                                    $plan->name,
                                    $renewsUntil
                                );
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Subscription SMS failed silently', ['tenant_id' => $tenantId, 'error' => $e->getMessage()]);
                        }

                        // Alert superadmin about subscription payment
                        try {
                            if ($tenant) {
                                app(NotificationSmsService::class)->notifySuperadminSubscriptionPayment(
                                    $tenant,
                                    (float) $payment->amount,
                                    $plan->name
                                );
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Superadmin subscription SMS failed silently', ['tenant_id' => $tenantId, 'error' => $e->getMessage()]);
                        }
                    }
                }
            }

            BillingAuditLog::create([
                'tenant_id' => $payment->tenant_id,
                'user_id' => null,
                'action' => 'payment_completed',
                'meta' => [
                    'provider' => 'selcom',
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'invoice_id' => $payment->invoice_id,
                ],
            ]);

            return response()->json(['status' => 'success']);
        }

        if ($isFailed) {
            $payment->update(['status' => 'failed']);

            BillingAuditLog::create([
                'tenant_id' => $payment->tenant_id,
                'user_id' => null,
                'action' => 'payment_failed',
                'meta' => [
                    'provider' => 'selcom',
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'payload' => $payload,
                ],
            ]);

            return response()->json(['status' => 'failed']);
        }

        // Otherwise keep pending
        return response()->json(['status' => 'pending']);
    }

    /**
     * Generate a general invoice (admin action).
     */
    public function generateGeneralInvoice(Request $request)
    {
        $tenantId = $this->tenantIdOrAbort();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'description' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenantId,
            'number' => 'INV-' . strtoupper(uniqid()),
            'amount' => (int) $validated['amount'],
            'currency' => 'TZS',
            'status' => 'pending',
            'due_date' => $validated['due_date'] ?? now()->addDays(7),
        ]);

        // Auto-link a pending Selcom payment for this invoice
        Payment::create([
            'tenant_id' => $tenantId,
            'invoice_id' => $invoice->id,
            'provider' => 'selcom',
            'reference' => 'PAY-' . strtoupper(uniqid()),
            'amount' => $invoice->remaining_balance,
            'status' => 'pending',
        ]);

        return back()->with('success', 'General invoice generated: ' . ($invoice->number ?? $invoice->id));
    }

    /**
     * Suspend tenant for non-payments (admin action).
     */
    public function suspendForNonPayment(Request $request)
    {
        $tenantId = $this->tenantIdOrAbort();

        $overdueCount = Invoice::where('tenant_id', $tenantId)->overdue()->count();
        if ($overdueCount <= 0) {
            return back()->with('warning', 'No overdue invoices. Suspension not applied.');
        }

        $tenant = \App\Models\Tenant::find($tenantId);
        if (!$tenant) {
            return back()->with('error', 'Tenant not found.');
        }
        $tenant->update(['status' => 'suspended']);

        return back()->with('success', 'Tenant suspended due to non-payment.');
    }

    /**
     * Credit goodwill on the latest pending invoice (admin action).
     */
    public function creditGoodwill(Request $request)
    {
        $tenantId = $this->tenantIdOrAbort();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:100',
            'note' => 'nullable|string|max:255',
        ]);

        $invoice = Invoice::where('tenant_id', $tenantId)->pending()->latest()->first();
        if (!$invoice) {
            return back()->with('error', 'No pending invoice found to credit.');
        }

        $payment = Payment::create([
            'tenant_id' => $tenantId,
            'invoice_id' => $invoice->id,
            'provider' => 'manual_adjustment',
            'reference' => 'GOODWILL-' . strtoupper(uniqid()),
            'amount' => (int) $validated['amount'],
            'status' => 'success',
            'paid_at' => now(),
        ]);

        // Update invoice if fully paid
        if ($invoice->fresh()->remaining_balance <= 0) {
            $invoice->update(['status' => 'paid']);
        }

        return back()->with('success', 'Goodwill credit applied via payment ' . $payment->reference);
    }

    /**
     * Upgrade subscription (route alias for changePlan).
     */
    public function upgradeSubscription(Request $request)
    {
        return $this->changePlan($request);
    }

    /**
     * Show payment methods management page.
     */
    public function paymentMethods()
    {
        return view('billing.payment-methods');
    }

    /**
     * Add a payment method (placeholder).
     */
    public function addPaymentMethod(Request $request)
    {
        // In a real implementation, tokenize and save method via provider SDK
        return back()->with('success', 'Payment method added.');
    }

    /**
     * Remove a payment method (placeholder).
     */
    public function removePaymentMethod($method)
    {
        // In a real implementation, delete method via provider SDK/storage
        return back()->with('success', 'Payment method removed.');
    }

    /**
     * Get billing statistics.
     */
    public function statistics()
    {
        $tenantId = $this->tenantIdOrAbort();
        
        $stats = [
            'monthly_revenue' => Payment::where('tenant_id', $tenantId)
                                       ->completed()
                                       ->whereMonth('created_at', now()->month)
                                       ->sum('amount'),
            'total_revenue' => Payment::where('tenant_id', $tenantId)
                                     ->completed()
                                     ->sum('amount'),
            'pending_amount' => Invoice::where('tenant_id', $tenantId)
                                      ->pending()
                                      ->sum('amount'),
            'overdue_amount' => Invoice::where('tenant_id', $tenantId)
                                      ->overdue()
                                      ->sum('amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Display subscription expired page for trial users.
     */
    public function subscriptionExpired()
    {
        $tenantId = $this->tenantIdOrAbort();
        
        // Get all available plans
        $plans = Plan::active()->orderBy('price')->get();
        
        // Get current tenant information
        $tenant = \App\Models\Tenant::find($tenantId);
        
        return view('billing.subscription-expired', compact('plans', 'tenant'));
    }

    /**
     * Display subscription details.
     */
    public function subscription()
    {
        $tenantId = $this->tenantIdOrAbort();
        
        $subscription = Subscription::where('tenant_id', $tenantId)
                                   ->with('plan')
                                   ->first();

        $plans = Plan::where('is_active', true)
            ->whereIn('code', ['starter','growth','enterprise'])
            ->get();

        $branches = cache()->get('setting_branches', []);
        if (is_string($branches)) {
            $decoded = json_decode($branches, true);
            $branches = is_array($decoded) ? $decoded : [];
        }
        $branch_count = max(1, (is_array($branches) ? count($branches) : 0));

        return view('billing.subscription', compact('subscription', 'plans', 'branch_count'));
    }
}