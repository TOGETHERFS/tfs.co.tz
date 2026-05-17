<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\Subscription;
use App\Services\NotificationSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlanController extends Controller
{
    /**
     * Display a listing of the plans.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $plans = Plan::orderBy('code')->paginate(15);
        
        // Get tenant subscriptions with their plans
        $subscriptions = Subscription::with(['tenant', 'plan'])
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'subs_page');
        
        // Get all tenants for dropdown
        $tenants = Tenant::orderBy('name')->get();
        $allPlans = Plan::where('is_active', true)->orderBy('code')->get();
        
        return view('admin.plans.index', compact('plans', 'subscriptions', 'tenants', 'allPlans'));
    }

    /**
     * Show the form for editing the specified plan.
     */
    public function edit(Plan $plan)
    {
        $this->authorizeAdmin();
        return view('admin.plans.edit', compact('plan'));
    }

    /**
     * Update the specified plan in storage.
     */
    public function update(Request $request, Plan $plan)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required','string','max:100'],
            'period' => ['required','string','in:monthly'],
            'price' => ['required','numeric','min:0'],
            'price_per_staff' => ['nullable','numeric','min:0'],
            'price_per_branch' => ['nullable','numeric','min:0'],
            'currency' => ['required','string','max:10'],
            'branch_limit' => ['nullable','numeric','min:0'],
            'staff_limit' => ['nullable','numeric','min:0'],
            'is_active' => ['nullable'],
            'sms_price_per_unit' => ['required','numeric','min:1'],
            'sms_volume_limit' => ['required','integer','min:1'],
        ]);

        // Convert to proper types
        $validated['price'] = (int) $validated['price'];
        $validated['price_per_staff'] = isset($validated['price_per_staff']) && $validated['price_per_staff'] !== '' 
            ? (int) $validated['price_per_staff'] 
            : 0;
        $validated['price_per_branch'] = isset($validated['price_per_branch']) && $validated['price_per_branch'] !== '' 
            ? (int) $validated['price_per_branch'] 
            : 0;
        $validated['branch_limit'] = isset($validated['branch_limit']) && $validated['branch_limit'] !== '' 
            ? (int) $validated['branch_limit'] 
            : null;
        $validated['staff_limit'] = isset($validated['staff_limit']) && $validated['staff_limit'] !== '' 
            ? (int) $validated['staff_limit'] 
            : null;
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $plan->update($validated);

        return redirect()->route('admin.plans.index')->with('status', 'Plan updated successfully');
    }

    /**
     * Manually update or create a tenant subscription.
     */
    public function updateTenantSubscription(Request $request)
    {
        $this->authorizeAdmin();
        
        $validated = $request->validate([
            'tenant_id'          => 'required|exists:tenants,id',
            'plan_id'            => 'required|exists:plans,id',
            'status'             => 'required|in:active,cancelled,expired,pending',
            'current_period_end' => 'required|date',
            'start_date'         => 'nullable|date',
            'months'             => 'nullable|integer|min:1|max:120',
            'total_amount'       => 'nullable|numeric|min:0',
            'notes'              => 'nullable|string|max:500',
        ]);
        
        $tenant = Tenant::findOrFail($validated['tenant_id']);
        $plan   = Plan::findOrFail($validated['plan_id']);

        $startDate   = $validated['start_date'] ?? now()->format('Y-m-d');
        $months      = (int) ($validated['months'] ?? 1);
        $totalAmount = (float) ($validated['total_amount'] ?? ($plan->price * $months));
        
        // Find or create subscription
        $subscription = Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id'              => $plan->id,
                'status'               => $validated['status'],
                'current_period_start' => $startDate,
                'current_period_end'   => $validated['current_period_end'],
                'grace_days'           => 7,
            ]
        );
        
        // Update tenant's plan_slug and status
        $tenant->update([
            'plan_slug'      => $plan->code,
            'plan_id'        => $plan->id,
            'status'         => $validated['status'] === 'active' ? 'active' : $tenant->status,
            'plan_renews_at' => $validated['current_period_end'],
            'is_on_trial'    => false,
        ]);

        // Create a payment record when activating so Revenue MTD is tracked
        if ($validated['status'] === 'active' && $totalAmount > 0) {
            Payment::create([
                'tenant_id'      => $tenant->id,
                'amount'         => $totalAmount,
                'payment_method' => 'manual',
                'reference'      => 'ADMIN-' . now()->format('YmdHis'),
                'status'         => 'success',
                'paid_at'        => now(),
                'provider'       => 'manual',
                'notes'          => $validated['notes'] ?? "Manual activation: {$months} month(s) of {$plan->name}",
            ]);
        }
        
        Log::info('Admin manually updated subscription', [
            'admin_id' => auth()->id(),
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? '',
        ]);

        // Send subscription confirmation SMS to tenant admin
        if ($validated['status'] === 'active' && $totalAmount > 0) {
            try {
                $renewsUntil = \Carbon\Carbon::parse($validated['current_period_end'])->format('M d, Y');
                app(NotificationSmsService::class)->sendSubscriptionPaymentSms(
                    $tenant,
                    $totalAmount,
                    $plan->name,
                    $renewsUntil
                );
            } catch (\Throwable $e) {
                Log::warning('Manual subscription SMS failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            }

            // Alert superadmin about manual subscription activation
            try {
                app(NotificationSmsService::class)->notifySuperadminSubscriptionPayment(
                    $tenant,
                    $totalAmount,
                    $plan->name
                );
            } catch (\Throwable $e) {
                Log::warning('Superadmin manual subscription SMS failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.plans.index')
            ->with('status', "Subscription for {$tenant->name} updated to {$plan->name} ({$validated['status']})");
    }

    /**
     * Delete a tenant subscription record.
     */
    public function deleteSubscription(Subscription $subscription)
    {
        $this->authorizeAdmin();

        $tenantName = $subscription->tenant->name ?? 'Unknown';
        $tenantId   = $subscription->tenant_id;

        // Delete the manual payment records created for this subscription
        Payment::where('tenant_id', $tenantId)
            ->where('provider', 'manual')
            ->delete();

        $subscription->delete();

        return redirect()->route('admin.plans.index')
            ->with('status', "Subscription and manual payments for {$tenantName} have been deleted.");
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}