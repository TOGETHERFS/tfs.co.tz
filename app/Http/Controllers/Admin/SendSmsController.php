<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SmsLog;
use App\Models\SmsWallet;
use App\Models\Sms\SmsBalance;
use App\Services\SmsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendSmsController extends Controller
{
    protected SmsManager $smsManager;

    public function __construct(SmsManager $smsManager)
    {
        $this->smsManager = $smsManager;
    }

    /**
     * Resolve the tenant ID to use for superadmin SMS operations.
     */
    private function resolveTenantId(): ?int
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id ?? null;

        if (!$tenantId) {
            // Try common slugs for the PHIDTECH superadmin tenant
            $phidtechTenant = Tenant::where('slug', 'phidtech-t-limited')
                ->orWhere('slug', 'phidtech')
                ->orWhere('name', 'like', '%PHIDTECH%')
                ->first();
            if ($phidtechTenant) {
                $tenantId = $phidtechTenant->id;
            } else {
                // Fall back to the first active tenant
                $firstTenant = Tenant::where('status', 'active')->first();
                if ($firstTenant) {
                    $tenantId = $firstTenant->id;
                }
            }
        }

        return $tenantId;
    }

    /**
     * Display the send SMS form
     */
    public function index()
    {
        $tenants = Tenant::where('status', 'active')
            ->select('id', 'name', 'phone', 'contact_email')
            ->orderBy('name')
            ->get();

        $smsBalance = 0;
        $tenantId = $this->resolveTenantId();
        if ($tenantId) {
            $balance = SmsBalance::where('tenant_id', $tenantId)->first();
            $smsBalance = $balance ? $balance->balance : 0;
        }

        return view('admin.sms.send', compact('tenants', 'smsBalance'));
    }

    /**
     * Send SMS to selected recipients synchronously (no queue).
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'send_to'      => 'required|in:single,selected,all',
            'phone_number' => 'required_if:send_to,single|nullable|string',
            'tenant_ids'   => 'required_if:send_to,selected|nullable|array',
            'tenant_ids.*' => 'exists:tenants,id',
            'sender_id'    => 'required|string|max:11',
            'message'      => 'required|string|max:800',
        ]);

        // Resolve tenant
        $tenantId = $this->resolveTenantId();
        if (!$tenantId) {
            return back()->with('error', 'Your account is not linked to a tenant. Please contact support.');
        }

        // Build recipient list
        $recipients = [];
        $sendTo = $validated['send_to'];

        if ($sendTo === 'single') {
            $phone = $this->formatPhoneNumber($validated['phone_number']);
            if ($phone) {
                $recipients[] = $phone;
            }
        } elseif ($sendTo === 'selected') {
            $tenants = Tenant::whereIn('id', $validated['tenant_ids'])->get();
            foreach ($tenants as $tenant) {
                $phone = $this->formatPhoneNumber($tenant->phone);
                if ($phone) {
                    $recipients[] = $phone;
                }
            }
        } elseif ($sendTo === 'all') {
            $tenants = Tenant::where('status', 'active')->get();
            foreach ($tenants as $tenant) {
                $phone = $this->formatPhoneNumber($tenant->phone);
                if ($phone) {
                    $recipients[] = $phone;
                }
            }
        }

        if (empty($recipients)) {
            return back()->with('error', 'No valid phone numbers found for the selected recipients.');
        }

        // Ensure SMS wallet exists for this tenant
        $wallet = SmsWallet::firstOrCreate(
            ['tenant_id' => $tenantId],
            ['balance' => 0, 'ledger' => []]
        );

        try {
            // Send synchronously via SmsManager (deducts credits + calls provider immediately)
            $result = $this->smsManager->sendBulkSms(
                $tenantId,
                $recipients,
                $validated['message'],
                $validated['sender_id'],
                auth()->id()
            );

            if ($result['success']) {
                $count = count($recipients);
                return back()->with('success', "SMS sent successfully to {$count} recipient(s). Credits remaining: " . ($result['remaining_balance'] ?? 'N/A'));
            }

            $errorDetail = $result['message'] ?? 'Failed to send SMS.';
            if (!empty($result['error'])) {
                $errorDetail .= ' | Provider error: ' . (is_array($result['error']) ? json_encode($result['error']) : $result['error']);
            }
            return back()->with('error', $errorDetail);

        } catch (\Exception $e) {
            Log::error('Admin SMS sending error', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Failed to send SMS: ' . $e->getMessage());
        }
    }

    /**
     * Format phone number to international format
     */
    private function formatPhoneNumber($phone)
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 0, replace with 255 (Tanzania)
        if (substr($phone, 0, 1) === '0') {
            $phone = '255' . substr($phone, 1);
        }

        // If doesn't start with 255, add it
        if (substr($phone, 0, 3) !== '255') {
            $phone = '255' . $phone;
        }

        // Validate length (should be 12 digits for Tanzania: 255 + 9 digits)
        if (strlen($phone) !== 12) {
            return null;
        }

        return $phone;
    }

}
