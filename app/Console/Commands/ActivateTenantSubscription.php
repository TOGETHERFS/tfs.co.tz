<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class ActivateTenantSubscription extends Command
{
    protected $signature = 'tenant:activate-subscription {email} {plan_code} {amount} {payment_date}';
    protected $description = 'Manually activate a tenant subscription and record payment';

    public function handle()
    {
        $email = $this->argument('email');
        $planCode = $this->argument('plan_code');
        $amount = $this->argument('amount');
        $paymentDate = $this->argument('payment_date');

        // Find tenant
        $tenant = Tenant::where('contact_email', $email)->first();
        
        if (!$tenant) {
            $this->error("Tenant with email {$email} not found!");
            return 1;
        }

        // Find plan
        $plan = Plan::where('code', $planCode)->first();
        
        if (!$plan) {
            $this->error("Plan with code {$planCode} not found!");
            return 1;
        }

        DB::beginTransaction();
        try {
            $this->info("Processing subscription activation for {$tenant->name}...");

            // Determine subscription period
            $period = strtolower((string) ($plan->period ?? 'monthly'));
            $months = match ($period) {
                'monthly', 'month' => 1,
                'semiannual', 'semi_annual', 'semiannualy', 'halfyear', 'half_year' => 6,
                'annual', 'year', 'yearly' => 12,
                default => 1,
            };

            // Create or update subscription
            $subscription = Subscription::firstOrNew(['tenant_id' => $tenant->id]);
            
            $startDate = now();
            $endDate = $startDate->copy()->addMonths($months);
            
            $subscription->fill([
                'plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => $startDate,
                'current_period_end' => $endDate,
                'grace_days' => 7,
                'cancel_at_period_end' => false,
            ]);
            $subscription->save();

            $this->info("✓ Subscription created/updated");

            // Update tenant
            $tenant->update([
                'plan_id' => $plan->id,
                'plan_slug' => $plan->code,
                'status' => 'active',
                'plan_renews_at' => $endDate,
            ]);

            $this->info("✓ Tenant updated to active status");

            // Create invoice
            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'number' => 'INV-' . strtoupper(uniqid()),
                'amount' => $amount,
                'currency' => 'TZS',
                'due_date' => now(),
                'status' => 'paid',
                'months' => $months,
            ]);

            $this->info("✓ Invoice created: {$invoice->number}");

            // Create payment record
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'provider' => 'selcom',
                'reference' => 'SELCOM-' . now()->format('YmdHis'),
                'amount' => $amount,
                'status' => 'success',
                'paid_at' => $paymentDate,
                'payment_method' => 'selcom',
                'notes' => 'Manual activation - Payment received via Selcom',
            ]);

            $this->info("✓ Payment recorded: {$payment->reference}");

            DB::commit();

            $this->info("\n========================================");
            $this->info("SUCCESS! Subscription activated for {$tenant->name}");
            $this->info("========================================");
            $this->info("Plan: {$plan->name} ({$plan->code})");
            $this->info("Amount: TZS " . number_format($amount));
            $this->info("Payment Date: {$paymentDate}");
            $this->info("Period: {$months} month(s)");
            $this->info("Renews: {$endDate->format('Y-m-d')}");
            $this->info("Invoice: {$invoice->number}");
            $this->info("Payment Ref: {$payment->reference}");
            $this->info("========================================\n");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to activate subscription: " . $e->getMessage());
            return 1;
        }
    }
}
