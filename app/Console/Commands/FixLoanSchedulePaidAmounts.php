<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\Repayment;
use App\Models\LoanSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixLoanSchedulePaidAmounts extends Command
{
    protected $signature = 'loans:fix-schedule-paid-amounts {--loan_id= : Fix a specific loan ID only}';
    protected $description = 'Recalculate schedule paid_amounts and statuses from actual repayments, then fix loan status';

    public function handle()
    {
        $specificLoanId = $this->option('loan_id');

        $query = Loan::with(['repayments', 'schedules'])
            ->whereIn('status', ['active', 'disbursed', 'completed']);

        if ($specificLoanId) {
            $query->where('id', $specificLoanId);
        }

        $loans = $query->get();
        $this->info("Processing {$loans->count()} loans...");

        foreach ($loans as $loan) {
            DB::transaction(function () use ($loan) {
                // Step 1: Reset all schedule paid_amounts to 0
                $loan->schedules()->update([
                    'paid_amount' => 0,
                    'status' => 'pending',
                    'paid_date' => null,
                ]);

                // Step 2: Replay repayments in chronological order onto schedules
                $repayments = $loan->repayments()->orderBy('payment_date')->orderBy('id')->get();
                $totalApplied = 0;

                foreach ($repayments as $repayment) {
                    $remaining = (float) $repayment->amount;

                    // Apply to earliest unpaid/partial schedules first
                    $schedules = $loan->schedules()
                        ->whereNotIn('status', ['paid'])
                        ->orderBy('due_date')
                        ->get();

                    foreach ($schedules as $schedule) {
                        if ($remaining <= 0) break;

                        $unpaid = (float) $schedule->total_amount - (float) $schedule->paid_amount;
                        $pay = min($remaining, $unpaid);

                        $schedule->paid_amount = round((float) $schedule->paid_amount + $pay, 2);
                        $remaining = round($remaining - $pay, 2);

                        if ($schedule->paid_amount >= $schedule->total_amount) {
                            $schedule->status = 'paid';
                            $schedule->paid_date = $repayment->payment_date;
                        } elseif ($schedule->paid_amount > 0) {
                            $schedule->status = 'partial';
                        }

                        $schedule->save();
                    }

                    $totalApplied += (float) $repayment->amount;
                }

                // Step 3: Fix loan status
                $unpaidCount = $loan->schedules()->whereNotIn('status', ['paid'])->count();
                $paidCount   = $loan->schedules()->where('status', 'paid')->count();
                $totalCount  = $loan->schedules()->count();

                if ($unpaidCount === 0 && $totalCount > 0) {
                    $newStatus = 'completed';
                } elseif ($loan->status === 'completed' && $unpaidCount > 0) {
                    $newStatus = 'active';
                } else {
                    $newStatus = $loan->status;
                }

                $loan->status = $newStatus;
                $loan->save();

                $this->line("Loan #{$loan->id} ({$loan->loan_number}): {$paidCount}/{$totalCount} schedules paid → status: {$newStatus}");
            });
        }

        $this->info('Done.');
    }
}
