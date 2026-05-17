<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add repayment_type column to loan_products
        Schema::table('loan_products', function (Blueprint $table) {
            $table->enum('repayment_type', ['amortized', 'interest_only'])
                  ->default('amortized')
                  ->after('interest_type')
                  ->comment('amortized=normal principal+interest; interest_only=interest each period, principal on last installment');
        });

        // Seed Special Loan and Agriculture Loan for every tenant
        $now = now();
        $newProducts = [
            [
                'name'                => 'Special Loan',
                'description'         => 'Interest-only repayments each period; full principal repaid on the final installment.',
                'min_amount'          => 5000,
                'max_amount'          => 500000000,
                'interest_rate'       => 15.0,
                'interest_type'       => 'flat',
                'repayment_type'      => 'interest_only',
                'min_term'            => 1,
                'max_term'            => 36,
                'processing_fee'      => 0,
                'processing_fee_type' => 'percentage',
                'is_active'           => 1,
            ],
            [
                'name'                => 'Agriculture Loan',
                'description'         => 'Interest-only seasonal loan — borrower pays interest each period and principal on the final installment.',
                'min_amount'          => 5000,
                'max_amount'          => 500000000,
                'interest_rate'       => 12.0,
                'interest_type'       => 'flat',
                'repayment_type'      => 'interest_only',
                'min_term'            => 1,
                'max_term'            => 36,
                'processing_fee'      => 0,
                'processing_fee_type' => 'percentage',
                'is_active'           => 1,
            ],
        ];

        $tenants = DB::table('tenants')->pluck('id');
        foreach ($tenants as $tenantId) {
            foreach ($newProducts as $product) {
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
        DB::table('loan_products')->whereIn('name', ['Special Loan', 'Agriculture Loan'])->delete();

        Schema::table('loan_products', function (Blueprint $table) {
            $table->dropColumn('repayment_type');
        });
    }
};
