<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Normalize loan product interest types to allowed values
            DB::table('loan_products')
                ->where('interest_type', 'reducing_balance')
                ->update(['interest_type' => 'reducing']);

            // Set all loan product interest rates to fixed 10% per month
            DB::table('loan_products')->update(['interest_rate' => 10.0]);

            // Set all loan interest rates to fixed 10% per month
            DB::table('loans')->update(['interest_rate' => 10.0]);

            // Recompute monthly_payment and total_amount for pending/approved loans
            $loans = DB::table('loans')
                ->select('id','principal','term','interest_rate','monthly_payment','total_amount','first_payment_date','product_id','tenant_id','status')
                ->whereIn('status', ['pending', 'approved'])
                ->get();

            foreach ($loans as $row) {
                $monthlyRate = 0.10; // 10% per month
                $principal = (float) $row->principal;
                $term = (int) $row->term;

                $product = DB::table('loan_products')
                    ->select('interest_type')
                    ->where('id', $row->product_id)
                    ->first();
                $interestType = $product ? $product->interest_type : 'flat';

                if ($term <= 0 || $principal <= 0) {
                    continue;
                }

                if ($interestType === 'reducing') {
                    // Reducing balance PMT using monthly rate
                    $monthlyPayment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $term)) / (pow(1 + $monthlyRate, $term) - 1);
                    $totalAmount = $monthlyPayment * $term;
                } else {
                    // Flat method
                    $interestPerMonth = $principal * $monthlyRate;
                    $monthlyPayment = ($principal / $term) + $interestPerMonth;
                    $totalAmount = $monthlyPayment * $term;
                }

                DB::table('loans')->where('id', $row->id)->update([
                    'monthly_payment' => round($monthlyPayment, 2),
                    'total_amount' => round($totalAmount, 2),
                    'interest_rate' => 10.0,
                    'first_payment_date' => $row->first_payment_date ?? now()->addDays(30),
                ]);
            }
        });

        // Attempt schedule regeneration via Eloquent (safe-guarded)
        try {
            foreach (\App\Models\Loan::whereIn('status', ['pending','approved'])->get() as $loan) {
                $loan->generateSchedule();
            }
        } catch (\Throwable $e) {
            logger()->warning('Schedule regeneration skipped during migration', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank: data normalization and rate enforcement are not trivially reversible.
    }
};