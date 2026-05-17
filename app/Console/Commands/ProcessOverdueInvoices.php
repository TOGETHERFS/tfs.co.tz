<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ProcessOverdueInvoices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billing:process-overdue 
                            {--tenant= : Specific tenant ID to process}
                            {--grace-days=7 : Grace period in days before suspension}
                            {--dry-run : Show what would be processed without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Process overdue invoices and suspend subscriptions if necessary';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');
        $graceDays = (int) $this->option('grace-days');
        $dryRun = $this->option('dry-run');

        $this->info("Processing overdue invoices...");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }

        // Get overdue invoices
        $query = Invoice::with(['tenant', 'subscription'])
                        ->where('status', 'pending')
                        ->where('due_date', '<', now()->subDays($graceDays));

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $overdueInvoices = $query->get();

        if ($overdueInvoices->isEmpty()) {
            $this->info('No overdue invoices found.');
            return 0;
        }

        $this->info("Found {$overdueInvoices->count()} overdue invoices.");

        $bar = $this->output->createProgressBar($overdueInvoices->count());
        $bar->start();

        $processed = 0;
        $suspended = 0;
        $failed = 0;

        foreach ($overdueInvoices as $invoice) {
            try {
                $tenant = $invoice->tenant;
                $subscription = $invoice->subscription;

                if (!$tenant || !$subscription) {
                    $this->warn("Missing tenant or subscription for invoice {$invoice->invoice_number}");
                    $failed++;
                    $bar->advance();
                    continue;
                }

                $daysOverdue = now()->diffInDays($invoice->due_date);

                $this->line("Processing invoice {$invoice->invoice_number} - {$daysOverdue} days overdue");

                if (!$dryRun) {
                    // Mark invoice as overdue
                    $invoice->update([
                        'status' => 'overdue',
                        'overdue_since' => $invoice->overdue_since ?: now()
                    ]);

                    // Suspend subscription if it's still active
                    if ($subscription->status === 'active') {
                        $subscription->update([
                            'status' => 'suspended',
                            'suspended_at' => now(),
                            'suspension_reason' => "Overdue payment - Invoice {$invoice->invoice_number}"
                        ]);

                        $suspended++;
                        $this->warn("Suspended subscription for tenant: {$tenant->name}");
                    }

                    // Update tenant status
                    if ($tenant->status === 'active') {
                        $tenant->update([
                            'status' => 'suspended',
                            'suspended_at' => now()
                        ]);
                    }
                }

                $processed++;

            } catch (\Exception $e) {
                $this->error("Failed to process invoice {$invoice->invoice_number}: " . $e->getMessage());
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($dryRun) {
            $this->info("Would process {$processed} overdue invoices");
            $this->info("Would suspend {$suspended} subscriptions");
        } else {
            $this->info("Processed overdue invoices: {$processed}");
            $this->info("Suspended subscriptions: {$suspended}");
        }
        
        if ($failed > 0) {
            $this->warn("Failed to process: {$failed}");
        }

        // Send summary report
        $this->sendSummaryReport($processed, $suspended, $failed, $dryRun);

        return 0;
    }

    /**
     * Send summary report to administrators
     */
    private function sendSummaryReport(int $processed, int $suspended, int $failed, bool $dryRun): void
    {
        // This could be enhanced to send email notifications to administrators
        $this->info("Summary report:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Invoices Processed', $processed],
                ['Subscriptions Suspended', $suspended],
                ['Failed Operations', $failed],
                ['Mode', $dryRun ? 'Dry Run' : 'Live'],
                ['Processed At', now()->format('Y-m-d H:i:s')]
            ]
        );
    }
}