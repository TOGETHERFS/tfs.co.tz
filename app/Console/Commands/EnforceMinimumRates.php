<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LoanProduct;

class EnforceMinimumRates extends Command
{
    protected $signature = 'loan-products:enforce-minimums 
                            {--tenant= : Specific tenant ID}
                            {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Ensure all loan products meet minimum requirements (interest_rate >= 1%, min_amount >= 5000, etc.)';

    public function handle()
    {
        $tenantId = $this->option('tenant');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $query = LoanProduct::withoutGlobalScopes();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->error('No loan products found.');
            return 1;
        }

        $this->info("Checking {$products->count()} product(s)...\n");

        $updated = 0;
        $issues = [];

        foreach ($products as $product) {
            $changes = [];

            // Ensure interest rate is at least 1%
            if ($product->interest_rate < 1) {
                $changes['interest_rate'] = ['from' => $product->interest_rate, 'to' => 1];
            }

            // Ensure min_amount is at least 5000
            if ($product->min_amount < 5000) {
                $changes['min_amount'] = ['from' => $product->min_amount, 'to' => 5000];
            }

            // Ensure min_term is at least 1
            if ($product->min_term < 1) {
                $changes['min_term'] = ['from' => $product->min_term, 'to' => 1];
            }

            // Ensure processing_fee is at least 0
            if ($product->processing_fee < 0) {
                $changes['processing_fee'] = ['from' => $product->processing_fee, 'to' => 0];
            }

            // Ensure processing_fee_type is set
            if (empty($product->processing_fee_type)) {
                $changes['processing_fee_type'] = ['from' => 'null', 'to' => 'percentage'];
            }

            if (!empty($changes)) {
                $issues[] = [
                    'product' => $product,
                    'changes' => $changes
                ];

                if (!$dryRun) {
                    $updateData = [];
                    foreach ($changes as $field => $change) {
                        $updateData[$field] = $change['to'];
                    }
                    $product->update($updateData);
                    $updated++;
                }
            }
        }

        // Display results
        if (!empty($issues)) {
            $this->info("Found " . count($issues) . " product(s) needing updates:\n");

            foreach ($issues as $issue) {
                $product = $issue['product'];
                $this->line("Product: {$product->name} (ID: {$product->id})");
                foreach ($issue['changes'] as $field => $change) {
                    $this->line("  - {$field}: {$change['from']} → {$change['to']}");
                }
                $this->newLine();
            }

            if (!$dryRun) {
                $this->info("✓ Updated {$updated} product(s)");
            } else {
                $this->info("Would update " . count($issues) . " product(s)");
            }
        } else {
            $this->info("✓ All products meet minimum requirements!");
        }

        return 0;
    }
}
