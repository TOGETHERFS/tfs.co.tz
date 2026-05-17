<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\LoanProduct;
use App\Services\TenantOnboardingService;

class SyncLoanProducts extends Command
{
    protected $signature = 'tenants:sync-loan-products 
                            {--tenant= : Specific tenant ID to sync}
                            {--activate-all : Activate all existing inactive products}
                            {--update-minimums : Update all products with new minimum values (min_amount=5000, min_term=1, processing_fee=0)}
                            {--dry-run : Show what would be synced without making changes}';

    protected $description = 'Sync default loan products for all tenants (adds missing products)';

    public function handle()
    {
        $tenantId = $this->option('tenant');
        $activateAll = $this->option('activate-all');
        $updateMinimums = $this->option('update-minimums');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $query = Tenant::query();
        if ($tenantId) {
            $query->where('id', $tenantId);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');
            return 1;
        }

        $this->info("Processing {$tenants->count()} tenant(s)...\n");

        $totalCreated = 0;
        $totalActivated = 0;
        $totalUpdated = 0;

        // Update minimums for ALL products across all tenants if requested
        if ($updateMinimums && !$dryRun) {
            $query = LoanProduct::withoutGlobalScopes();
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
            
            $totalUpdated = $query->update([
                'min_amount' => 5000,
                'min_term' => 1,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
            ]);
            
            $this->info("✓ Updated {$totalUpdated} product(s) with new minimum values");
            $this->line("  - min_amount: 5000 TZS");
            $this->line("  - min_term: 1 month");
            $this->line("  - processing_fee: 0");
            $this->line("  - processing_fee_type: percentage");
            $this->newLine();
        } elseif ($updateMinimums && $dryRun) {
            $query = LoanProduct::withoutGlobalScopes();
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
            $count = $query->count();
            $this->info("Would update {$count} product(s) with new minimum values");
            $this->newLine();
        }

        foreach ($tenants as $tenant) {
            $this->info("Tenant: {$tenant->name} (ID: {$tenant->id})");

            // Get current product count
            $existingProducts = LoanProduct::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->pluck('name')
                ->toArray();

            $this->line("  Current products: " . count($existingProducts));

            if (!$dryRun) {
                // Seed default products (uses firstOrCreate, so won't duplicate)
                $products = TenantOnboardingService::seedDefaultLoanProducts($tenant);
                
                $newCount = count($products) - count($existingProducts);
                if ($newCount > 0) {
                    $this->line("  ✓ Created {$newCount} new product(s)");
                    $totalCreated += $newCount;
                }

                // Activate all products if requested
                if ($activateAll) {
                    $activated = LoanProduct::withoutGlobalScopes()
                        ->where('tenant_id', $tenant->id)
                        ->where('is_active', false)
                        ->update(['is_active' => true]);
                    
                    if ($activated > 0) {
                        $this->line("  ✓ Activated {$activated} product(s)");
                        $totalActivated += $activated;
                    }
                }
            } else {
                // Calculate what would be created
                $defaultNames = [
                    'Group Loan', 'Other Loan Product', 'Staff Loan',
                    'Agricultural Loan', 'Business Loan', 'Individual Loan',
                    'Asset Loan', 'Emergency Loan',
                    'Transport Loan', 'Vacation Loan', 'Funeral Loan', 'Business Start-Up Loan',
                ];
                $missingProducts = array_diff($defaultNames, $existingProducts);
                
                if (!empty($missingProducts)) {
                    $this->line("  Would create: " . implode(', ', $missingProducts));
                }

                if ($activateAll) {
                    $inactiveCount = LoanProduct::withoutGlobalScopes()
                        ->where('tenant_id', $tenant->id)
                        ->where('is_active', false)
                        ->count();
                    if ($inactiveCount > 0) {
                        $this->line("  Would activate {$inactiveCount} product(s)");
                    }
                }
            }

            $this->newLine();
        }

        $this->info("Summary:");
        $action = $dryRun ? 'would be' : '';
        if ($updateMinimums) {
            $this->line("  Products {$action} updated with new minimums: {$totalUpdated}");
        }
        $this->line("  Products {$action} created: {$totalCreated}");
        if ($activateAll) {
            $this->line("  Products {$action} activated: {$totalActivated}");
        }

        return 0;
    }
}
