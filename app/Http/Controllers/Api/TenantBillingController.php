<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionItem;
use App\Models\Tenant;
use App\Services\SelcomPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantBillingController extends Controller
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

    public function summary(Request $request)
    {
        $tenant = $this->tenantOrAbort();
        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->with(['plan', 'items'])
            ->first();

        $latestInvoice = Invoice::where('tenant_id', $tenant->id)
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'status' => $tenant->status,
                    'trial_ends_at' => $tenant->trial_ends_at,
                ],
                'subscription' => $subscription,
                'latest_invoice' => $latestInvoice,
            ]
        ]);
    }

    public function plans()
    {
        $plans = Plan::active()
            ->whereIn('code', ['starter','growth','enterprise'])
            ->with('addons')
            ->get();
        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    public function subscription()
    {
        $tenant = $this->tenantOrAbort();
        $sub = Subscription::where('tenant_id', $tenant->id)
            ->with(['plan', 'items'])
            ->first();
        return response()->json(['success' => true, 'data' => $sub]);
    }

    public function changePlan(Request $request)
    {
        $tenant = $this->tenantOrAbort();
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $sub = Subscription::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $validated['plan_id'],
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
                'grace_days' => 7,
                'cancel_at_period_end' => false,
            ]
        );

        if ($sub->exists) {
            $sub->update(['plan_id' => $validated['plan_id']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Plan updated',
            'data' => $sub->load('plan')
        ]);
    }

    public function updateAddons(Request $request)
    {
        $tenant = $this->tenantOrAbort();
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.addon_slug' => 'required|string',
            'items.*.quantity' => 'required|integer|min:0',
            'items.*.unit_price' => 'required|integer|min:0',
            'items.*.currency' => 'nullable|string|size:3',
        ]);

        $sub = Subscription::where('tenant_id', $tenant->id)->firstOrFail();
        DB::transaction(function () use ($sub, $validated) {
            foreach ($validated['items'] as $item) {
                SubscriptionItem::updateOrCreate(
                    ['subscription_id' => $sub->id, 'addon_slug' => $item['addon_slug']],
                    [
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'currency' => $item['currency'] ?? 'TZS',
                    ]
                );
            }
        });

        return response()->json(['success' => true, 'message' => 'Add-ons updated']);
    }

    public function cancel(Request $request)
    {
        $tenant = $this->tenantOrAbort();
        $sub = Subscription::where('tenant_id', $tenant->id)->firstOrFail();
        $sub->update(['cancel_at_period_end' => true]);
        return response()->json(['success' => true, 'message' => 'Subscription will cancel at period end']);
    }

    public function resume(Request $request)
    {
        $tenant = $this->tenantOrAbort();
        $sub = Subscription::where('tenant_id', $tenant->id)->firstOrFail();
        $sub->update(['cancel_at_period_end' => false]);
        return response()->json(['success' => true, 'message' => 'Subscription resumed']);
    }

    public function initiatePayment(Request $request, SelcomPaymentService $selcom)
    {
        $tenant = $this->tenantOrAbort();
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'channel' => 'nullable|string',
        ]);

        $invoice = Invoice::where('tenant_id', $tenant->id)->findOrFail($validated['invoice_id']);

        $reference = 'INV-' . $invoice->number . '-' . time();
        $payload = [
            'reference' => $reference,
            'amount' => (int) $invoice->remaining_balance,
            'currency' => $invoice->currency,
            'channel' => $validated['channel'] ?? 'USSD',
        ];

        $result = $selcom->initiate($payload);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'provider' => 'selcom',
            'reference' => $reference,
            'amount' => $payload['amount'],
            'status' => $result['status'] ?? 'pending',
            'payload' => $result,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated',
            'data' => $payment
        ], 201);
    }
}