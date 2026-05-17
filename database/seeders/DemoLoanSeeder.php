<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\Client;
use App\Models\User;
use App\Models\LoanProduct;
use App\Models\Loan;

class DemoLoanSeeder extends Seeder
{
    /**
     * Seed a demo pending loan for staged approval testing.
     */
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'phidtech-demo')->first() ?? Tenant::first();
        if (!$tenant) {
            $this->command?->warn('No tenant found; skipping DemoLoanSeeder.');
            return;
        }

        $officer = User::where('tenant_id', $tenant->id)->where('email', 'user@phidtech.com')->first()
            ?? User::where('tenant_id', $tenant->id)->first();
        if (!$officer) {
            $this->command?->warn('No user found for tenant; skipping DemoLoanSeeder.');
            return;
        }

        $product = LoanProduct::where('tenant_id', $tenant->id)->first();
        if (!$product) {
            $this->command?->warn('No loan product found; skipping DemoLoanSeeder.');
            return;
        }

        // Minimal demo client
        $client = Client::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'phone' => '+255700000001',
            ],
            [
                'first_name' => 'Demo',
                'last_name' => 'Client',
                'email' => 'demo.client@example.com',
                'status' => 'active',
            ]
        );

        // Unique loan number per tenant
        $base = 'DEMO-' . now()->format('Ymd');
        $loanNumber = $base;
        $counter = 1;
        while (Loan::where('tenant_id', $tenant->id)->where('loan_number', $loanNumber)->exists()) {
            $counter++;
            $loanNumber = $base . '-' . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
        }

        $principal = 1000000; // TSHS 1,000,000
        $term = 6;
        $monthlyRate = 0.10; // 10% per month
        $interestType = $product->interest_type ?? 'flat';
        if ($interestType === 'reducing') {
            $monthlyPayment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $term)) / (pow(1 + $monthlyRate, $term) - 1);
        } else {
            $monthlyPayment = ($principal / $term) + ($principal * $monthlyRate);
        }
        $totalAmount = $monthlyPayment * $term;

        $loan = Loan::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'loan_number' => $loanNumber,
            ],
            [
                'tenant_id' => $tenant->id,
                'client_id' => $client->id,
                'product_id' => $product->id,
                'user_id' => $officer->id,
                'principal' => $principal,
                'interest_rate' => 10.0,
                'term' => $term,
                'monthly_payment' => round($monthlyPayment, 2),
                'total_amount' => round($totalAmount, 2),
                'processing_fee' => 0,
                'first_payment_date' => now()->addDays(30),
                'status' => 'pending',
                'approval_stage' => 'cso_review',
                'approval_stage_status' => 'pending',
            ]
        );

        try {
            $loan->generateSchedule();
        } catch (\Throwable $e) {
            // ignore schedule generation errors in seeding
        }

        $this->command?->info("Demo loan seeded: {$loan->loan_number} for tenant {$tenant->slug}");
    }
}