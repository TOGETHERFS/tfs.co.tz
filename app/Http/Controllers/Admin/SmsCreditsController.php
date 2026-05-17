<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SmsPurchase;
use App\Models\Sms\SmsBalance;
use App\Services\NotificationSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SmsCreditsController extends Controller
{
    /**
     * Display SMS credit management page.
     */
    public function index()
    {
        $balances = SmsBalance::with('tenant')->get();
        $purchases = SmsPurchase::with('tenant')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        $tenants = Tenant::orderBy('name')->get();

        return view('admin.sms-credits.index', compact('balances', 'purchases', 'tenants'));
    }

    /**
     * Add SMS credits to a tenant manually.
     */
    public function addCredits(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            // Get or create SMS balance for tenant
            $smsBalance = SmsBalance::getOrCreateForTenant($validated['tenant_id']);
            
            // Credit the balance
            $smsBalance->credit($validated['quantity']);

            // Create a purchase record for tracking
            SmsPurchase::create([
                'tenant_id' => $validated['tenant_id'],
                'user_id' => Auth::id(),
                'quantity' => $validated['quantity'],
                'unit_price' => 0,
                'total_amount' => 0,
                'status' => 'approved',
                'notes' => $validated['notes'] ?? 'Manual credit by admin',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Also update tenant table for backward compatibility
            Tenant::where('id', $validated['tenant_id'])->increment('sms_credits', $validated['quantity']);

            // Send SMS package activation notification to tenant admin
            try {
                $tenant = Tenant::find($validated['tenant_id']);
                if ($tenant) {
                    app(NotificationSmsService::class)->sendSmsPackageActivatedSms(
                        $tenant,
                        (int) $validated['quantity'],
                        0.0
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('SMS package notification failed silently', ['tenant_id' => $validated['tenant_id'], 'error' => $e->getMessage()]);
            }

            // Alert superadmin about SMS package activation
            try {
                if (!isset($tenant)) {
                    $tenant = Tenant::find($validated['tenant_id']);
                }
                if ($tenant) {
                    app(NotificationSmsService::class)->notifySuperadminSmsPackagePayment(
                        $tenant,
                        (int) $validated['quantity'],
                        0.0
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Superadmin SMS package alert failed silently', ['tenant_id' => $validated['tenant_id'], 'error' => $e->getMessage()]);
            }

            return redirect()->route('admin.sms-credits.index')
                ->with('success', "Successfully added {$validated['quantity']} SMS credits to tenant.");
        } catch (\Exception $e) {
            return redirect()->route('admin.sms-credits.index')
                ->with('error', 'Failed to add credits: ' . $e->getMessage());
        }
    }
}
