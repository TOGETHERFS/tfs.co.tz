<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\LoanProduct;

class LoanProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Resolve target tenant: prefer existing 'phidtech', else first, else create
        $tenant = Tenant::where('slug', 'phidtech')->first()
            ?? Tenant::first()
            ?? Tenant::firstOrCreate(
                ['slug' => 'phidtech'],
                [
                    'name' => 'PhidTech',
                    'status' => 'active',
                ]
            );

        $now = now();

        $products = [
            [
                'name' => 'Individual Loan',
                'description' => 'Standard loan for individual borrowers.',
                'min_amount' => 100000,
                'max_amount' => 20000000,
                'interest_rate' => 3.5,
                'interest_type' => 'flat',
                'min_term' => 3,
                'max_term' => 12,
                'processing_fee' => 1.0,
                'is_active' => true,
            ],
            [
                'name' => 'Group Loan',
                'description' => 'Loan tailored for borrower groups.',
                'min_amount' => 300000,
                'max_amount' => 50000000,
                'interest_rate' => 3.5,
                'interest_type' => 'flat',
                'min_term' => 6,
                'max_term' => 24,
                'processing_fee' => 1.0,
                'is_active' => true,
            ],
            [
                'name' => 'Business Loan',
                'description' => 'Loan for business expansion and working capital.',
                'min_amount' => 500000,
                'max_amount' => 100000000,
                'interest_rate' => 3.5,
                'interest_type' => 'reducing_balance',
                'min_term' => 6,
                'max_term' => 36,
                'processing_fee' => 1.5,
                'is_active' => true,
            ],
            [
                'name' => 'Emergency Loan',
                'description' => 'Short-term loan for emergencies.',
                'min_amount' => 50000,
                'max_amount' => 2000000,
                'interest_rate' => 3.5,
                'interest_type' => 'flat',
                'min_term' => 1,
                'max_term' => 6,
                'processing_fee' => 0.5,
                'is_active' => true,
            ],
            [
                'name' => 'Staff Loan',
                'description' => 'Preferential loan product for staff members.',
                'min_amount' => 100000,
                'max_amount' => 10000000,
                'interest_rate' => 3.5,
                'interest_type' => 'flat',
                'min_term' => 6,
                'max_term' => 24,
                'processing_fee' => 0.5,
                'is_active' => true,
            ],
            [
                'name' => 'Other Loan Product',
                'description' => 'Generic loan product for special cases.',
                'min_amount' => 100000,
                'max_amount' => 20000000,
                'interest_rate' => 3.5,
                'interest_type' => 'flat',
                'min_term' => 3,
                'max_term' => 12,
                'processing_fee' => 1.0,
                'is_active' => true,
            ],
            [
                'name' => 'Transport Loan',
                'description' => 'Loan for purchase or repair of personal or commercial transport.',
                'min_amount' => 100000,
                'max_amount' => 20000000,
                'interest_rate' => 3.5,
                'interest_type' => 'flat',
                'min_term' => 3,
                'max_term' => 24,
                'processing_fee' => 1.0,
                'is_active' => true,
            ],
            [
                'name' => 'Vacation Loan',
                'description' => 'Loan to cover travel and holiday expenses.',
                'min_amount' => 50000,
                'max_amount' => 5000000,
                'interest_rate' => 3.5,
                'interest_type' => 'flat',
                'min_term' => 1,
                'max_term' => 12,
                'processing_fee' => 1.0,
                'is_active' => true,
            ],
            [
                'name' => 'Funeral Loan',
                'description' => 'Short-term loan to assist with funeral and burial expenses.',
                'min_amount' => 50000,
                'max_amount' => 3000000,
                'interest_rate' => 3.5,
                'interest_type' => 'flat',
                'min_term' => 1,
                'max_term' => 6,
                'processing_fee' => 0.5,
                'is_active' => true,
            ],
            [
                'name' => 'Business Start Up Loan',
                'description' => 'Loan designed to help entrepreneurs start a new business.',
                'min_amount' => 500000,
                'max_amount' => 50000000,
                'interest_rate' => 3.5,
                'interest_type' => 'reducing_balance',
                'min_term' => 6,
                'max_term' => 36,
                'processing_fee' => 1.5,
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            LoanProduct::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => $product['name'],
                ],
                array_merge($product, [
                    'tenant_id' => $tenant->id,
                    'updated_at' => $now,
                ])
            );
        }
    }
}