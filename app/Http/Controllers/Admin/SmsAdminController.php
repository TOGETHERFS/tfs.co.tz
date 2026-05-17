<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sms\SmsPackage;
use App\Models\Sms\SmsBalance;
use App\Models\Sms\SmsSenderId;
use App\Models\Sms\SmsSenderIdRequest;
use App\Models\Sms\SmsTransaction;
use App\Models\Sms\SmsMessage;
use App\Models\Sms\SmsProviderSetting;
use App\Models\Tenant;
use App\Services\Sms\BeemAfricaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SmsAdminController extends Controller
{
    protected BeemAfricaService $beemService;

    public function __construct(BeemAfricaService $beemService)
    {
        $this->beemService = $beemService;
    }

    public function dashboard()
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'total_sms_sold' => SmsTransaction::where('type', 'purchase')->sum('amount'),
            'total_sms_used' => SmsMessage::count(),
            'pending_sender_requests' => SmsSenderIdRequest::pending()->count(),
            'provider_balance' => 0,
        ];

        if ($this->beemService->isConfigured()) {
            $balanceResult = $this->beemService->getBalance();
            $stats['provider_balance'] = $balanceResult['balance'] ?? 0;
        }

        $recentTransactions = SmsTransaction::with(['tenant', 'user'])
            ->latest()
            ->take(10)
            ->get();

        $tenantBalances = SmsBalance::with('tenant')
            ->orderByDesc('balance')
            ->take(10)
            ->get();

        return view('admin.sms.dashboard', compact('stats', 'recentTransactions', 'tenantBalances'));
    }

    // === Provider Settings ===
    public function settings()
    {
        $settings = SmsProviderSetting::getBeemAfrica() ?? new SmsProviderSetting(['provider' => 'beem_africa']);
        return view('admin.sms.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string',
            'secret_key' => 'required|string',
            'default_sender_id' => 'nullable|string|max:11',
            'cost_per_sms' => 'required|numeric|min:0',
            'selling_price_per_sms' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $settings = SmsProviderSetting::firstOrNew(['provider' => 'beem_africa']);
        $settings->fill([
            'api_key' => $request->api_key,
            'secret_key' => $request->secret_key,
            'default_sender_id' => $request->default_sender_id,
            'cost_per_sms' => $request->cost_per_sms,
            'selling_price_per_sms' => $request->selling_price_per_sms,
            'is_active' => true,
        ]);
        $settings->save();

        return back()->with('success', 'SMS provider settings updated successfully');
    }

    public function syncBalance()
    {
        if (!$this->beemService->isConfigured()) {
            return response()->json(['success' => false, 'error' => 'Provider not configured']);
        }

        $result = $this->beemService->getBalance();
        return response()->json($result);
    }

    public function syncSenderIds()
    {
        if (!$this->beemService->isConfigured()) {
            return response()->json(['success' => false, 'error' => 'Provider not configured']);
        }

        // Use the new syncSenderIdsToDatabase method that handles all response formats
        $result = $this->beemService->syncSenderIdsToDatabase();

        return response()->json($result);
    }

    // === SMS Packages ===
    public function packages()
    {
        $packages = SmsPackage::ordered()->get();
        return view('admin.sms.packages', compact('packages'));
    }

    public function createPackage()
    {
        return view('admin.sms.package-form', ['package' => null]);
    }

    public function storePackage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'sms_count' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        SmsPackage::create($request->only(['name', 'description', 'sms_count', 'price', 'sort_order', 'is_active']));

        return redirect()->route('admin.sms.packages')->with('success', 'SMS package created successfully');
    }

    public function editPackage(SmsPackage $package)
    {
        return view('admin.sms.package-form', compact('package'));
    }

    public function updatePackage(Request $request, SmsPackage $package)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'sms_count' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $package->update($request->only(['name', 'description', 'sms_count', 'price', 'sort_order', 'is_active']));

        return redirect()->route('admin.sms.packages')->with('success', 'SMS package updated successfully');
    }

    public function togglePackage(SmsPackage $package)
    {
        $package->update(['is_active' => !$package->is_active]);
        return back()->with('success', 'Package status updated');
    }

    // === Sender ID Management ===
    public function senderIdRequests()
    {
        $requests = SmsSenderIdRequest::with(['tenant', 'requestedBy', 'approvedBy'])
            ->latest()
            ->paginate(20);

        return view('admin.sms.sender-id-requests', compact('requests'));
    }

    public function approveSenderId(Request $request, SmsSenderIdRequest $senderIdRequest)
    {
        if (!$senderIdRequest->isPending()) {
            return back()->with('error', 'This request has already been processed');
        }

        DB::transaction(function () use ($request, $senderIdRequest) {
            $senderIdRequest->approve(Auth::id(), $request->notes);

            // Create active sender ID for tenant
            SmsSenderId::create([
                'tenant_id' => $senderIdRequest->tenant_id,
                'sender_id' => $senderIdRequest->sender_id,
                'is_default' => !SmsSenderId::where('tenant_id', $senderIdRequest->tenant_id)->exists(),
                'is_active' => true,
            ]);
        });

        return back()->with('success', 'Sender ID approved successfully');
    }

    public function rejectSenderId(Request $request, SmsSenderIdRequest $senderIdRequest)
    {
        if (!$senderIdRequest->isPending()) {
            return back()->with('error', 'This request has already been processed');
        }

        $senderIdRequest->reject(Auth::id(), $request->notes);

        return back()->with('success', 'Sender ID rejected');
    }

    // === Tenant SMS Management ===
    public function tenantBalances()
    {
        $balances = SmsBalance::with('tenant')
            ->paginate(20);

        return view('admin.sms.tenant-balances', compact('balances'));
    }

    public function creditTenant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|exists:tenants,id',
            'amount' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::transaction(function () use ($request) {
            $balance = SmsBalance::getOrCreateForTenant($request->tenant_id);
            $balanceBefore = $balance->balance;
            
            $balance->credit($request->amount);
            
            SmsTransaction::recordManualCredit(
                $request->tenant_id,
                Auth::id(),
                $request->amount,
                $balanceBefore,
                $request->reason
            );
        });

        return back()->with('success', "Successfully credited {$request->amount} SMS to tenant");
    }

    public function debitTenant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|exists:tenants,id',
            'amount' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $balance = SmsBalance::getOrCreateForTenant($request->tenant_id);
        
        if ($balance->balance < $request->amount) {
            return back()->with('error', 'Tenant does not have enough balance');
        }

        DB::transaction(function () use ($request, $balance) {
            $balanceBefore = $balance->balance;
            $balance->decrement('balance', $request->amount);
            
            SmsTransaction::create([
                'tenant_id' => $request->tenant_id,
                'admin_id' => Auth::id(),
                'type' => SmsTransaction::TYPE_MANUAL_DEBIT,
                'amount' => -$request->amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore - $request->amount,
                'payment_status' => SmsTransaction::PAYMENT_COMPLETED,
                'admin_reason' => $request->reason,
                'description' => "Manual debit of {$request->amount} SMS",
            ]);
        });

        return back()->with('success', "Successfully debited {$request->amount} SMS from tenant");
    }

    // === Reports ===
    public function reports(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo = $request->date_to ?? now()->toDateString();

        $messageStats = SmsMessage::whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->selectRaw('DATE(created_at) as date, status, COUNT(*) as count, SUM(sms_count) as total_sms')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        $tenantUsage = SmsMessage::whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->with('tenant')
            ->selectRaw('tenant_id, COUNT(*) as message_count, SUM(sms_count) as total_sms')
            ->groupBy('tenant_id')
            ->orderByDesc('total_sms')
            ->take(20)
            ->get();

        $revenue = SmsTransaction::where('type', SmsTransaction::TYPE_PURCHASE)
            ->where('payment_status', SmsTransaction::PAYMENT_COMPLETED)
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->sum('payment_amount');

        return view('admin.sms.reports', compact('messageStats', 'tenantUsage', 'revenue', 'dateFrom', 'dateTo'));
    }

    public function allMessages(Request $request)
    {
        $query = SmsMessage::with(['tenant', 'user', 'client']);

        if ($request->tenant_id) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $messages = $query->latest()->paginate(50);
        $tenants = Tenant::orderBy('name')->get(['id', 'name']);

        return view('admin.sms.all-messages', compact('messages', 'tenants'));
    }
}
