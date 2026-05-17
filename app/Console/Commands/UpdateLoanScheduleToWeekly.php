<?php

namespace App\Console\Commands;

use App\Models\Loan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateLoanScheduleToWeekly extends Command
{
    protected $signature = 'loans:update-schedule-weekly {--loan-ids=* : Specific loan IDs to update}';
    protected $description = 'Update loan repayment schedules to weekly and regenerate schedules';

    public function handle()
    {
        $loanIds = $this->option('loan-ids');
        
        $query = Loan::query();
        
        if (!empty($loanIds)) {
            $query->whereIn('id', $loanIds);
        } else {
            // If no specific IDs, ask for confirmation
            if (!$this->confirm('No loan IDs specified. Do you want to update ALL active loans to weekly schedule?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
            $query->whereIn('status', ['pending', 'approved', 'disbursed', 'active']);
        }
        
        $loans = $query->with(['product', 'schedules'])->get();
        
        if ($loans->isEmpty()) {
            $this->error('No loans found to update.');
            return 1;
        }
        
        $this->info("Found {$loans->count()} loan(s) to update.");
        
        $bar = $this->output->createProgressBar($loans->count());
        $bar->start();
        
        $updated = 0;
        $errors = 0;
        
        foreach ($loans as $loan) {
            DB::beginTransaction();
            try {
                // Update repayment schedule to weekly
                $loan->update(['repayment_schedule' => 'weekly']);
                
                // Regenerate schedule
                $loan->generateSchedule();
                
                DB::commit();
                $updated++;
                $this->newLine();
                $this->info("✓ Updated Loan #{$loan->loan_number} (ID: {$loan->id}) - Client: {$loan->client->full_name}");
            } catch (\Exception $e) {
                DB::rollBack();
                $errors++;
                $this->newLine();
                $this->error("✗ Failed to update Loan #{$loan->loan_number} (ID: {$loan->id}): {$e->getMessage()}");
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Update complete!");
        $this->info("Successfully updated: {$updated}");
        if ($errors > 0) {
            $this->warn("Errors: {$errors}");
        }
        
        return 0;
    }
}
