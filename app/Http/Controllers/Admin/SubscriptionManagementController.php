<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionManagementController extends Controller
{
    /**
     * Display all tenant subscriptions for super admin
     */
    public function index()
    {
        $tenants = Tenant::with(['subscriptions.plan', 'invoices', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total_tenants' => Tenant::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'trial_subscriptions' => Subscription::where('status', 'trial')->count(),
            'suspended_subscriptions' => Subscription::where('status', 'suspended')->count(),
            'total_revenue_mtd' => Payment::whereIn('status', ['success', 'completed'])
                ->where(function ($q) {
                    $q->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                      ->orWhere(function ($q2) {
                          $q2->whereNull('paid_at')
                             ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                      });
                })
                ->sum('amount'),
            'pending_payments' => Invoice::where('status', 'pending')->sum('amount'),
            'overdue_invoices' => Invoice::where('status', 'overdue')->count(),
        ];

        return view('admin.subscriptions.index', compact('tenants', 'stats'));
    }

    /**
     * Show subscription details for a specific tenant
     */
    public function show(Tenant $tenant)
    {
        $tenant->load(['subscriptions.plan', 'invoices.payments', 'payments']);
        
        $stats = [
            'total_invoices' => $tenant->invoices()->count(),
            'paid_invoices' => $tenant->invoices()->where('status', 'paid')->count(),
            'pending_invoices' => $tenant->invoices()->where('status', 'pending')->count(),
            'overdue_invoices' => $tenant->invoices()->where('status', 'overdue')->count(),
            'total_paid' => $tenant->payments()->where('status', 'success')->sum('amount'),
            'total_pending' => $tenant->invoices()->where('status', 'pending')->sum('amount'),
        ];

        return view('admin.subscriptions.show', compact('tenant', 'stats'));
    }

    /**
     * Record manual payment for a tenant
     */
    public function recordPayment(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,bank_transfer,mobile_money,other',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'paid_at' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            // Create payment record
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'invoice_id' => $validated['invoice_id'] ?? null,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? 'MANUAL-' . now()->format('YmdHis'),
                'status' => 'success',
                'paid_at' => $validated['paid_at'] ?? now(),
                'notes' => $validated['notes'] ?? 'Manual payment recorded by super admin',
            ]);

            // If linked to an invoice, update invoice status
            if ($validated['invoice_id']) {
                $invoice = Invoice::find($validated['invoice_id']);
                $totalPaid = $invoice->payments()->where('status', 'success')->sum('amount');
                
                if ($totalPaid >= $invoice->amount) {
                    $invoice->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                }
            }

            \Log::info('Manual payment recorded by superadmin', ['tenant_id' => $tenant->id, 'amount' => $validated['amount']]);

            DB::commit();

            return redirect()->back()->with('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }

    /**
     * Extend trial period for a tenant
     */
    public function extendTrial(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
            'reason' => 'nullable|string|max:500',
        ]);

        $currentTrialEnd = $tenant->trial_ends_at ?? now();
        $newTrialEnd = Carbon::parse($currentTrialEnd)->addDays($validated['days']);

        $tenant->update([
            'trial_ends_at' => $newTrialEnd,
            'is_on_trial' => true,
        ]);

        // Update subscription period end as well
        $subscription = $tenant->subscriptions()->latest()->first();
        if ($subscription) {
            $newPeriodEnd = ($subscription->current_period_end && Carbon::parse($subscription->current_period_end)->isFuture())
                ? Carbon::parse($subscription->current_period_end)->addDays($validated['days'])
                : $newTrialEnd;
            $subscription->update(['current_period_end' => $newPeriodEnd]);
            $tenant->update(['plan_renews_at' => $newPeriodEnd]);
        }

        \Log::info('Plan extended by superadmin', ['tenant_id' => $tenant->id, 'days' => $validated['days'], 'new_end' => $newTrialEnd]);

        return redirect()->back()->with('success', "Plan extended by {$validated['days']} days.");
    }

    /**
     * Update tenant subscription plan (upgrade or downgrade)
     */
    public function updatePlan(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan_id'        => 'required|exists:plans,id',
            'keep_period'    => 'nullable|boolean',
            'new_period_end' => 'nullable|date',
            'notes'          => 'nullable|string|max:500',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        DB::beginTransaction();
        try {
            $subscription = $tenant->subscriptions()->latest()->first();

            // Determine period end: keep existing end unless admin overrides
            $keepPeriod  = $validated['keep_period'] ?? true;
            $existingEnd = $subscription?->current_period_end;

            if (!$keepPeriod && !empty($validated['new_period_end'])) {
                $periodEnd = Carbon::parse($validated['new_period_end']);
            } elseif ($existingEnd && Carbon::parse($existingEnd)->isFuture()) {
                $periodEnd = Carbon::parse($existingEnd);
            } else {
                // Default: 1 month from now
                $periodEnd = now()->addMonth();
            }

            if ($subscription) {
                $subscription->update([
                    'plan_id'              => $plan->id,
                    'status'               => 'active',
                    'current_period_start' => $subscription->current_period_start ?? now(),
                    'current_period_end'   => $periodEnd,
                ]);
            } else {
                $subscription = Subscription::create([
                    'tenant_id'            => $tenant->id,
                    'plan_id'              => $plan->id,
                    'status'               => 'active',
                    'current_period_start' => now(),
                    'current_period_end'   => $periodEnd,
                    'grace_days'           => 7,
                ]);
            }

            $tenant->update([
                'plan_id'       => $plan->id,
                'plan_slug'     => $plan->code,
                'status'        => 'active',
                'plan_renews_at'=> $periodEnd,
            ]);

            \Log::info('Subscription plan changed by superadmin', [
                'admin_id'   => auth()->id(),
                'tenant_id'  => $tenant->id,
                'plan'       => $plan->name,
                'period_end' => $periodEnd,
                'notes'      => $validated['notes'] ?? '',
            ]);

            DB::commit();

            return redirect()->back()->with('success', "Plan changed to {$plan->name} successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update plan: ' . $e->getMessage());
        }
    }

    /**
     * Suspend tenant subscription
     */
    public function suspend(Tenant $tenant, Request $request)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $subscription = $tenant->subscriptions()->latest()->first();
        if ($subscription) {
            $subscription->update([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspension_reason' => $validated['reason'] ?? 'Suspended by super admin',
            ]);
        }

        $tenant->update([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);

        \Log::info('Subscription suspended by superadmin', ['tenant_id' => $tenant->id]);

        return redirect()->back()->with('success', 'Subscription suspended successfully.');
    }

    /**
     * Reactivate tenant subscription
     */
    public function reactivate(Tenant $tenant)
    {
        $subscription = $tenant->subscriptions()->latest()->first();
        if ($subscription) {
            $subscription->update([
                'status' => 'active',
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);
        }

        $tenant->update([
            'status' => 'active',
            'suspended_at' => null,
        ]);

        \Log::info('Subscription reactivated by superadmin', ['tenant_id' => $tenant->id]);

        return redirect()->back()->with('success', 'Subscription reactivated successfully.');
    }

    /**
     * Reduce plan period for a tenant
     */
    public function reducePlan(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
            'reason' => 'required|string|max:500',
        ]);

        // Reduce trial period if on trial
        if ($tenant->trial_ends_at) {
            $currentTrialEnd = $tenant->trial_ends_at;
            $newTrialEnd = Carbon::parse($currentTrialEnd)->subDays($validated['days']);

            $tenant->update([
                'trial_ends_at' => $newTrialEnd,
            ]);

            // Update subscription if exists
            $subscription = $tenant->subscriptions()->latest()->first();
            if ($subscription && $subscription->status === 'trial') {
                $subscription->update([
                    'trial_ends_at' => $newTrialEnd,
                ]);
            }
        } else {
            // Reduce subscription period end
            $subscription = $tenant->subscriptions()->latest()->first();
            if ($subscription && $subscription->current_period_end) {
                $newEnd = Carbon::parse($subscription->current_period_end)->subDays($validated['days']);

                $subscription->update(['current_period_end' => $newEnd]);
                $tenant->update(['plan_renews_at' => $newEnd]);
            }
        }

        \Log::info('Plan reduced by superadmin', ['tenant_id' => $tenant->id, 'days' => $validated['days']]);

        return redirect()->back()->with('success', "Plan period reduced by {$validated['days']} days.");
    }

    /**
     * Manually edit revenue for a tenant (record manual payment)
     */
    public function editRevenue(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,bank_transfer,mobile_money,other',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'paid_at' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            // Create manual payment record
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? 'MANUAL-' . now()->format('YmdHis'),
                'status' => 'success',
                'paid_at' => $validated['paid_at'] ?? now(),
                'notes' => $validated['notes'] ?? 'Manual revenue entry by super admin',
                'provider' => 'manual',
            ]);

            \Log::info('Manual revenue recorded by superadmin', ['tenant_id' => $tenant->id, 'amount' => $validated['amount']]);

            DB::commit();

            return redirect()->back()->with('success', 'Revenue updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update revenue: ' . $e->getMessage());
        }
    }
}
