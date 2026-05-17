<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Services\Accounting\ChartOfAccountsService;

class SeedChartOfAccountsForAllTenants extends Command
{
    protected $signature   = 'accounts:seed-all-tenants';
    protected $description = 'Seed default Chart of Accounts for all existing tenants that are missing accounts';

    public function handle(): int
    {
        $service = new ChartOfAccountsService();
        $tenants = Tenant::where('status', 'active')->get();

        $this->info("Found {$tenants->count()} active tenants.");

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        $seeded = 0;
        $errors = 0;

        foreach ($tenants as $tenant) {
            try {
                $service->setupDefaultAccounts($tenant->id);
                $seeded++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed for tenant [{$tenant->id}] {$tenant->name}: {$e->getMessage()}");
                $errors++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Seeded: {$seeded} tenants. Errors: {$errors}.");

        return self::SUCCESS;
    }
}
