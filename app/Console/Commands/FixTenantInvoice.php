<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

class FixTenantInvoice extends Command
{
    protected $signature = 'tenant:fix-invoice {email}';
    protected $description = 'Mark all pending invoices as paid and activate subscription for a tenant';

    public function handle()
    {
        $email = $this->argument('email');

        $tenant = Tenant::where('contact_email', $email)->first();

        if (!$tenant) {
            $this->error("Tenant with email {$email} not found!");
            return 1;
        }

        $this->info("Fixing invoices for: {$tenant->name} ({$tenant->contact_email})");
        $this->info("Tenant ID: {$tenant->id}");

        DB::beginTransaction();
        try {
            // Get all pending invoices
            $pendingInvoices = Invoice::where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->get();

            $this->info("Found {$pendingInvoices->count()} pending invoice(s)");

            foreach ($pendingInvoices as $invoice) {
                $this->info("  Processing invoice: {$invoice->number} - TZS " . number_format($invoice->amount));

                // Check if there's already a successful payment
                $existingPayment = Payment::where('invoice_id', $invoice->id)
                    ->where('status', 'success')
                    ->first();

                if (!$existingPayment) {
                    // Create payment record
                    $payment = Payment::create([
                        'tenant_id' => $tenant->id,
                        'invoice_id' => $invoice->id,
                        'provider' => 'selcom',
                        'reference' => 'SELCOM-FIX-' . now()->format('YmdHis'),
                        'amount' => $invoice->amount,
                        'status' => 'success',
                        'paid_at' => '2026-02-18 00:00:00',
                        'payment_method' => 'selcom',
                        'notes' => 'Payment received via Selcom on 18/02/2026 - manually confirmed',
                    ]);
                    $this->info("  ✓ Payment recorded: {$payment->reference}");
                } else {
                    $this->info("  ✓ Payment already exists: {$existingPayment->reference}");
                }

                // Mark invoice as paid
                $invoice->update(['status' => 'paid']);
                $this->info("  ✓ Invoice marked as paid");
            }

            // Activate the tenant
            $tenant->update(['status' => 'active']);
            $this->info("✓ Tenant status set to active");

            // Ensure subscription is active
            $subscription = Subscription::where('tenant_id', $tenant->id)->latest()->first();
            if ($subscription) {
                $subscription->update(['status' => 'active']);
                $this->info("✓ Subscription status set to active");
            }

            DB::commit();

            // Show final state
            $this->info("\n========================================");
            $this->info("SUCCESS! Tenant billing fixed");
            $this->info("========================================");
            $this->info("Tenant: {$tenant->name}");
            $this->info("Status: " . $tenant->fresh()->status);
            $this->info("Total Revenue: TZS " . number_format(
                Payment::where('tenant_id', $tenant->id)->where('status', 'success')->sum('amount')
            ));
            $this->info("Pending Invoices: " . Invoice::where('tenant_id', $tenant->id)->where('status', 'pending')->count());
            $this->info("========================================\n");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed: " . $e->getMessage());
            return 1;
        }
    }
}
