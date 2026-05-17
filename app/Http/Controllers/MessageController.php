<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\SmsPurchase;
use App\Models\SmsLog;
use App\Models\Sms\SmsPackage;
use App\Services\BeemSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /**
     * Show list of sent messages (logs).
     */
    public function index()
    {
        $tenantId = auth()->user()->tenant_id ?? session('tenant_id');

        $logs = SmsLog::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(15);

        // Get SMS balance for this tenant
        $balance = \App\Models\Sms\SmsBalance::where('tenant_id', $tenantId)->first();
        
        // Get stats for this month
        $startOfMonth = now()->startOfMonth();
        $stats = [
            'delivered' => SmsLog::where('tenant_id', $tenantId)
                ->where('status', 'delivered')
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'sent' => SmsLog::where('tenant_id', $tenantId)
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'failed' => SmsLog::where('tenant_id', $tenantId)
                ->where('status', 'failed')
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
        ];

        return view('messages.index', compact('logs', 'balance', 'stats'));
    }

    /**
     * Show compose message form.
     */
    public function create()
    {
        $tenant = session('current_tenant');
        $tenantId = $tenant ? $tenant->id : null;

        $clients = Client::where('tenant_id', $tenantId)
            ->whereNotNull('phone')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'phone']);

        // Get approved sender IDs for this tenant
        $senderIds = \App\Models\Sms\SmsSenderIdRequest::where('tenant_id', $tenantId)
            ->whereIn('status', ['approved', 'live'])
            ->get();

        return view('messages.create', compact('clients', 'senderIds'));
    }

    /**
     * Send message via Beem and record log.
     */
    public function store(Request $request, BeemSmsService $smsService)
    {
        $tenantFromSession = session('current_tenant');
        $tenantId = session('tenant_id') ?? ($tenantFromSession ? $tenantFromSession->id : null);

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'sender_id' => 'nullable|string|max:11',
            'recipients' => 'array',
            'recipients.*' => 'exists:clients,id',
            'manual_numbers' => 'nullable|string', // comma-separated
        ]);

        $recipients = [];

        // From selected clients
        if (!empty($validated['recipients'])) {
            $clients = Client::whereIn('id', $validated['recipients'])
                ->where('tenant_id', $tenantId)
                ->get();
            foreach ($clients as $client) {
                if ($client->phone) {
                    $recipients[] = $client->phone;
                }
            }
        }

        // From manual numbers
        if (!empty($validated['manual_numbers'])) {
            $manuals = preg_split('/[,\n]+/', $validated['manual_numbers']);
            foreach ($manuals as $num) {
                $num = trim($num);
                if ($num !== '') {
                    $recipients[] = $num;
                }
            }
        }

        $recipients = array_values(array_unique($recipients));

        if (count($recipients) === 0) {
            return back()
                ->withInput()
                ->withErrors(['recipients' => 'Please select borrowers or enter phone numbers.']);
        }

        // Calculate SMS segments needed and enforce tenant controls & credits
        $segmentsPerRecipient = $smsService->calculateSmsCount($validated['message']);
        $segmentsRequired = count($recipients) * $segmentsPerRecipient;

        $tenantModel = $tenantId ? Tenant::find($tenantId) : null;

        // Enforce admin messaging control (if disabled, non-admins cannot send)
        if ($tenantModel && $tenantModel->messaging_enabled === false && !auth()->user()->isAdmin()) {
            return back()
                ->withInput()
                ->withErrors(['message' => 'Messaging is disabled by admin for this tenant.']);
        }

        // Enforce available credits
        if ($tenantModel) {
            $availableCredits = $tenantModel->sms_credits ?? 0;
            if ($availableCredits < $segmentsRequired) {
                return back()
                    ->withInput()
                    ->withErrors(['message' => 'Insufficient SMS credits. Please buy more SMS credits.']);
            }
        }

        // Send via Beem
        $result = $smsService->sendBulkSms($recipients, $validated['message'], $validated['sender_id'] ?? null);

        // Record log
        $log = new SmsLog([
            'tenant_id' => $tenantId,
            'user_id' => auth()->id(),
            'sender_id' => $validated['sender_id'] ?? null,
            'message' => $validated['message'],
            'recipients' => $recipients,
            'status' => $result['success'] ? 'sent' : 'failed',
            'provider_request_id' => $result['success'] ? ($result['data']['request_id'] ?? null) : null,
            'provider_response' => $result['success'] ? ($result['data'] ?? null) : null,
            'error' => $result['success'] ? null : ($result['error'] ?? 'Unknown error'),
            'sent_at' => now(),
        ]);
        $log->save();

        if ($result['success']) {
            // Deduct credits if we have a tenant context
            if ($tenantModel) {
                $tenantModel->decrement('sms_credits', $segmentsRequired);
            }
            return redirect()->route('messages.index')
                ->with('status', 'SMS sent successfully to ' . count($recipients) . ' recipient(s).');
        }

        Log::warning('Beem SMS send failed', ['result' => $result]);
        return back()->withInput()->withErrors(['message' => 'Failed to send SMS: ' . ($result['error'] ?? 'Unknown error')]);
    }

    /**
     * Show SMS account balance.
     */
    public function balance(BeemSmsService $smsService)
    {
        $result = $smsService->getBalance();
        $balance = $result['success'] ? ($result['data'] ?? []) : null;
        $error = $result['success'] ? null : ($result['error'] ?? 'Unable to fetch balance');

        return view('messages.balance', compact('balance', 'error'));
    }

    /**
     * Show purchase SMS information.
     */
    public function purchase()
    {
        return view('messages.purchase');
    }

    /**
     * Show Buy SMS page for users (buy from admin/phidtech).
     */
    public function buy()
    {
        $tenant = session('current_tenant') ?? Tenant::find(session('tenant_id'));
        $unitPrice = (int) (config('services.sms.unit_price', env('SMS_UNIT_PRICE', 40)));
        $packages = SmsPackage::active()->ordered()->get();
        $purchases = SmsPurchase::where('tenant_id', $tenant->id ?? session('tenant_id'))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        
        return view('messages.buy', compact('unitPrice', 'tenant', 'packages', 'purchases'));
    }

    /**
     * Redirect to Selcom checkout for SMS purchase.
     */
    public function buyCheckout(Request $request)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:50',
            'amount' => 'required',
            'package_name' => 'nullable|string',
        ]);

        $tenant = session('current_tenant') ?? Tenant::find(session('tenant_id'));
        $tenantId = $tenant->id ?? session('tenant_id');
        $userId = auth()->id();
        
        // Parse amount (remove commas)
        $amount = (int) str_replace(',', '', $validated['amount']);
        $quantity = (int) $validated['quantity'];
        $unitPrice = $amount / $quantity;

        // Create SMS purchase record
        $purchase = SmsPurchase::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $amount,
            'status' => 'pending',
            'notes' => $validated['package_name'] ?? 'Custom',
        ]);

        // Generate unique reference for Selcom
        $reference = 'SMS_' . $tenantId . '_' . $purchase->id . '_' . time();
        $purchase->update(['payment_reference' => $reference]);

        // Try Selcom hosted payment if configured; otherwise fall back to manual request
        $selcomService = new \App\Services\SelcomPaymentService();
        $configCheck = $selcomService->checkConfiguration();

        if ($configCheck['configured']) {
            $paymentData = [
                'order_id' => $reference,
                'amount' => $amount,
                'description' => "Purchase {$quantity} SMS Credits ({$validated['package_name']} Package)",
                'email' => auth()->user()->email ?? $tenant->contact_email ?? '',
                'buyer_name' => auth()->user()->name ?? $tenant->name ?? 'Customer',
                'phone_number' => $tenant->phone ?? '255700000000',
                'callback_url' => route('webhooks.selcom'),
                'success_url' => route('messages.buy.success', ['reference' => $reference]),
                'cancel_url' => route('messages.buy'),
            ];

            try {
                $response = $selcomService->createHostedPayment($paymentData);

                if (isset($response['result']) && $response['result'] === 'SUCCESS') {
                    if (isset($response['payment_url'])) {
                        return redirect($response['payment_url']);
                    }
                    return redirect()->route('messages.buy')
                        ->with('status', 'Order created. Reference: ' . $reference);
                } else {
                    Log::warning('Selcom SMS purchase failed, falling back to manual request', [
                        'response' => $response,
                        'purchase_id' => $purchase->id,
                    ]);
                    // Fall through to manual request below
                }
            } catch (\Exception $e) {
                Log::warning('Selcom unavailable for SMS purchase, using manual request', [
                    'error' => $e->getMessage(),
                    'purchase_id' => $purchase->id,
                ]);
                // Fall through to manual request below
            }
        }

        // Selcom not configured or unavailable — keep purchase as pending for admin approval
        return redirect()->route('messages.buy')
            ->with('status', "Your request to purchase {$quantity} SMS credits (TZS " . number_format($amount) . ") has been submitted. Credits will be added once payment is confirmed by our team. Reference: {$reference}");
    }

    /**
     * Handle successful SMS purchase payment.
     */
    public function buySuccess(Request $request)
    {
        $reference = $request->get('reference');
        $tenant = session('current_tenant') ?? Tenant::find(session('tenant_id'));
        
        $purchase = SmsPurchase::where('payment_reference', $reference)
            ->where('tenant_id', $tenant->id ?? session('tenant_id'))
            ->first();

        if ($purchase && $purchase->status === 'pending') {
            // Mark as approved and credit the tenant
            $purchase->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
            
            // Credit SmsBalance table (used by SMS sending system)
            $smsBalance = \App\Models\Sms\SmsBalance::getOrCreateForTenant($purchase->tenant_id);
            $smsBalance->credit($purchase->quantity);
            
            // Also update tenant table for backward compatibility
            Tenant::where('id', $purchase->tenant_id)->increment('sms_credits', $purchase->quantity);
            
            return redirect()->route('messages.buy')
                ->with('status', "Successfully purchased {$purchase->quantity} SMS credits!");
        }

        return redirect()->route('messages.buy')
            ->with('status', 'Payment is being processed. Credits will be added shortly.');
    }

    // ...
    /**
     * Handle Buy SMS form submission (legacy - manual approval).
     */
    public function buyStore(Request $request)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:10',
            'notes' => 'nullable|string|max:500',
        ]);

        $tenantId = session('tenant_id');
        $userId = auth()->id();
        $unitPrice = (int) (config('services.sms.unit_price', env('SMS_UNIT_PRICE', 40)));
        $totalAmount = $unitPrice * $validated['quantity'];

        $purchase = SmsPurchase::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'quantity' => $validated['quantity'],
            'unit_price' => $unitPrice,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('messages.index')
            ->with('status', 'Your request to buy ' . $purchase->quantity . ' SMS has been submitted for approval.');
    }

    /**
     * Admin view (superadmin): list and approve/reject purchases.
     */
    public function purchasesIndex()
    {
        $purchases = SmsPurchase::orderByDesc('created_at')->paginate(20);
        return view('messages.purchases.index', compact('purchases'));
    }

    /**
     * Approve a purchase and credit the tenant.
     */
    public function purchasesApprove($id)
    {
        $purchase = SmsPurchase::findOrFail($id);
        if ($purchase->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending purchases can be approved.']);
        }

        $purchase->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Credit SmsBalance table (used by SMS sending system)
        $smsBalance = \App\Models\Sms\SmsBalance::getOrCreateForTenant($purchase->tenant_id);
        $smsBalance->credit($purchase->quantity);

        // Also update tenant table for backward compatibility
        Tenant::where('id', $purchase->tenant_id)->increment('sms_credits', $purchase->quantity);

        return back()->with('status', 'Purchase approved and credits added.');
    }

    /**
     * Reject a purchase.
     */
    public function purchasesReject($id)
    {
        $purchase = SmsPurchase::findOrFail($id);
        if ($purchase->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending purchases can be rejected.']);
        }

        $purchase->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Purchase rejected.');
    }

    /**
     * Show sender ID request information.
     */
    public function senderIdRequest()
    {
        return view('messages.sender-id-request');
    }
}