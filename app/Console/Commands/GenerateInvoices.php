<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class GenerateInvoices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billing:generate-invoices 
                            {--tenant= : Specific tenant ID to process}
                            {--month= : Month to generate invoices for (YYYY-MM)}
                            {--force : Force regeneration of existing invoices}';

    /**
     * The console command description.
     */
    protected $description = 'Generate monthly subscription invoices for tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');
        $month = $this->option('month');
        $force = $this->option('force');

        // Parse month or use current month
        if ($month) {
            try {
                $billingDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            } catch (\Exception $e) {
                $this->error('Invalid month format. Use YYYY-MM (e.g., 2024-01)');
                return 1;
            }
        } else {
            $billingDate = now()->startOfMonth();
        }

        $this->info("Generating invoices for: {$billingDate->format('F Y')}");

        // Get active subscriptions
        $query = Subscription::with(['tenant', 'plan'])
                             ->where('status', 'active');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $subscriptions = $query->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No active subscriptions found.');
            return 0;
        }

        $this->info("Found {$subscriptions->count()} active subscriptions.");

        $bar = $this->output->createProgressBar($subscriptions->count());
        $bar->start();

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                // Check if invoice already exists for this period
                $existingInvoice = Invoice::where('tenant_id', $subscription->tenant_id)
                                         ->where('subscription_id', $subscription->id)
                                         ->whereYear('billing_period_start', $billingDate->year)
                                         ->whereMonth('billing_period_start', $billingDate->month)
                                         ->first();

                if ($existingInvoice && !$force) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Delete existing invoice if force regeneration
                if ($existingInvoice && $force) {
                    $existingInvoice->delete();
                }

                // Calculate billing period
                $periodStart = $billingDate->copy();
                $periodEnd = $billingDate->copy()->endOfMonth();

                // Calculate amount based on subscription plan and branch count
                $amount = $this->calculateInvoiceAmount($subscription, $periodStart, $periodEnd);

                // Generate invoice
                $invoice = Invoice::create([
                    'tenant_id' => $subscription->tenant_id,
                    // Align to existing schema: use 'number' and include plan_id
                    'plan_id' => $subscription->plan_id,
                    'number' => $this->generateInvoiceNumber($subscription->tenant_id, $billingDate),
                    'amount' => $amount,
                    'currency' => 'TZS',
                    'status' => 'pending',
                    'due_date' => $periodStart->copy()->addDays(30),
                ]);

                $generated++;

            } catch (\Exception $e) {
                $this->error("Failed to generate invoice for tenant {$subscription->tenant_id}: " . $e->getMessage());
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Invoices generated: {$generated}");
        
        if ($skipped > 0) {
            $this->info("Invoices skipped (already exist): {$skipped}");
        }
        
        if ($failed > 0) {
            $this->warn("Failed to generate: {$failed}");
        }

        return 0;
    }

    /**
     * Calculate invoice amount based on subscription and billing period
     */
    private function calculateInvoiceAmount(Subscription $subscription, Carbon $periodStart, Carbon $periodEnd): float
    {
        $plan = $subscription->plan;

        // Determine monthly equivalent based on plan period
        $period = strtolower((string) ($plan->period ?? 'month'));
        $price = (float) ($plan->price ?? 0);

        $monthlyPerBranch = in_array($period, ['year', 'annual', 'yearly'])
            ? ($price / 12.0)
            : $price;

        // Branch count from cached settings (fallback to 1)
        $branchCount = $this->getBranchCount();

        return $monthlyPerBranch * $branchCount;
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(int $tenantId, Carbon $billingDate): string
    {
        $prefix = 'INV';
        $year = $billingDate->format('Y');
        $month = $billingDate->format('m');
        
        // Get next sequence number for this tenant and month
        $lastInvoice = Invoice::where('tenant_id', $tenantId)
                             ->where('number', 'like', "{$prefix}-{$year}{$month}-%")
                             ->orderBy('number', 'desc')
                             ->first();

        $sequence = 1;
        if ($lastInvoice) {
            $lastSequence = (int) substr($lastInvoice->number, -4);
            $sequence = $lastSequence + 1;
        }

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }

    /**
     * Get the branch count from cached settings.
     * Uses the same cache key pattern as SettingsController.
     */
    private function getBranchCount(): int
    {
        $branches = Cache::get('setting_branches', []);
        if (is_string($branches)) {
            $decoded = json_decode($branches, true);
            $branches = is_array($decoded) ? $decoded : [];
        }

        $count = is_array($branches) ? count($branches) : 0;
        return max(1, $count);
    }
}