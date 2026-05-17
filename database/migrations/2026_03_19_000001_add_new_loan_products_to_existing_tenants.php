<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $newProducts = [
        [
            'name'                 => 'Transport Loan',
            'description'          => 'Loan for purchasing or repairing vehicles and transport equipment.',
            'min_amount'           => 5000,
            'max_amount'           => 100000000,
            'interest_rate'        => 15.0,
            'interest_type'        => 'reducing_balance',
            'min_term'             => 1,
            'max_term'             => 48,
            'processing_fee'       => 0,
            'processing_fee_type'  => 'percentage',
            'is_active'            => 1,
        ],
        [
            'name'                 => 'Vacation Loan',
            'description'          => 'Loan to finance travel and holiday expenses.',
            'min_amount'           => 5000,
            'max_amount'           => 10000000,
            'interest_rate'        => 12.0,
            'interest_type'        => 'flat',
            'min_term'             => 1,
            'max_term'             => 12,
            'processing_fee'       => 0,
            'processing_fee_type'  => 'percentage',
            'is_active'            => 1,
        ],
        [
            'name'                 => 'Funeral Loan',
            'description'          => 'Short-term loan to cover funeral and burial expenses.',
            'min_amount'           => 5000,
            'max_amount'           => 5000000,
            'interest_rate'        => 10.0,
            'interest_type'        => 'flat',
            'min_term'             => 1,
            'max_term'             => 6,
            'processing_fee'       => 0,
            'processing_fee_type'  => 'percentage',
            'is_active'            => 1,
        ],
        [
            'name'                 => 'Business Start-Up Loan',
            'description'          => 'Loan to help entrepreneurs launch new businesses.',
            'min_amount'           => 5000,
            'max_amount'           => 50000000,
            'interest_rate'        => 15.0,
            'interest_type'        => 'flat',
            'min_term'             => 1,
            'max_term'             => 36,
            'processing_fee'       => 0,
            'processing_fee_type'  => 'percentage',
            'is_active'            => 1,
        ],
    ];

    public function up(): void
    {
        $now = now();
        $tenants = DB::table('tenants')->pluck('id');

        foreach ($tenants as $tenantId) {
            foreach ($this->newProducts as $product) {
                $exists = DB::table('loan_products')
                    ->where('tenant_id', $tenantId)
                    ->where('name', $product['name'])
                    ->exists();

                if (!$exists) {
                    DB::table('loan_products')->insert(array_merge($product, [
                        'tenant_id'  => $tenantId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]));
                }
            }
        }
    }

    public function down(): void
    {
        $names = array_column($this->newProducts, 'name');
        DB::table('loan_products')->whereIn('name', $names)->delete();
    }
};
