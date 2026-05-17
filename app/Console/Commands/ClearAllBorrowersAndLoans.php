<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class ClearAllBorrowersAndLoans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:all-borrowers-loans {--confirm : Confirm deletion without prompting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all borrowers and loans data from the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Analyzing current borrowers and loans data...');

        // Get all clients
        $clients = Client::all();
        $this->info("Found {$clients->count()} borrowers in the system:");
        
        foreach ($clients as $client) {
            $this->line("- ID: {$client->id}, Name: {$client->first_name} {$client->last_name}, Email: {$client->email}, Phone: {$client->phone}");
        }

        // Get all loans
        $loans = Loan::all();
        $this->info("\nFound {$loans->count()} loans in the system:");
        
        foreach ($loans as $loan) {
            $this->line("- Loan: {$loan->loan_number}, Client: {$loan->client->first_name} {$loan->client->last_name}, Principal: {$loan->principal}, Status: {$loan->status}");
        }

        // Get loan schedules count
        $schedules = LoanSchedule::count();
        $this->info("\nFound {$schedules} loan schedule entries");

        // Get payments count (if payments table exists)
        try {
            $payments = Payment::count();
            $this->info("Found {$payments} payment records");
        } catch (\Exception $e) {
            $this->info("No payment records found or payments table doesn't exist");
            $payments = 0;
        }

        if ($clients->isEmpty() && $loans->isEmpty()) {
            $this->info('No borrowers or loans found in the system.');
            return 0;
        }

        // Confirm deletion
        if (!$this->option('confirm')) {
            if (!$this->confirm('Are you sure you want to delete ALL borrowers and loans data? This action cannot be undone.')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Clearing all borrowers and loans data...');

        DB::transaction(function () use ($schedules, $payments) {
            // Delete payments first (if table exists)
            if ($payments > 0) {
                try {
                    $paymentsDeleted = Payment::truncate();
                    $this->info("Deleted all payment records");
                } catch (\Exception $e) {
                    $this->info("No payments to delete or payments table doesn't exist");
                }
            }

            // Delete loan schedules
            if ($schedules > 0) {
                LoanSchedule::truncate();
                $this->info("Deleted all loan schedules");
            }

            // Delete loans
            $loansDeleted = Loan::count();
            if ($loansDeleted > 0) {
                Loan::truncate();
                $this->info("Deleted all {$loansDeleted} loans");
            }

            // Delete clients
            $clientsDeleted = Client::count();
            if ($clientsDeleted > 0) {
                Client::truncate();
                $this->info("Deleted all {$clientsDeleted} borrowers");
            }
        });

        $this->info('All borrowers and loans data cleared successfully!');
        $this->info('The dashboard should now show no recent borrowers or loans.');
        return 0;
    }
}