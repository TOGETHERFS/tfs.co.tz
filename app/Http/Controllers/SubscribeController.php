<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\SelcomGateway;
use Illuminate\Http\Request;

class SubscribeController extends Controller
{
    private function tenantOrAbort(): Tenant
    {
        $tenant = session('tenant');
        if ($tenant instanceof Tenant) {
            return $tenant;
        }
        $tenantId = session('tenant_id') ?? (auth()->user() ? auth()->user()->tenant_id : null);
        if ($tenantId) {
            $t = Tenant::find($tenantId);
            if ($t) {
                session(['tenant' => $t, 'tenant_id' => $t->id]);
                return $t;
            }
        }
        abort(403, 'Tenant context not resolved');
    }

    /**
     * Confirm screen for plan selection.
     */
    public function show(Plan $plan)
    {
        return view('billing.subscribe', compact('plan'));
    }

    /**
     * Create payment intent for a plan.
     */
    public function intent(Request $request, string $planCode, SelcomGateway $selcom)
    {
        $tenant = $this->tenantOrAbort();
        $plan = Plan::where('code', $planCode)->firstOrFail();

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'currency' => 'TZS',
            'status' => 'pending',
            'due_date' => now()->addDays(7),
        ]);

        $intent = $selcom->createPaymentIntent($tenant, $invoice);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount,
            'currency' => $invoice->currency,
            'provider' => 'selcom',
            'reference' => $intent['reference'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'intent' => $intent,
            'payment' => [
                'reference' => $payment->reference,
                'amount' => $payment->amount,
                'currency' => $invoice->currency,
            ],
        ], 201);
    }

    /**
     * Redirect to Selcom hosted payment page.
     */
    public function redirectToPayment(Request $request, Plan $plan)
    {
        $tenant = $this->tenantOrAbort();

        // Validate months, staff count, and branch count input
        $months = max(1, min(12, (int) $request->input('months', 1)));
        $staffCount = max(1, min(100, (int) $request->input('staff_count', 1)));
        $branchCount = max(1, min(50, (int) $request->input('branch_count', 1)));
        
        // Calculate extra staff cost
        $includedStaff = $plan->staff_limit ?? 1;
        $extraStaff = max(0, $staffCount - $includedStaff);
        $pricePerExtraStaff = $plan->price_per_staff ?? 0;
        $extraStaffCost = $extraStaff * $pricePerExtraStaff;
        
        // Calculate extra branch cost
        $includedBranches = $plan->branch_limit ?? 1;
        $extraBranches = max(0, $branchCount - $includedBranches);
        $pricePerExtraBranch = $plan->price_per_branch ?? 0;
        $extraBranchCost = $extraBranches * $pricePerExtraBranch;
        
        // Calculate total: base price + extra staff cost + extra branch cost * months
        $monthlyTotal = $plan->price + $extraStaffCost + $extraBranchCost;
        $totalAmount = $monthlyTotal * $months;

        // Check if Selcom is properly configured
        $selcomService = new \App\Services\SelcomPaymentService();
        $configCheck = $selcomService->checkConfiguration();
        
        if (!$configCheck['configured']) {
            \Log::error('Selcom payment gateway not configured', [
                'errors' => $configCheck['errors'],
                'plan_code' => $plan->code,
                'tenant_id' => $tenant->id,
            ]);
            
            return back()->with('error', 'Payment service is not properly configured. Please contact support.');
        }

        // Create invoice
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'number' => $this->generateInvoiceNumber($tenant->id),
            'amount' => $totalAmount,
            'currency' => 'TZS',
            'status' => 'pending',
            'due_date' => now()->addDays(7),
            'months' => $months,
            'staff_count' => $staffCount,
        ]);

        // Create payment record
        $reference = 'SUB_' . strtoupper($plan->code) . '_' . $tenant->id . '_' . time();
        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'amount' => $totalAmount,
            'currency' => $invoice->currency,
            'provider' => 'selcom',
            'reference' => $reference,
            'status' => 'pending',
        ]);

        // Prepare payment data for Selcom (reuse existing $selcomService instance)
        $monthsLabel = $months > 1 ? "{$months} months" : "1 month";
        $staffLabel = $staffCount > 1 ? "{$staffCount} staff" : "1 staff";
        $paymentData = [
            'order_id' => $reference,
            'amount' => $totalAmount,
            'description' => "Subscription to {$plan->name} plan ({$staffLabel}, {$monthsLabel})",
            'email' => auth()->user()->email ?? $tenant->contact_email ?? '',
            'buyer_name' => auth()->user()->name ?? $tenant->name ?? 'Customer',
            'phone_number' => $tenant->phone ?? '255700000000',
            'callback_url' => route('webhooks.selcom'),
            'success_url' => route('subscribe.success', ['reference' => $reference]),
            'cancel_url' => route('subscribe.show', ['plan' => $plan->code]),
        ];

        try {
            // Create order with Selcom
            $response = $selcomService->createHostedPayment($paymentData);

            // If connection failed (cURL error), response will have result=FAIL with connection error message
            if (isset($response['result']) && $response['result'] === 'FAIL' && isset($response['error'])) {
                \Log::error('Selcom connection failed for subscription', [
                    'error' => $response['error'],
                    'plan_code' => $plan->code,
                    'tenant_id' => $tenant->id,
                ]);
                return back()->with('error', 'Payment gateway is currently unreachable. Please try again later or contact support.');
            }

            \Log::info('Selcom createHostedPayment response', ['response' => $response]);
            
            if (isset($response['result']) && $response['result'] === 'SUCCESS') {
                // Store payment details in session for callback processing
                session([
                    'pending_subscription_payment' => [
                        'tenant_id' => $tenant->id,
                        'plan_code' => $plan->code,
                        'payment_id' => $payment->id,
                        'reference' => $reference,
                        'order_id' => $response['data'][0]['order_id'] ?? $reference,
                        'months' => $months,
                        'staff_count' => $staffCount,
                    ]
                ]);

                // Check if we have a payment URL
                if (isset($response['payment_url'])) {
                    return redirect($response['payment_url']);
                }
                
                // If no payment URL, show success with instructions
                return redirect()->route('billing.subscription')
                    ->with('success', 'Order created successfully. Please complete payment using reference: ' . $reference);
            } else {
                $errorMessage = $response['message'] ?? 'Failed to create payment session';
                \Log::error('Selcom order creation failed', [
                    'response' => $response,
                    'plan_code' => $plan->code,
                ]);
                return back()->with('error', $errorMessage . '. Please try again.');
            }
        } catch (\Exception $e) {
            \Log::error('Selcom hosted payment creation failed', [
                'plan_code' => $plan->code,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Payment service is currently unavailable. Please try again later.');
        }
    }

    /**
     * Execute Selcom mobile money/card payment.
     */
    public function pay(Request $request, SelcomGateway $selcom)
    {
        $tenant = $this->tenantOrAbort();
        $validated = $request->validate([
            'reference' => 'required|string',
            'channel' => 'nullable|string',
            'msisdn' => 'nullable|string',
            'card_number' => 'nullable|string',
        ]);

        $payment = Payment::where('tenant_id', $tenant->id)
            ->where('provider', 'selcom')
            ->where('reference', $validated['reference'])
            ->firstOrFail();

        $result = $selcom->chargeMobileMoney(
            $validated['msisdn'] ?? '',
            (int) $payment->amount,
            $payment->reference
        );

        $payment->update(['payload' => $result, 'status' => 'pending']);

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated. Awaiting Selcom confirmation.',
            'data' => [
                'reference' => $payment->reference,
                'invoice_id' => $payment->invoice_id,
                'status' => $payment->status,
            ]
        ]);
    }

    /**
     * Handle successful payment redirect from Selcom.
     */
    public function success(Request $request)
    {
        $reference = $request->get('reference');
        $tenant = $this->tenantOrAbort();
        
        if (!$reference) {
            return redirect()->route('user.dashboard')->with('error', 'Invalid payment reference.');
        }

        $payment = Payment::where('reference', $reference)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$payment) {
            return redirect()->route('user.dashboard')->with('error', 'Payment not found.');
        }

        // Check if payment is already completed
        if ($payment->status === 'completed') {
            return redirect()->route('user.dashboard')->with('success', 'Payment completed successfully! Your subscription is now active.');
        }

        // Payment is still pending - show waiting page
        return view('billing.payment-success', [
            'payment' => $payment,
            'message' => 'Payment is being processed. Your subscription will be activated shortly.'
        ]);
    }

    /**
     * Handle cancelled payment redirect from Selcom.
     */
    public function cancel(Request $request)
    {
        $reference = $request->get('reference');
        
        if ($reference) {
            $tenant = $this->tenantOrAbort();
            $payment = Payment::where('reference', $reference)
                ->where('tenant_id', $tenant->id)
                ->first();
                
            if ($payment && $payment->status === 'pending') {
                $payment->update(['status' => 'cancelled']);
            }
        }

        return redirect()->route('user.dashboard')->with('warning', 'Payment was cancelled. You can try again anytime.');
    }

    /**
     * List invoices for current tenant (simple view).
     */
    public function invoices()
    {
        $tenant = $this->tenantOrAbort();
        $invoices = \App\Models\Invoice::where('tenant_id', $tenant->id)->latest()->paginate(10);
        return view('billing.invoices', compact('invoices'));
    }

    /**
     * Download invoice PDF (placeholder HTML response).
     */
    public function invoicePdf(\App\Models\Invoice $invoice)
    {
        $tenant = $this->tenantOrAbort();
        if ($invoice->tenant_id !== $tenant->id) {
            abort(403);
        }
        return response()->view('billing.invoice-pdf', ['invoice' => $invoice]);
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(int $tenantId): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $time = now()->format('His');
        $random = strtoupper(substr(uniqid(), -4));
        
        // Format: INV-YYYYMMDD-HHMMSS-XXXX (guaranteed unique with timestamp + random)
        return sprintf('%s-%s-%s-%s', $prefix, $date, $time, $random);
    }

    /**
     * Process wallet payment with USSD push (Selcom)
     */
    public function processWalletPayment(Request $request)
    {
        $tenant = $this->tenantOrAbort();
        
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'phone_number' => 'required|string|min:9|max:15',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $selcomService = new \App\Services\SelcomPaymentService();

        // Check if Selcom is properly configured
        $configCheck = $selcomService->checkConfiguration();
        if (!$configCheck['configured']) {
            \Log::error('Selcom payment gateway not configured for wallet payment', [
                'errors' => $configCheck['errors'],
                'plan_id' => $plan->id,
                'tenant_id' => $tenant->id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Payment service is not properly configured. Please contact support.',
            ], 503);
        }

        // Generate unique order ID
        $orderId = 'SUB_' . strtoupper($plan->code) . '_' . $tenant->id . '_' . time();

        try {
            // Step 1: Create invoice
            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'number' => $this->generateInvoiceNumber($tenant->id),
                'amount' => $plan->price,
                'currency' => 'TZS',
                'status' => 'pending',
                'due_date' => now()->addDays(7),
            ]);

            // Step 2: Create payment record
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'amount' => $plan->price,
                'currency' => 'TZS',
                'provider' => 'selcom',
                'reference' => $orderId,
                'status' => 'pending',
            ]);

            // Step 3: Create order with Selcom
            $orderData = [
                'order_id' => $orderId,
                'amount' => $plan->price,
                'description' => "Subscription to {$plan->name} plan",
                'email' => auth()->user()->email ?? $tenant->contact_email ?? '',
                'buyer_name' => auth()->user()->name ?? $tenant->name ?? 'Customer',
                'phone_number' => $validated['phone_number'],
                'callback_url' => route('webhooks.selcom'),
                'success_url' => route('billing.subscription'),
                'cancel_url' => route('billing.subscription'),
            ];

            $orderResponse = $selcomService->createOrder($orderData);
            
            \Log::info('Selcom createOrder response', [
                'response' => $orderResponse,
                'order_id' => $orderId,
                'amount' => $plan->price,
            ]);

            if (!isset($orderResponse['result']) || $orderResponse['result'] !== 'SUCCESS') {
                $payment->update(['status' => 'failed']);
                
                // Extract detailed error message from Selcom response
                $errorMessage = 'Failed to create order with payment provider';
                if (isset($orderResponse['message'])) {
                    $errorMessage = $orderResponse['message'];
                } elseif (isset($orderResponse['error'])) {
                    $errorMessage = $orderResponse['error'];
                } elseif (isset($orderResponse['resultcode'])) {
                    $errorMessage = "Error code: " . $orderResponse['resultcode'];
                }
                
                \Log::error('Selcom createOrder failed', [
                    'order_id' => $orderId,
                    'response' => $orderResponse,
                    'error_message' => $errorMessage,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 400);
            }

            // Step 4: Trigger wallet payment (USSD push)
            $walletData = [
                'order_id' => $orderId,
                'phone_number' => $validated['phone_number'],
                'transaction_id' => 'TXN_' . time(),
            ];

            $walletResponse = $selcomService->processWalletPayment($walletData);
            
            \Log::info('Selcom wallet-payment response', ['response' => $walletResponse]);

            if (isset($walletResponse['result']) && in_array($walletResponse['result'], ['SUCCESS', 'PENDING'])) {
                // Update payment with transaction reference
                $payment->update([
                    'payload' => [
                        'order_response' => $orderResponse,
                        'wallet_response' => $walletResponse,
                    ],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment initiated. Please check your phone for USSD prompt.',
                    'order_id' => $orderId,
                    'reference' => $walletResponse['reference'] ?? $orderId,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $walletResponse['message'] ?? 'Failed to initiate mobile money payment. Please try again.',
                ], 400);
            }

        } catch (\Exception $e) {
            \Log::error('Selcom wallet payment failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment service error. Please try again later.',
            ], 500);
        }
    }

    /**
     * Check payment status by order ID
     */
    public function checkPaymentStatus(string $orderId)
    {
        $tenant = $this->tenantOrAbort();

        // Find payment by reference (order_id)
        $payment = Payment::where('tenant_id', $tenant->id)
                         ->where('reference', $orderId)
                         ->first();

        if (!$payment) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Payment not found',
            ], 404);
        }

        // If already marked as success/failed, return that
        if ($payment->status === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => 'Payment completed successfully',
            ]);
        }

        if ($payment->status === 'failed') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Payment failed',
            ]);
        }

        // Query Selcom for current status
        try {
            $selcomService = new \App\Services\SelcomPaymentService();
            $statusResponse = $selcomService->queryOrderStatus($orderId);
            
            \Log::info('Selcom order status query', [
                'order_id' => $orderId,
                'response' => $statusResponse
            ]);

            if (isset($statusResponse['result']) && $statusResponse['result'] === 'SUCCESS') {
                $paymentStatus = $statusResponse['data'][0]['payment_status'] ?? 'PENDING';
                
                if ($paymentStatus === 'COMPLETED') {
                    // Payment confirmed by Selcom - but we wait for webhook to actually update
                    // This is just for UI feedback
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Payment completed',
                    ]);
                } elseif (in_array($paymentStatus, ['FAILED', 'CANCELLED', 'EXPIRED'])) {
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Payment ' . strtolower($paymentStatus),
                    ]);
                }
            }

            return response()->json([
                'status' => 'pending',
                'message' => 'Payment is being processed',
            ]);

        } catch (\Exception $e) {
            \Log::error('Selcom status query failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'pending',
                'message' => 'Checking payment status...',
            ]);
        }
    }
}