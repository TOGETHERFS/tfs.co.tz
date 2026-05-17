<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class SetTenantFreePlan extends Command
{
    protected $signature = 'tenant:set-free {email}';
    protected $description = 'Set a tenant to free plan, remove all payments and invoices';

    public function handle()
    {
        $email = $this->argument('email');

        $tenant = Tenant::where('contact_email', 'like', "%{$email}%")
            ->orWhere('name', 'like', "%{$email}%")
            ->first();

        if (!$tenant) {
            $this->error("Tenant not found for: {$email}");
            $this->info("Available tenants with similar name/email:");
            Tenant::all()->each(function ($t) {
                $this->line("  ID:{$t->id} | {$t->name} | {$t->contact_email}");
            });
            return 1;
        }

        $this->info("Found tenant: {$tenant->name} ({$tenant->contact_email})");
        $this->info("Current plan: {$tenant->plan_slug}, status: {$tenant->status}");

        DB::beginTransaction();
        try {
            // Delete all payments
            $deletedPayments = Payment::where('tenant_id', $tenant->id)->delete();
            $this->info("✓ Deleted {$deletedPayments} payment(s)");

            // Delete all invoices
            $deletedInvoices = Invoice::where('tenant_id', $tenant->id)->delete();
            $this->info("✓ Deleted {$deletedInvoices} invoice(s)");

            // Find free plan
            $freePlan = Plan::where('price', 0)->first()
                ?? Plan::where('code', 'free_trial')->first()
                ?? Plan::where('code', 'free')->first();

            if ($freePlan) {
                // Update subscription
                $sub = Subscription::where('tenant_id', $tenant->id)->latest()->first();
                if ($sub) {
                    $sub->update([
                        'plan_id' => $freePlan->id,
                        'status' => 'active',
                    ]);
                } else {
                    Subscription::create([
                        'tenant_id' => $tenant->id,
                        'plan_id' => $freePlan->id,
                        'status' => 'active',
                        'current_period_start' => now(),
                        'current_period_end' => now()->addYears(10),
                        'grace_days' => 0,
                        'cancel_at_period_end' => false,
                    ]);
                }

                $tenant->update([
                    'plan_id' => $freePlan->id,
                    'plan_slug' => $freePlan->code,
                    'status' => 'active',
                    'plan_renews_at' => null,
                ]);

                $this->info("✓ Set to free plan: {$freePlan->name} (code: {$freePlan->code})");
            } else {
                // No free plan - just clear billing, keep active
                $tenant->update([
                    'status' => 'active',
                    'plan_renews_at' => null,
                ]);
                $this->info("✓ No free plan found in DB - cleared billing data, tenant set to active");
            }

            DB::commit();

            $this->info("\n========================================");
            $this->info("SUCCESS: {$tenant->name}");
            $this->info("Revenue: TZS 0");
            $this->info("Invoices: 0");
            $this->info("Plan: " . ($freePlan->name ?? $tenant->fresh()->plan_slug));
            $this->info("Status: " . $tenant->fresh()->status);
            $this->info("========================================\n");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed: " . $e->getMessage());
            return 1;
        }
    }
}
