<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\Repayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixRwizaLoanIssues extends Command
{
    protected $signature = 'loans:fix-rwiza-issues {loan_id}';
    protected $description = 'Fix duplicate repayments and update loan schedule to weekly';

    public function handle()
    {
        $loanId = $this->argument('loan_id');
        
        $loan = Loan::with(['client', 'repayments', 'schedules'])->find($loanId);
        
        if (!$loan) {
            $this->error("Loan #{$loanId} not found.");
            return 1;
        }
        
        $this->info("Processing Loan #{$loan->loan_number} for {$loan->client->full_name}");
        $this->newLine();
        
        DB::beginTransaction();
        try {
            // Step 1: Find and remove duplicate repayments
            $this->info("Step 1: Checking for duplicate repayments...");
            $repayments = $loan->repayments()->orderBy('created_at', 'asc')->get();
            
            if ($repayments->count() > 1) {
                $this->warn("Found {$repayments->count()} repayment records:");
                foreach ($repayments as $index => $repayment) {
                    $num = $index + 1;
                    $this->line("  #{$num}: ID={$repayment->id}, Amount={$repayment->amount}, Date={$repayment->payment_date}, Created={$repayment->created_at}");
                }
                
                // Keep only the first repayment, delete the rest
                $keepRepayment = $repayments->first();
                $duplicates = $repayments->slice(1);
                
                $this->newLine();
                $this->info("Keeping repayment ID {$keepRepayment->id} (created at {$keepRepayment->created_at})");
                $this->warn("Deleting {$duplicates->count()} duplicate(s)...");
                
                foreach ($duplicates as $duplicate) {
                    $this->line("  Deleting repayment ID {$duplicate->id}");
                    $duplicate->delete();
                }
                
                $this->info("✓ Duplicates removed");
            } else {
                $this->info("✓ No duplicates found");
            }
            
            $this->newLine();
            
            // Step 2: Update loan to weekly schedule
            $this->info("Step 2: Updating repayment schedule to weekly...");
            $oldSchedule = $loan->repayment_schedule ?? 'monthly';
            $this->line("  Current schedule: {$oldSchedule}");
            
            $loan->update(['repayment_schedule' => 'weekly']);
            $this->info("✓ Updated to weekly");
            
            $this->newLine();
            
            // Step 3: Regenerate loan schedule
            $this->info("Step 3: Regenerating payment schedule...");
            $oldScheduleCount = $loan->schedules()->count();
            $this->line("  Deleting {$oldScheduleCount} old schedule entries...");
            
            $loan->schedules()->delete();
            
            $this->line("  Generating new weekly schedule...");
            $loan->generateSchedule();
            
            $newScheduleCount = $loan->fresh()->schedules()->count();
            $this->info("✓ Generated {$newScheduleCount} weekly payment schedules");
            
            // Show first 5 schedules
            $this->newLine();
            $this->info("First 5 payment schedules:");
            $schedules = $loan->schedules()->orderBy('due_date')->take(5)->get();
            foreach ($schedules as $schedule) {
                $this->line("  #{$schedule->installment_number}: Due {$schedule->due_date->format('Y-m-d')}, Amount: TZS {$schedule->total_amount}");
            }
            
            DB::commit();
            
            $this->newLine();
            $this->info("✅ All fixes completed successfully!");
            $this->newLine();
            
            // Summary
            $loan = $loan->fresh(['repayments', 'schedules']);
            $this->info("Summary:");
            $this->line("  Loan: #{$loan->loan_number}");
            $this->line("  Borrower: {$loan->client->full_name}");
            $this->line("  Principal: TZS {$loan->principal}");
            $this->line("  Outstanding: TZS {$loan->outstanding_balance}");
            $this->line("  Repayment Schedule: {$loan->repayment_schedule}");
            $this->line("  Total Repayments: {$loan->repayments->count()}");
            $this->line("  Total Paid: TZS {$loan->repayments->sum('amount')}");
            $this->line("  Payment Schedules: {$loan->schedules->count()}");
            
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
