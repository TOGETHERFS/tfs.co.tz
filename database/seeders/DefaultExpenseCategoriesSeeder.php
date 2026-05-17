<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Accounting\ExpenseCategory;
use App\Models\Tenant;

class DefaultExpenseCategoriesSeeder extends Seeder
{
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

    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            foreach ($this->defaultCategories as $cat) {
                ExpenseCategory::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => $cat['name']],
                    ['code' => $cat['code'], 'is_active' => true]
                );
            }
        }

        $this->command->info('Default expense categories seeded for all tenants.');
    }
}
