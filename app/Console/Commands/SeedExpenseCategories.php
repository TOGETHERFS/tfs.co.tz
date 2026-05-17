<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Accounting\ExpenseCategory;
use App\Models\Tenant;

class SeedExpenseCategories extends Command
{
    protected $signature = 'expense:seed-categories';
    protected $description = 'Seed default expense categories for all tenants';

    protected array $defaultCategories = [
        ['name' => 'Salaries & Wages',       'code' => 'SAL'],
        ['name' => 'Rent & Utilities',        'code' => 'RNT'],
        ['name' => 'Office Supplies',         'code' => 'OFC'],
        ['name' => 'Travel & Transport',      'code' => 'TRV'],
        ['name' => 'Communication',           'code' => 'COM'],
        ['name' => 'Marketing & Advertising', 'code' => 'MKT'],
        ['name' => 'Maintenance & Repairs',   'code' => 'MNT'],
        ['name' => 'Professional Fees',       'code' => 'PRF'],
        ['name' => 'Insurance',               'code' => 'INS'],
        ['name' => 'Bank Charges',            'code' => 'BNK'],
        ['name' => 'Loan Disbursement Cost',  'code' => 'LDC'],
        ['name' => 'Staff Training',          'code' => 'TRN'],
        ['name' => 'Miscellaneous',           'code' => 'MSC'],
    ];

    public function handle()
    {
        $tenants = Tenant::all();
        $this->info("Seeding default expense categories for {$tenants->count()} tenant(s)...");

        foreach ($tenants as $tenant) {
            $added = 0;
            foreach ($this->defaultCategories as $cat) {
                $created = ExpenseCategory::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => $cat['name']],
                    ['code' => $cat['code'], 'is_active' => true]
                );
                if ($created->wasRecentlyCreated) {
                    $added++;
                }
            }
            $this->info("  {$tenant->name}: {$added} new categories added");
        }

        $this->info("\nDone! Default expense categories seeded successfully.");
        return 0;
    }
}
