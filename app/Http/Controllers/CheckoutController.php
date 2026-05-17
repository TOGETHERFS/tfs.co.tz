<?php

namespace App\Http\Controllers;

use App\Support\Pricing;
use App\Models\Tenant;
use App\Services\SelcomPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    protected $selcomService;

    public function __construct(SelcomPaymentService $selcomService)
    {
        $this->selcomService = $selcomService;
    }

    /**
     * Show checkout page for selected plan.
     */
    public function show(Request $request)
    {
        $planSlug = $request->query('plan');
        
        // Validate plan slug
        if (!$planSlug || !Pricing::isValidPlan($planSlug)) {
            return redirect()->route('pricing')->with('error', 'Invalid plan selected.');
        }
        
        // Get plan details
        $plan = Pricing::getPlan($planSlug);
        
        // Check if user is authenticated
        if (!Auth::check()) {
            // Store intended checkout URL and redirect to login
            session(['intended_checkout' => route('checkout.show', ['plan' => $planSlug])]);
            return redirect()->route('login')->with('info', 'Please login to continue with your subscription.');
        }
        
        // Get current tenant
        $tenant = $this->getCurrentTenant();
        if (!$tenant) {
            return redirect()->route('dashboard')->with('error', 'Tenant context not found.');
        }
        
        // Get current plan for comparison
        $currentPlan = $tenant->getCurrentPlan();
        
        // Check if this is an upgrade, downgrade, or same plan
        $planComparison = $this->comparePlans($currentPlan, $plan);
        
        // Calculate pricing
        $pricing = [
            'subtotal' => $plan['price'],
            'tax' => 0, // No tax for now
            'total' => $plan['price'],
            'currency' => 'TZS'
        ];
        
        // Prepare variables expected by checkout view
        $plans = array_values(Pricing::getPlans());
        $selectedPlan = $plan;
        $nextBilling = Pricing::calculateNextRenewal($planSlug);
        
        return view('checkout', compact('plans', 'selectedPlan', 'currentPlan', 'planComparison', 'pricing', 'tenant', 'nextBilling'));
    }

    /**
     * Process payment for selected plan.
     */
    public function process(Request $request)
    {
        $request->validate([
            'plan_slug' => 'required|string',
            'payment_method' => 'required|in:selcom',
        ]);

        $planSlug = $request->plan_slug;
        
        // Validate plan
        if (!Pricing::isValidPlan($planSlug)) {
            return back()->with('error', 'Invalid plan selected.');
        }
        
        $plan = Pricing::getPlan($planSlug);
        $tenant = $this->getCurrentTenant();
        
        if (!$tenant) {
            return back()->with('error', 'Tenant context not found.');
        }

        try {
            DB::beginTransaction();
            
            // Create payment request with Selcom
            $paymentData = [
                'amount' => $plan['price'],
                'currency' => 'TZS',
                'description' => "Subscription to {$plan['name']} plan",
                'reference' => 'PLAN_' . strtoupper($planSlug) . '_' . $tenant->id . '_' . time(),
                // Ensure the service uses our checkout callback
                'callback_url' => route('checkout.callback'),
                'success_url' => route('checkout.success'),
                'cancel_url' => route('checkout.show', ['plan' => $planSlug]),
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'plan_slug' => $planSlug,
                    'user_id' => Auth::id(),
                ]
            ];
            
            // Initialize payment with Selcom using the supported method
            $paymentResponse = $this->selcomService->initiate($paymentData);
            
            if ($paymentResponse && isset($paymentResponse['result']) && $paymentResponse['result'] === 'SUCCESS') {
                // Store payment details in session for callback processing
                session([
                    'pending_payment' => [
                        'tenant_id' => $tenant->id,
                        'plan_slug' => $planSlug,
                        'amount' => $plan['price'],
                        'reference' => $paymentData['reference'],
                        'payment_id' => $paymentResponse['payment_id'] ?? null,
                    ]
                ]);
                
                DB::commit();
                
                // Redirect to Selcom payment page if provided; else show success info
                if (isset($paymentResponse['payment_url'])) {
                    return redirect($paymentResponse['payment_url']);
                }
                
                return back()->with('success', 'Selcom payment initiated. Please confirm the prompt on your phone.');
            } else {
                throw new \Exception($paymentResponse['message'] ?? 'Failed to initialize payment with Selcom');
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout payment processing failed', [
                'tenant_id' => $tenant->id,
                'plan_slug' => $planSlug,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Payment processing failed. Please try again.');
        }
    }

    /**
     * Handle payment callback from Selcom.
     */
    public function callback(Request $request)
    {
        try {
            // Verify callback signature
            $isValid = $this->selcomService->verifyCallback($request->all());
            
            if ($isValid) {
                $result = $request->input('result');
                $status = strtolower($request->input('status', ''));
                
                if (($result && $result === 'SUCCESS') || in_array($status, ['success', 'completed', 'paid'])) {
                    $pendingPayment = session('pending_payment');
                    
                    if ($pendingPayment) {
                        $tenant = Tenant::find($pendingPayment['tenant_id']);
                        
                        if ($tenant) {
                            // Update tenant plan
                            $tenant->updatePlan($pendingPayment['plan_slug']);
                            
                            // Send success notifications
                            $this->sendPaymentSuccessNotifications($tenant, $pendingPayment);
                            
                            // Store transaction ID for success page
                            session(['transaction_id' => $request->input('transaction_id', 'N/A')]);
                            
                            // Clear pending payment from session
                            session()->forget('pending_payment');
                            
                            return redirect()->route('checkout.success')->with('success', 'Payment successful! Your plan has been updated.');
                        }
                    }
                }
            }
            
            return redirect()->route('checkout.show', ['plan' => session('pending_payment.plan_slug', 'starter')])
                ->with('error', 'Payment verification failed. Please try again.');
                
        } catch (\Exception $e) {
            Log::error('Payment callback processing failed', [
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('pricing')->with('error', 'Payment processing failed. Please contact support.');
        }
    }

    /**
     * Show payment success page.
     */
    public function success()
    {
        $tenant = $this->getCurrentTenant();
        
        if (!$tenant) {
            return redirect()->route('pricing')->with('error', 'Session expired. Please try again.');
        }
        
        $plan = $tenant->getCurrentPlan();
        $nextBilling = $tenant->plan_renews_at;
        $transactionId = session('transaction_id'); // Store this during payment processing
        
        return view('checkout.success', compact('plan', 'nextBilling', 'transactionId'));
    }

    /**
     * Get current tenant.
     */
    private function getCurrentTenant()
    {
        if (Auth::check()) {
            return Auth::user()->tenant;
        }
        
        return null;
    }

    /**
     * Compare two plans.
     */
    private function comparePlans($currentPlan, $newPlan)
    {
        if (!$currentPlan) {
            return 'new';
        }
        
        if ($currentPlan['price'] < $newPlan['price']) {
            return 'upgrade';
        } elseif ($currentPlan['price'] > $newPlan['price']) {
            return 'downgrade';
        } else {
            return 'same';
        }
    }

    /**
     * Send payment success notifications.
     */
    private function sendPaymentSuccessNotifications($tenant, $paymentData)
    {
        try {
            // Send SMS notification
            $smsMessage = "Payment successful! Your {$tenant->getCurrentPlan()['name']} plan is now active. Thank you for choosing our service.";
            
            // Send email notification
            $emailData = [
                'tenant_name' => $tenant->name,
                'plan_name' => $tenant->getCurrentPlan()['name'],
                'amount' => number_format($paymentData['amount']),
                'renewal_date' => $tenant->plan_renews_at ? $tenant->plan_renews_at->format('M d, Y') : 'N/A',
            ];
            
            // TODO: Implement actual SMS and email sending
            Log::info('Payment success notifications sent', [
                'tenant_id' => $tenant->id,
                'plan_slug' => $paymentData['plan_slug'],
                'amount' => $paymentData['amount']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send payment success notifications', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}