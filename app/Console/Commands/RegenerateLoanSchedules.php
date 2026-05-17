<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Loan;
use App\Models\LoanSchedule;

class RegenerateLoanSchedules extends Command
{
    protected $signature = 'loans:regenerate-schedules 
                            {--loan= : Specific loan ID to regenerate}
                            {--fix-zero : Only regenerate loans with zero-amount schedules}
                            {--interest-only : Only regenerate loans with interest_only repayment type (Special/Agriculture loans)}
                            {--dry-run : Show what would be changed without making changes}';

    protected $description = 'Regenerate loan repayment schedules';

    public function handle()
    {
        $dryRun       = $this->option('dry-run');
        $fixZero      = $this->option('fix-zero');
        $interestOnly = $this->option('interest-only');
        $loanId       = $this->option('loan');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $query = Loan::with('product')->whereIn('status', ['pending', 'approved', 'disbursed', 'active']);

        if ($loanId) {
            $query->where('id', $loanId);
        }

        if ($fixZero) {
            $loanIdsWithZero = LoanSchedule::where('total_amount', 0)
                ->distinct()
                ->pluck('loan_id');
            $query->whereIn('id', $loanIdsWithZero);
        }

        if ($interestOnly) {
            // Filter to loans whose product has repayment_type = interest_only
            $interestOnlyProductIds = \App\Models\LoanProduct::where('repayment_type', 'interest_only')
                ->pluck('id');
            $query->whereIn('product_id', $interestOnlyProductIds);
        }

        $loans = $query->get();

        if ($loans->isEmpty()) {
            $this->info('No loans found matching criteria.');
            return 0;
        }

        $this->info("Found {$loans->count()} loan(s) to process.\n");

        $regenerated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($loans as $loan) {
            $scheduleCount = $loan->schedules()->count();
            $zeroCount = $loan->schedules()->where('total_amount', 0)->count();

            $this->line("Loan #{$loan->id}: Principal={$loan->principal}, Term={$loan->term}, Rate={$loan->interest_rate}%, Schedule={$loan->repayment_schedule}");
            $this->line("  Current schedules: {$scheduleCount}, Zero amounts: {$zeroCount}");

            if ($fixZero && $zeroCount == 0) {
                $this->line("  → Skipping (no zero amounts)");
                $skipped++;
                continue;
            }

            // Calculate what the monthly payment should be
            $principal         = $loan->principal;
            $term              = $loan->term;
            $monthlyRate       = $loan->interest_rate / 100;
            $repaymentSchedule = $loan->repayment_schedule ?? 'monthly';
            $repaymentType     = optional($loan->product)->repayment_type ?? 'amortized';
            $interestType      = optional($loan->product)->interest_type ?? 'flat';

            // Convert term to months
            $termInMonths = $term;
            if ($repaymentSchedule === 'daily')    { $termInMonths = $term / 30; }
            elseif ($repaymentSchedule === 'weekly') { $termInMonths = $term / 4; }
            elseif ($repaymentSchedule === 'biweekly') { $termInMonths = $term / 2; }

            if ($repaymentType === 'interest_only') {
                $installmentPayment = round($principal * $monthlyRate, 2);
                $totalInterest      = round($installmentPayment * $term, 2);
                $totalAmount        = round($totalInterest + $principal, 2);
            } elseif (in_array(strtolower((string) $interestType), ['reducing', 'reducing_balance', 'reducing-balance'], true) && $monthlyRate > 0) {
                $ratePerInstallment = $monthlyRate;
                if ($repaymentSchedule === 'daily')    { $ratePerInstallment = $monthlyRate / 30; }
                elseif ($repaymentSchedule === 'weekly') { $ratePerInstallment = $monthlyRate / 4; }
                elseif ($repaymentSchedule === 'biweekly') { $ratePerInstallment = $monthlyRate / 2; }
                $installmentPayment = round($principal * ($ratePerInstallment * pow(1 + $ratePerInstallment, $term)) / (pow(1 + $ratePerInstallment, $term) - 1), 2);
                $totalAmount        = round($installmentPayment * $term, 2);
                $totalInterest      = round($totalAmount - $principal, 2);
            } else {
                $totalInterest      = round($principal * $monthlyRate * $termInMonths, 2);
                $totalAmount        = round($principal + $totalInterest, 2);
                $installmentPayment = round($totalAmount / $term, 2);
            }

            $this->line("  Type={$repaymentType}, Calculated: Installment={$installmentPayment}, TotalInterest={$totalInterest}, TotalAmount={$totalAmount}");

            if (!$dryRun) {
                try {
                    $loan->update([
                        'monthly_payment' => $installmentPayment,
                        'total_amount'    => $totalAmount,
                    ]);

                    // Regenerate schedule (generateSchedule() now handles interest_only)
                    $loan->generateSchedule();

                    $newScheduleCount = $loan->schedules()->count();
                    $this->info("  ✓ Regenerated {$newScheduleCount} schedule entries");
                    $regenerated++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Error: {$e->getMessage()}");
                    $errors++;
                }
            } else {
                $this->line("  → Would regenerate schedule");
                $regenerated++;
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->line("  " . ($dryRun ? "Would regenerate" : "Regenerated") . ": {$regenerated}");
        $this->line("  Skipped: {$skipped}");
        if ($errors > 0) {
            $this->error("  Errors: {$errors}");
        }

        return $errors > 0 ? 1 : 0;
    }
}
