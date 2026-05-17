<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LoanSchedule;

class FixRehemaSchedule extends Command
{
    protected $signature = 'fix:rehema-schedule';
    protected $description = 'Fix Rehema Ally Mchezo schedule with due date 2025-04-03 to mark as unpaid';

    public function handle()
    {
        $this->info('Searching for Rehema Ally Mchezo schedule with due date 2025-04-03...');
        
        // Find Rehema's schedule with due date 2025-04-03
        $schedules = LoanSchedule::with(['loan.client'])
            ->whereHas('loan.client', function($q) {
                $q->where('first_name', 'LIKE', '%Rehema%')
                   ->where('last_name', 'LIKE', '%Ally%');
            })
            ->where('due_date', '2025-04-03')
            ->get();

        $this->info("Found {$schedules->count()} schedule(s)");
        $this->line('');

        foreach($schedules as $schedule) {
            $this->line("Client: " . $schedule->loan->client->first_name . " " . $schedule->loan->client->last_name);
            $this->line("Loan Number: " . $schedule->loan->loan_number);
            $this->line("Installment: " . $schedule->installment_number);
            $this->line("Due Date: " . $schedule->due_date);
            $this->line("Current Status: " . $schedule->status);
            $this->line("Paid Amount: " . $schedule->paid_amount);
            $this->line("Total Amount: " . $schedule->total_amount);
            $this->line("Paid Date: " . ($schedule->paid_date ?? 'NULL'));
            $this->line('---');
            
            // Update to unpaid
            $schedule->status = 'pending';
            $schedule->paid_amount = 0;
            $schedule->paid_date = null;
            $schedule->payment_method = null;
            
            if ($schedule->save()) {
                $this->info("UPDATED: Marked as unpaid");
                $this->line("New Status: " . $schedule->fresh()->status);
                $this->line("New Paid Amount: " . $schedule->fresh()->paid_amount);
                $this->line("New Paid Date: " . ($schedule->fresh()->paid_date ?? 'NULL'));
            } else {
                $this->error("ERROR: Failed to update");
            }
            $this->line('===');
            $this->line('');
        }

        $this->info('Done!');
        
        return 0;
    }
}
