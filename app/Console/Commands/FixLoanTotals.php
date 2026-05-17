<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FixLoanTotals extends Command
{
    protected $signature = 'loans:fix-totals';
    protected $description = 'Fix loan total amounts and regenerate schedules with correct calculations';

    public function handle()
    {
        $this->info('Fixing loan total amounts...');
        
        $loans = Loan::whereIn('status', ['pending', 'approved', 'disbursed', 'active'])->get();
        
        $this->info("Found {$loans->count()} loans to process");
        
        $bar = $this->output->createProgressBar($loans->count());
        $bar->start();
        
        foreach ($loans as $loan) {
            DB::transaction(function () use ($loan) {
                $rMonthly = 0.10; // 10% per month
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
                    
                    $totalInterest = round($loan->principal * $rMonthly * $termInMonths, 2);
                    $interestPerInstallment = round($totalInterest / $loan->term, 2);
                    $principalPerPayment = round($loan->principal / $loan->term, 2);
                    $monthlyPayment = $principalPerPayment + $interestPerInstallment;
                } else {
                    $monthlyPayment = round($loan->principal * $rMonthly / (1 - pow(1 + $rMonthly, -$loan->term)), 2);
                }
                
                $totalAmount = round($monthlyPayment * $loan->term, 2);
                
                $loan->update([
                    'monthly_payment' => $monthlyPayment,
                    'total_amount' => $totalAmount,
                ]);
                
                // Regenerate schedule
                $loan->schedules()->delete();
                
                $schedules = [];
                $paymentDate = Carbon::parse($loan->first_payment_date);
                $tenantId = $loan->tenant_id;
                $principalBalance = $loan->principal;
                
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
                
                for ($i = 1; $i <= $loan->term; $i++) {
                    if ($interestType === 'reducing' && $rMonthly > 0) {
                        $interestAmount = round($principalBalance * $rMonthly, 2);
                        $principalAmount = round($monthlyPayment - $interestAmount, 2);
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
                        'tenant_id' => $tenantId,
                        'loan_id' => $loan->id,
                        'installment_number' => $i,
                        'due_date' => $paymentDate->copy(),
                        'principal_amount' => $principalAmount,
                        'interest_amount' => $interestAmount,
                        'total_amount' => $totalPayment,
                        'paid_amount' => 0,
                        'balance' => $principalBalance,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    switch ($repaymentSchedule) {
                        case 'daily':
                            $paymentDate->addDay();
                            break;
                        case 'weekly':
                            $paymentDate->addWeek();
                            break;
                        case 'monthly':
                        default:
                            $paymentDate->addMonth();
                            break;
                    }
                }
                
                DB::table('loan_schedules')->insert($schedules);
                
                // Update schedules with existing repayments
                $repayments = $loan->repayments()->orderBy('payment_date')->get();
                
                foreach ($repayments as $repayment) {
                    $remainingAmount = $repayment->amount;
                    $pendingSchedules = $loan->schedules()->where('status', '!=', 'paid')->orderBy('due_date')->get();
                    
                    foreach ($pendingSchedules as $schedule) {
                        if ($remainingAmount <= 0) break;
                        
                        $unpaidAmount = $schedule->total_amount - $schedule->paid_amount;
                        $paymentForSchedule = min($remainingAmount, $unpaidAmount);
                        
                        $schedule->paid_amount += $paymentForSchedule;
                        $remainingAmount -= $paymentForSchedule;
                        
                        if ($schedule->paid_amount >= $schedule->total_amount) {
                            $schedule->status = 'paid';
                            $schedule->paid_date = $repayment->payment_date;
                        } elseif ($schedule->paid_amount > 0) {
                            $schedule->status = 'partial';
                        }
                        
                        $schedule->save();
                    }
                }
            });
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Successfully fixed {$loans->count()} loans!");
        
        return 0;
    }
}
