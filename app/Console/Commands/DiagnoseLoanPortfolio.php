<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Repayment;

class DiagnoseLoanPortfolio extends Command
{
    protected $signature = 'diagnose:loan-portfolio';
    protected $description = 'Diagnose loan portfolio calculation issues';

    public function handle()
    {
        $this->info('=== LOAN PORTFOLIO ISSUE DIAGNOSIS ===');
        $this->newLine();

        // Get a sample active loan
        $loan = Loan::whereIn('status', ['disbursed', 'active'])->first();

        if (!$loan) {
            $this->warn('No active loans found. Creating test data...');
            
            // Create a test loan if none exists
            $client = \App\Models\Client::first();
            $product = \App\Models\LoanProduct::first();
            
            if ($client && $product) {
                $loan = Loan::create([
                    'loan_number' => 'TEST001',
                    'client_id' => $client->id,
                    'product_id' => $product->id,
                    'user_id' => 1,
                    'principal' => 1000000,
                    'interest_rate' => 10,
                    'term' => 12,
                    'processing_fee' => 10000,
                    'total_amount' => 1120000, // Principal + Interest
                    'monthly_payment' => 93333.33,
                    'first_payment_date' => now()->addDays(30),
                    'status' => 'active',
                    'outstanding_balance' => 1120000
                ]);
                
                $this->info("Created test loan #{$loan->id}");
            } else {
                $this->error('No client or product found. Please create test data first.');
                return 1;
            }
        }

        $this->info("Analyzing Loan #{$loan->id}:");
        $this->line("  Loan Number: {$loan->loan_number}");
        $this->line("  Principal: " . number_format($loan->principal, 2));
        $this->line("  Total Amount: " . number_format($loan->total_amount, 2));
        $this->line("  Outstanding Balance: " . number_format($loan->outstanding_balance, 2));
        $this->line("  Status: {$loan->status}");
        $this->newLine();

        // Check schedules
        $schedules = $loan->schedules()->orderBy('installment_number')->get();
        $this->info("Schedules ({$schedules->count()} found):");
        foreach ($schedules as $schedule) {
            $this->line("  Installment #{$schedule->installment_number}: ");
            $this->line("    Due Date: {$schedule->due_date}");
            $this->line("    Principal: " . number_format($schedule->principal_amount, 2));
            $this->line("    Interest: " . number_format($schedule->interest_amount, 2));
            $this->line("    Total: " . number_format($schedule->total_amount, 2));
            $this->line("    Paid: " . number_format($schedule->paid_amount, 2));
            $this->line("    Status: {$schedule->status}");
            $this->line("    ---");
        }

        // Check repayments
        $repayments = $loan->repayments;
        $this->newLine();
        $this->info("Repayments ({$repayments->count()} found):");
        $totalRepaid = 0;
        foreach ($repayments as $repayment) {
            $this->line("  Payment on {$repayment->payment_date}: " . number_format($repayment->amount, 2));
            $totalRepaid += $repayment->amount;
        }
        $this->line("  Total Repaid: " . number_format($totalRepaid, 2));
        $this->newLine();

        // Calculate what dashboard should show
        $this->info('=== DASHBOARD CALCULATION COMPARISON ===');

        // Method 1: Using outstanding_balance (OLD METHOD - INCORRECT)
        $method1 = $loan->outstanding_balance;
        $this->line("Method 1 (outstanding_balance): " . number_format($method1, 2));

        // Method 2: Using total_amount - paid (CORRECT METHOD)
        $method2 = max(0, $loan->total_amount - $totalRepaid);
        $this->line("Method 2 (total_amount - paid): " . number_format($method2, 2));

        // Method 3: Using schedule totals (MOST ACCURATE)
        $scheduleTotal = $schedules->sum('total_amount');
        $schedulePaid = $schedules->sum('paid_amount');
        $method3 = max(0, $scheduleTotal - $schedulePaid);
        $this->line("Method 3 (schedule totals): " . number_format($method3, 2));

        $this->newLine();
        $this->info('=== PORTFOLIO SUMMARY ===');
        $activeLoans = Loan::whereIn('status', ['disbursed', 'active'])->get();

        // OLD method (incorrect)
        $oldPortfolio = $activeLoans->sum('outstanding_balance');
        $this->line("OLD Portfolio Value (outstanding_balance): " . number_format($oldPortfolio, 2));

        // NEW method (correct)
        $newPortfolio = $activeLoans->sum(function($loan) {
            $totalPaid = $loan->repayments()->sum('amount');
            return max(0, $loan->total_amount - $totalPaid);
        });
        $this->line("NEW Portfolio Value (total_amount - paid): " . number_format($newPortfolio, 2));

        $difference = $newPortfolio - $oldPortfolio;
        $this->line("Difference: " . number_format($difference, 2) . " (" . round(($difference / max($oldPortfolio, 1)) * 100, 2) . "%)");

        $this->newLine();
        $this->info('=== RECOMMENDATION ===');
        if (abs($difference) > 1000) {  // If difference is significant
            $this->error('CRITICAL: Portfolio values are significantly different!');
            $this->line('   You should run the fix commands:');
            $this->line('   php artisan fix:loan-totals');
            $this->line('   php artisan regenerate:loan-schedules');
        } else {
            $this->info('Portfolio values are consistent');

            // Check if schedules have proper interest data
            $missingInterest = $schedules->filter(function($s) {
                return $s->interest_amount <= 0 && $s->total_amount > $s->principal_amount;
            })->count();
            
            if ($missingInterest > 0) {
                $this->warn("WARNING: {$missingInterest} schedules have missing interest data");
                $this->line('   Run: php artisan regenerate:loan-schedules');
            } else {
                $this->info('All schedules have proper interest data');
            }
        }

        $this->newLine();
        $this->info('=== DONE ===');
        return 0;
    }
}