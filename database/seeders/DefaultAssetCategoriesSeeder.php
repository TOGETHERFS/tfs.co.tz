<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Accounting\FixedAssetCategory;
use App\Models\Tenant;

class DefaultAssetCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all active tenants
        $tenants = Tenant::where('status', 'active')->get();

        $defaultCategories = [
            // FIXED ASSETS (Non-Current Assets)
            [
                'name' => 'Furniture & Fixtures',
                'code' => 'FURN',
                'description' => 'Office furniture, desks, chairs, cabinets, shelving',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 10,
                'salvage_value_percentage' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Computer Equipment',
                'code' => 'COMP',
                'description' => 'Computers, laptops, servers, printers, scanners',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 5,
                'salvage_value_percentage' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Office Equipment',
                'code' => 'OFFC',
                'description' => 'Phones, fax machines, photocopiers, projectors',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 7,
                'salvage_value_percentage' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Vehicles',
                'code' => 'VHCL',
                'description' => 'Cars, motorcycles, trucks, delivery vehicles',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 8,
                'salvage_value_percentage' => 15,
                'is_active' => true,
            ],
            [
                'name' => 'Buildings',
                'code' => 'BLDG',
                'description' => 'Office buildings, warehouses, structures',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 40,
                'salvage_value_percentage' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Land',
                'code' => 'LAND',
                'description' => 'Land, plots, property (not depreciated)',
                'depreciation_method' => 'none',
                'useful_life_years' => 0,
                'salvage_value_percentage' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Machinery & Equipment',
                'code' => 'MACH',
                'description' => 'Industrial machinery, tools, equipment',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 12,
                'salvage_value_percentage' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Leasehold Improvements',
                'code' => 'LEAS',
                'description' => 'Improvements to leased property',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 10,
                'salvage_value_percentage' => 0,
                'is_active' => true,
            ],
            
            // CURRENT ASSETS (No depreciation)
            [
                'name' => 'Cash in Hand',
                'code' => 'CASH-H',
                'description' => 'Physical cash, petty cash',
                'depreciation_method' => 'none',
                'useful_life_years' => 0,
                'salvage_value_percentage' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Cash at Bank',
                'code' => 'CASH-B',
                'description' => 'Bank accounts, checking accounts, savings accounts',
                'depreciation_method' => 'none',
                'useful_life_years' => 0,
                'salvage_value_percentage' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Accounts Receivable (Debtors)',
                'code' => 'AR',
                'description' => 'Money owed by customers, debtors',
                'depreciation_method' => 'none',
                'useful_life_years' => 0,
                'salvage_value_percentage' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Debtors',
                'code' => 'DEBT',
                'description' => 'Money owed to the business',
                'depreciation_method' => 'none',
                'useful_life_years' => 0,
                'salvage_value_percentage' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Inventory',
                'code' => 'INV',
                'description' => 'Stock, goods for sale, raw materials',
                'depreciation_method' => 'none',
                'useful_life_years' => 0,
                'salvage_value_percentage' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Prepaid Expenses',
                'code' => 'PREP',
                'description' => 'Prepaid rent, insurance, subscriptions',
                'depreciation_method' => 'none',
                'useful_life_years' => 0,
                'salvage_value_percentage' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Short-term Investments',
                'code' => 'ST-INV',
                'description' => 'Marketable securities, short-term deposits',
                'depreciation_method' => 'none',
                'useful_life_years' => 0,
                'salvage_value_percentage' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Other Current Assets',
                'code' => 'OTH-CA',
                'description' => 'Other current assets not listed above',
                'depreciation_method' => 'none',
                'useful_life_years' => 0,
                'salvage_value_percentage' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Other Fixed Assets',
                'code' => 'OTH-FA',
                'description' => 'Other fixed assets not listed above',
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 5,
                'salvage_value_percentage' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($tenants as $tenant) {
            foreach ($defaultCategories as $category) {
                // Check if category already exists for this tenant
                $exists = FixedAssetCategory::where('tenant_id', $tenant->id)
                    ->where('name', $category['name'])
                    ->exists();

                if (!$exists) {
                    FixedAssetCategory::create(array_merge($category, [
                        'tenant_id' => $tenant->id,
                    ]));
                }
            }
        }

        $this->command->info('Default asset categories created for all active tenants.');
    }
}
