<?php

namespace App\Http\Controllers;

use App\Models\Sms\SmsPackage;
use App\Models\Sms\SmsBalance;
use App\Models\Sms\SmsSenderId;
use App\Models\Sms\SmsSenderIdRequest;
use App\Models\Sms\SmsTransaction;
use App\Models\Sms\SmsMessage;
use App\Models\Sms\SmsTemplate;
use App\Models\Sms\SmsPurchaseRequest;
use App\Models\Client;
use App\Models\Tenant;
use App\Services\Sms\SmsService;
use App\Services\SelcomSmsTopupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SmsController extends Controller
{
    protected SmsService $smsService;
    protected SelcomSmsTopupService $selcomService;

    public function __construct(SmsService $smsService, SelcomSmsTopupService $selcomService)
    {
        $this->smsService = $smsService;
        $this->selcomService = $selcomService;
    }

    protected function initService(): void
    {
        $this->smsService->setContext(
            session('tenant_id'),
            Auth::id()
        );
    }

    public function index()
    {
        $this->initService();
        
        $balance = $this->smsService->getBalance();
        $stats = $this->smsService->getUsageStats('month');
        $recentMessages = SmsMessage::forTenant(session('tenant_id'))
            ->with(['client', 'user'])
            ->latest()
            ->take(10)
            ->get();

        $senderIds = SmsSenderId::forTenant(session('tenant_id'))->active()->get();
        
        // Also get approved sender ID requests
        $approvedRequests = SmsSenderIdRequest::where('tenant_id', session('tenant_id'))
            ->whereIn('status', ['approved', 'live'])
            ->get();
        if ($approvedRequests->isNotEmpty()) {
            $senderIds = $senderIds->merge($approvedRequests);
        }

        // Debug: Get all balances for comparison
        $allBalances = \App\Models\Sms\SmsBalance::with('tenant')->get();

        return view('messages.index', compact('balance', 'stats', 'recentMessages', 'senderIds', 'allBalances'));
    }
    
    public function debugBalance()
    {
        $this->initService();
        $balance = $this->smsService->getBalance();
        $allBalances = \App\Models\Sms\SmsBalance::with('tenant')->get();
        
        return view('messages.debug-balance', compact('balance', 'allBalances'));
    }

    // === Send SMS ===
    public function compose()
    {
        $this->initService();
        
        $balance = $this->smsService->getBalance();
        
        // Get sender IDs from both SmsSenderId and approved SmsSenderIdRequest
        $senderIds = SmsSenderId::forTenant(session('tenant_id'))->active()->get();
        
        // Also get approved sender ID requests for this tenant
        $approvedRequests = SmsSenderIdRequest::where('tenant_id', session('tenant_id'))
            ->whereIn('status', ['approved', 'live'])
            ->get();
        
        // Merge both collections
        if ($approvedRequests->isNotEmpty()) {
            $senderIds = $senderIds->merge($approvedRequests);
        }
        
        $templates = SmsTemplate::forTenant(session('tenant_id'))->active()->get();
        
        $clients = Client::where('tenant_id', session('tenant_id'))
            ->whereNotNull('phone')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'phone']);

        return view('messages.compose', compact('balance', 'senderIds', 'templates', 'clients'));
    }

    public function send(Request $request)
    {
        $this->initService();

        // Custom validation - at least one recipient method must be provided
        $hasClientIds = $request->filled('client_ids') && is_array($request->client_ids) && count($request->client_ids) > 0;
        $hasPhone = $request->filled('phone') && trim($request->phone) !== '';
        
        if (!$hasClientIds && !$hasPhone) {
            return back()
                ->withInput()
                ->withErrors(['recipients' => 'Please select at least one borrower OR enter phone numbers manually.']);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:800',
            'sender_id' => 'nullable|string|max:11',
            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
            'phone' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $senderId = $request->sender_id ?: $this->smsService->getDefaultSenderId();
        
        if (!$senderId) {
            return back()->with('error', 'No sender ID available. Please request one first.');
        }

        // Collect all recipients
        $recipients = [];
        
        // Add selected clients
        if ($hasClientIds) {
            $clients = Client::where('tenant_id', session('tenant_id'))
                ->whereIn('id', $request->client_ids)
                ->whereNotNull('phone')
                ->get(['id', 'phone']);
            
            foreach ($clients as $client) {
                $recipients[] = [
                    'phone' => $client->phone,
                    'client_id' => $client->id,
                ];
            }
        }
        
        // Add manual phone numbers
        if ($hasPhone) {
            $manualNumbers = preg_split('/[\s,]+/', trim($request->phone));
            foreach ($manualNumbers as $number) {
                $number = trim($number);
                if (!empty($number)) {
                    $recipients[] = [
                        'phone' => $number,
                        'client_id' => null,
                    ];
                }
            }
        }
        
        if (empty($recipients)) {
            return back()
                ->withInput()
                ->withErrors(['recipients' => 'No valid recipients found. Please check phone numbers.']);
        }

        $result = $this->smsService->sendBulk(
            $recipients,
            $request->message,
            $senderId,
            ['type' => SmsMessage::TYPE_BULK]
        );

        if ($result['success']) {
            $totalSent = $result['sent'] ?? count($recipients);
            return redirect()->route('messages.index')->with('success', "SMS sent successfully to {$totalSent} recipient(s)");
        }
        return back()->withInput()->with('error', $result['error'] ?? 'Failed to send SMS');
    }

    public function quickSend(Request $request)
    {
        $this->initService();

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'message' => 'required|string|max:800',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $client = Client::find($request->client_id);
        
        if ($client->tenant_id !== session('tenant_id')) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $result = $this->smsService->sendToClient($client, $request->message);

        return response()->json($result);
    }

    // === Message History ===
    public function history(Request $request)
    {
        $this->initService();

        $messages = $this->smsService->getMessageHistory(50, [
            'status' => $request->status,
            'type' => $request->type,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'search' => $request->search,
        ]);

        $balance = $this->smsService->getBalance();

        return view('messages.history', compact('messages', 'balance'));
    }

    // === Sender ID Management ===
    public function senderIds()
    {
        $activeSenderIds = SmsSenderId::forTenant(session('tenant_id'))->get();
        $requests = SmsSenderIdRequest::where('tenant_id', session('tenant_id'))
            ->latest()
            ->get();

        return view('messages.sender-ids', compact('activeSenderIds', 'requests'));
    }

    public function requestSenderId()
    {
        return view('messages.request-sender-id');
    }

    public function storeSenderIdRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|string|max:11|alpha_num',
            'company_name' => 'required|string|max:100',
            'purpose' => 'required|string|max:500',
            'sample_message' => 'required|string|max:320',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check for existing pending request
        $existing = SmsSenderIdRequest::where('tenant_id', session('tenant_id'))
            ->where('sender_id', strtoupper($request->sender_id))
            ->whereIn('status', ['pending', 'approved', 'submitted_to_provider'])
            ->first();

        if ($existing) {
            return back()->with('error', 'A request for this Sender ID already exists');
        }

        SmsSenderIdRequest::create([
            'tenant_id' => session('tenant_id'),
            'requested_by' => Auth::id(),
            'sender_id' => strtoupper($request->sender_id),
            'company_name' => $request->company_name,
            'purpose' => $request->purpose,
            'sample_message' => $request->sample_message,
            'status' => SmsSenderIdRequest::STATUS_PENDING,
        ]);

        return redirect()->route('messages.sender-ids')->with('success', 'Sender ID request submitted successfully');
    }

    public function editSenderIdRequest($id)
    {
        $senderIdRequest = SmsSenderIdRequest::where('tenant_id', session('tenant_id'))
            ->where('id', $id)
            ->firstOrFail();

        if (!in_array($senderIdRequest->status, ['pending', 'rejected'])) {
            return redirect()->route('messages.sender-ids')->with('error', 'Only pending or rejected requests can be edited');
        }

        return view('messages.edit-sender-id', compact('senderIdRequest'));
    }

    public function updateSenderIdRequest(Request $request, $id)
    {
        $senderIdRequest = SmsSenderIdRequest::where('tenant_id', session('tenant_id'))
            ->where('id', $id)
            ->firstOrFail();

        if (!in_array($senderIdRequest->status, ['pending', 'rejected'])) {
            return redirect()->route('messages.sender-ids')->with('error', 'Only pending or rejected requests can be edited');
        }

        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|string|max:11|alpha_num',
            'company_name' => 'required|string|max:100',
            'purpose' => 'required|string|max:500',
            'sample_message' => 'required|string|max:320',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $senderIdRequest->update([
            'sender_id' => strtoupper($request->sender_id),
            'company_name' => $request->company_name,
            'purpose' => $request->purpose,
            'sample_message' => $request->sample_message,
            'status' => SmsSenderIdRequest::STATUS_PENDING,
            'admin_notes' => null,
        ]);

        return redirect()->route('messages.sender-ids')->with('success', 'Sender ID request updated successfully');
    }

    // === SMS Packages & Purchase ===
    public function packages()
    {
        $packages = SmsPackage::active()->ordered()->get();
        $balance = SmsBalance::getOrCreateForTenant(session('tenant_id'));
        $transactions = SmsTransaction::forTenant(session('tenant_id'))
            ->with('package')
            ->latest()
            ->take(20)
            ->get();

        return view('messages.packages', compact('packages', 'balance', 'transactions'));
    }

    public function purchasePackage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:sms_packages,id',
            'phone_number' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $package = SmsPackage::findOrFail($request->package_id);
        
        if (!$package->is_active) {
            return back()->with('error', 'This package is no longer available');
        }

        $tenantId = session('tenant_id');
        $tenant = Tenant::find($tenantId);

        // Create purchase request record
        $purchaseRequest = SmsPurchaseRequest::create([
            'tenant_id' => $tenantId,
            'user_id' => Auth::id(),
            'package_id' => $package->id,
            'sms_count' => $package->sms_count,
            'amount' => $package->price,
            'currency' => $package->currency ?? 'TZS',
            'status' => SmsPurchaseRequest::STATUS_PENDING,
            'selcom_order_id' => SmsPurchaseRequest::generateOrderId(),
        ]);

        // Create Selcom payment
        try {
            $result = $this->selcomService->createTopupPayment(
                $tenantId,
                $package->sms_count,
                (float) $package->price,
                $package->currency ?? 'TZS',
                $request->phone_number ?? $tenant->phone,
                $request->email ?? $tenant->contact_email
            );

            if ($result['success'] && isset($result['data']['payment_url'])) {
                // Update purchase request with Selcom reference
                $purchaseRequest->update([
                    'selcom_order_id' => $result['data']['internal_ref'],
                    'payment_reference' => $result['data']['selcom_ref'],
                ]);

                // Redirect to Selcom payment gateway
                return redirect($result['data']['payment_url']);
            } elseif ($result['success']) {
                // No payment URL, show our payment page
                return redirect()->route('messages.payment', $purchaseRequest->id);
            } else {
                $purchaseRequest->markAsFailed(['error' => $result['message']]);
                return back()->with('error', 'Failed to create payment: ' . $result['message']);
            }
        } catch (\Exception $e) {
            Log::error('SMS package purchase failed', [
                'tenant_id' => $tenantId,
                'package_id' => $package->id,
                'error' => $e->getMessage()
            ]);
            $purchaseRequest->markAsFailed(['error' => $e->getMessage()]);
            return back()->with('error', 'Payment processing failed. Please try again.');
        }
    }

    public function paymentPage(SmsPurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->tenant_id !== session('tenant_id')) {
            abort(403);
        }

        if (!$purchaseRequest->isPending()) {
            return redirect()->route('messages.packages')->with('error', 'This purchase has already been processed');
        }

        $package = $purchaseRequest->package;
        $tenant = Tenant::find(session('tenant_id'));

        return view('messages.payment', compact('purchaseRequest', 'package', 'tenant'));
    }

    public function paymentSuccess(SmsPurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->tenant_id !== session('tenant_id')) {
            abort(403);
        }

        $package = $purchaseRequest->package;
        
        return view('messages.payment-success', compact('purchaseRequest', 'package'));
    }

    public function paymentCancel(SmsPurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->tenant_id !== session('tenant_id')) {
            abort(403);
        }

        $purchaseRequest->update(['status' => SmsPurchaseRequest::STATUS_CANCELLED]);
        
        return redirect()->route('messages.packages')->with('error', 'Payment was cancelled');
    }

    // Selcom webhook callback
    public function paymentCallback(Request $request)
    {
        Log::info('SMS Purchase Webhook received', $request->all());

        $orderId = $request->order_id;
        $transactionId = $request->transid ?? $request->reference;
        $status = $request->payment_status;

        // Find purchase by selcom_order_id
        $purchaseRequest = SmsPurchaseRequest::where('selcom_order_id', $orderId)->first();

        if (!$purchaseRequest) {
            Log::warning('SMS Purchase not found for order', ['order_id' => $orderId]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        if (in_array(strtolower($status), ['completed', 'success'])) {
            // Avoid double processing
            if ($purchaseRequest->isCompleted()) {
                return response()->json(['success' => true, 'message' => 'Already processed']);
            }

            $purchaseRequest->markAsCompleted($transactionId, $request->all());

            // Credit SMS to tenant
            $balance = SmsBalance::getOrCreateForTenant($purchaseRequest->tenant_id);
            $balanceBefore = $balance->balance;
            $balance->credit($purchaseRequest->sms_count);

            // Record transaction
            SmsTransaction::recordPurchase(
                $purchaseRequest->tenant_id,
                $purchaseRequest->user_id,
                $purchaseRequest->package,
                $balanceBefore,
                $transactionId,
                'selcom'
            );

            Log::info('SMS Purchase completed', [
                'purchase_id' => $purchaseRequest->id,
                'tenant_id' => $purchaseRequest->tenant_id,
                'sms_count' => $purchaseRequest->sms_count,
                'new_balance' => $balance->balance
            ]);

            return response()->json(['success' => true]);
        } else {
            $purchaseRequest->markAsFailed($request->all());
            Log::info('SMS Purchase failed', [
                'purchase_id' => $purchaseRequest->id,
                'status' => $status
            ]);
            return response()->json(['success' => false]);
        }
    }

    // === Templates ===
    public function templates()
    {
        $templates = SmsTemplate::forTenant(session('tenant_id'))->get();
        $categories = SmsTemplate::getCategoryOptions();

        return view('messages.templates', compact('templates', 'categories'));
    }

    public function storeTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'category' => 'required|string',
            'content' => 'required|string|max:800',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        SmsTemplate::create([
            'tenant_id' => session('tenant_id'),
            'name' => $request->name,
            'category' => $request->category,
            'content' => $request->content,
            'is_system' => false,
            'is_active' => true,
        ]);

        return back()->with('success', 'Template created successfully');
    }

    public function deleteTemplate(SmsTemplate $template)
    {
        if ($template->tenant_id !== session('tenant_id') || $template->is_system) {
            abort(403);
        }

        $template->delete();
        return back()->with('success', 'Template deleted');
    }

    // === API Endpoints ===
    public function getBalance()
    {
        $balance = SmsBalance::getOrCreateForTenant(session('tenant_id'));
        return response()->json([
            'balance' => $balance->balance,
            'total_purchased' => $balance->total_purchased,
            'total_used' => $balance->total_used,
        ]);
    }

    public function getClients(Request $request)
    {
        $query = Client::where('tenant_id', session('tenant_id'))
            ->whereNotNull('phone');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $clients = $query->orderBy('first_name')
            ->take(50)
            ->get(['id', 'first_name', 'last_name', 'phone']);

        return response()->json($clients);
    }
}
