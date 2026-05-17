<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\LoanSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateLoanInterest extends Command
{
    protected $signature = 'loans:recalculate-interest {--loan= : Specific loan ID to recalculate}';
    protected $description = 'Recalculate interest and regenerate schedules for all loans using their stored interest rates';

    public function handle()
    {
        $loanId = $this->option('loan');
        
        $query = Loan::query();
        if ($loanId) {
            $query->where('id', $loanId);
        }
        
        $loans = $query->with('product')->get();
        
        $this->info("Found {$loans->count()} loan(s) to recalculate.");
        
        $bar = $this->output->createProgressBar($loans->count());
        $bar->start();
        
        $updated = 0;
        $errors = 0;
        
        foreach ($loans as $loan) {
            try {
                DB::beginTransaction();
                
                $rMonthly = $loan->interest_rate / 100;
                $interestType = optional($loan->product)->interest_type ?? 'flat';
                $repaymentSchedule = $loan->repayment_schedule ?? 'monthly';
                
                if ($interestType === 'flat') {
                    // Convert term to months based on repayment schedule
                    $termInMonths = $loan->term;
                    if ($repaymentSchedule === 'weekly') {
                        $termInMonths = $loan->term / 4;
                    } elseif ($repaymentSchedule === 'daily') {
                        $termInMonths = $loan->term / 30;
                    }
                    
                    // Calculate total interest for the entire loan period
                    $totalInterest = round($loan->principal * $rMonthly * $termInMonths, 2);
                    $interestPerInstallment = round($totalInterest / $loan->term, 2);
                    $principalPerPayment = round($loan->principal / $loan->term, 2);
                    $monthlyPayment = $principalPerPayment + $interestPerInstallment;
                } else {
                    // Reducing balance
                    if ($rMonthly > 0) {
                        $monthlyPayment = round($loan->principal * $rMonthly / (1 - pow(1 + $rMonthly, -$loan->term)), 2);
                    } else {
                        $monthlyPayment = round($loan->principal / $loan->term, 2);
                    }
                }
                
                $totalAmount = round($monthlyPayment * $loan->term, 2);
                
                // Update loan totals
                $loan->update([
                    'monthly_payment' => $monthlyPayment,
                    'total_amount' => $totalAmount,
                ]);
                
                // Delete existing schedules
                $loan->schedules()->delete();
                
                // Regenerate schedule
                $this->generateSchedule($loan, $rMonthly, $interestType, $repaymentSchedule);
                
                DB::commit();
                $updated++;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("\nError on loan #{$loan->id}: " . $e->getMessage());
                $errors++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info("Completed: {$updated} loans updated, {$errors} errors.");
        
        return 0;
    }
    
    private function generateSchedule($loan, $rMonthly, $interestType, $repaymentSchedule)
    {
        $paymentDate = $loan->first_payment_date;
        $principalBalance = $loan->principal;
        
        // For flat interest, calculate total interest based on loan duration in months
        $interestPerInstallment = 0;
        if ($interestType === 'flat') {
            $termInMonths = $loan->term;
            if ($repaymentSchedule === 'weekly') {
                $termInMonths = $loan->term / 4;
            } elseif ($repaymentSchedule === 'daily') {
                $termInMonths = $loan->term / 30;
            }
            $totalInterest = round($loan->principal * $rMonthly * $termInMonths, 2);
            $interestPerInstallment = round($totalInterest / $loan->term, 2);
        }
        
        $schedules = [];
        for ($i = 1; $i <= $loan->term; $i++) {
            if ($interestType === 'reducing' && $rMonthly > 0) {
                $interestAmount = round($principalBalance * $rMonthly, 2);
                $principalAmount = round($loan->monthly_payment - $interestAmount, 2);
                if ($principalAmount < 0) {
                    $principalAmount = 0;
                }
            } else {
                $interestAmount = $interestPerInstallment;
                $principalAmount = round($loan->principal / $loan->term, 2);
            }
            
            $totalPayment = round($principalAmount + $interestAmount, 2);
            $principalBalance = max(0, round($principalBalance - $principalAmount, 2));
            
            $schedules[] = [
                'tenant_id' => $loan->tenant_id,
                'loan_id' => $loan->id,
                'installment_number' => $i,
                'due_date' => $paymentDate,
                'principal_amount' => $principalAmount,
                'interest_amount' => $interestAmount,
                'total_amount' => $totalPayment,
                'paid_amount' => 0,
                'balance' => $principalBalance,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            // Increment payment date
            switch ($repaymentSchedule) {
                case 'daily':
                    $paymentDate = $paymentDate->copy()->addDay();
                    break;
                case 'weekly':
                    $paymentDate = $paymentDate->copy()->addWeek();
                    break;
                case 'monthly':
                default:
                    $paymentDate = $paymentDate->copy()->addMonth();
                    break;
            }
        }
        
        LoanSchedule::insert($schedules);
    }
}
