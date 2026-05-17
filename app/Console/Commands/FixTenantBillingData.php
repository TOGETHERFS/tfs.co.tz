<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class FixTenantBillingData extends Command
{
    protected $signature = 'tenant:fix-billing-data';
    protected $description = 'Fix billing data for specific tenants: kware (30000 only), masanjac (remove 120000), bagokap (free plan)';

    public function handle()
    {
        $this->info("Starting billing data fixes...\n");

        $this->fixKwareMicrofinance();
        $this->fixMasanjac();
        $this->fixBagokap();

        $this->info("\nAll fixes completed!");
        return 0;
    }

    private function fixKwareMicrofinance()
    {
        $this->info("=== FIX 1: KWARE MICROFINANCE (kwaremicrofinancetz@gmail.com) ===");
        $this->info("Issue: Paid 30,000 only on 18/02/2026, but showing 60,000 revenue");

        $tenant = Tenant::where('contact_email', 'kwaremicrofinancetz@gmail.com')->first();
        if (!$tenant) {
            $this->error("Tenant not found!");
            return;
        }

        DB::beginTransaction();
        try {
            // Show current payments
            $payments = Payment::where('tenant_id', $tenant->id)->get();
            $this->info("Current payments: " . $payments->count());
            foreach ($payments as $p) {
                $this->info("  - ID:{$p->id} Ref:{$p->reference} Amount:{$p->amount} Status:{$p->status} Date:{$p->paid_at}");
            }

            // Keep only one successful payment of 30000, delete duplicates
            $successPayments = Payment::where('tenant_id', $tenant->id)
                ->whereIn('status', ['success', 'completed'])
                ->orderBy('id')
                ->get();

            if ($successPayments->count() > 1) {
                // Keep the first one, delete the rest
                $keepPayment = $successPayments->first();
                $keepPayment->update([
                    'amount' => 30000,
                    'paid_at' => '2026-02-18 00:00:00',
                    'provider' => 'selcom',
                    'payment_method' => 'selcom',
                    'notes' => 'Payment received via Selcom on 18/02/2026',
                ]);

                foreach ($successPayments->skip(1) as $dup) {
                    $dup->delete();
                    $this->info("  ✓ Deleted duplicate payment ID:{$dup->id}");
                }
                $this->info("  ✓ Kept payment ID:{$keepPayment->id} - TZS 30,000");
            } elseif ($successPayments->count() == 1) {
                $successPayments->first()->update([
                    'amount' => 30000,
                    'paid_at' => '2026-02-18 00:00:00',
                    'provider' => 'selcom',
                    'payment_method' => 'selcom',
                    'notes' => 'Payment received via Selcom on 18/02/2026',
                ]);
                $this->info("  ✓ Updated payment to TZS 30,000");
            }

            // Delete any pending payments
            Payment::where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->delete();

            // Fix invoices: keep only one paid invoice for 30000, mark others as pending
            $invoices = Invoice::where('tenant_id', $tenant->id)->orderBy('id')->get();
            $this->info("Current invoices: " . $invoices->count());
            foreach ($invoices as $inv) {
                $this->info("  - ID:{$inv->id} Num:{$inv->number} Amount:{$inv->amount} Status:{$inv->status}");
            }

            // Mark first invoice as paid with correct amount, rest as pending
            $firstInvoice = $invoices->first();
            if ($firstInvoice) {
                $firstInvoice->update(['amount' => 30000, 'status' => 'paid']);
                $this->info("  ✓ Invoice {$firstInvoice->number} marked as paid - TZS 30,000");
            }

            foreach ($invoices->skip(1) as $inv) {
                $inv->update(['status' => 'pending']);
                $this->info("  ✓ Invoice {$inv->number} set to pending");
            }

            DB::commit();

            $finalRevenue = Payment::where('tenant_id', $tenant->id)->whereIn('status', ['success', 'completed'])->sum('amount');
            $this->info("  ✓ Final revenue: TZS " . number_format($finalRevenue));
            $this->info("DONE\n");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed: " . $e->getMessage());
        }
    }

    private function fixMasanjac()
    {
        $this->info("=== FIX 2: MASANJAC (masanjac76@gmail.com) ===");
        $this->info("Issue: No payment made, but showing TZS 120,000 revenue - remove all payments");

        $tenant = Tenant::where('contact_email', 'masanjac76@gmail.com')->first();
        if (!$tenant) {
            $this->error("Tenant not found!");
            return;
        }

        DB::beginTransaction();
        try {
            // Show current payments
            $payments = Payment::where('tenant_id', $tenant->id)->get();
            $this->info("Current payments: " . $payments->count());
            foreach ($payments as $p) {
                $this->info("  - ID:{$p->id} Ref:{$p->reference} Amount:{$p->amount} Status:{$p->status}");
            }

            // Delete ALL payments for this tenant
            $deleted = Payment::where('tenant_id', $tenant->id)->delete();
            $this->info("  ✓ Deleted {$deleted} payment(s)");

            // Mark all invoices as pending (unpaid)
            $updated = Invoice::where('tenant_id', $tenant->id)->update(['status' => 'pending']);
            $this->info("  ✓ Reset {$updated} invoice(s) to pending");

            DB::commit();

            $finalRevenue = Payment::where('tenant_id', $tenant->id)->whereIn('status', ['success', 'completed'])->sum('amount');
            $this->info("  ✓ Final revenue: TZS " . number_format($finalRevenue));
            $this->info("DONE\n");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed: " . $e->getMessage());
        }
    }

    private function fixBagokap()
    {
        $this->info("=== FIX 3: BAGOKAP (bagokap.8275@gmail.com) ===");
        $this->info("Issue: Has free plan - should not be charged, set to free plan with no invoices");

        $tenant = Tenant::where('contact_email', 'bagokap.8275@gmail.com')->first();
        if (!$tenant) {
            $this->error("Tenant not found!");
            return;
        }

        DB::beginTransaction();
        try {
            // Find free plan
            $freePlan = Plan::where('code', 'free_trial')
                ->orWhere('price', 0)
                ->orWhere('code', 'free')
                ->first();

            $this->info("Current plan_slug: {$tenant->plan_slug}, status: {$tenant->status}");

            // Delete all payments (no payment should exist for free plan)
            $deleted = Payment::where('tenant_id', $tenant->id)->delete();
            $this->info("  ✓ Deleted {$deleted} payment(s)");

            // Delete all invoices (free plan should not have invoices)
            $deletedInv = Invoice::where('tenant_id', $tenant->id)->delete();
            $this->info("  ✓ Deleted {$deletedInv} invoice(s)");

            // Update subscription to free plan or remove it
            if ($freePlan) {
                Subscription::where('tenant_id', $tenant->id)->update([
                    'plan_id' => $freePlan->id,
                    'status' => 'active',
                ]);

                $tenant->update([
                    'plan_id' => $freePlan->id,
                    'plan_slug' => $freePlan->code,
                    'status' => 'active',
                    'plan_renews_at' => null,
                ]);
                $this->info("  ✓ Set to free plan: {$freePlan->name}");
            } else {
                // No free plan found, just clear billing data and keep current plan
                Subscription::where('tenant_id', $tenant->id)->update(['status' => 'active']);
                $tenant->update([
                    'status' => 'active',
                    'plan_renews_at' => null,
                ]);
                $this->info("  ✓ No free plan found - cleared billing data, kept current plan");
            }

            DB::commit();

            $finalRevenue = Payment::where('tenant_id', $tenant->id)->whereIn('status', ['success', 'completed'])->sum('amount');
            $this->info("  ✓ Final revenue: TZS " . number_format($finalRevenue));
            $this->info("DONE\n");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed: " . $e->getMessage());
        }
    }
}
