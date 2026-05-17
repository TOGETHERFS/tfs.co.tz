<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteDemoBorrowers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:cleanup {--confirm : Confirm deletion without prompting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all demo data: borrowers, loans, and demo staff';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Demo Data Cleanup ===');
        $this->newLine();

        // 1. Find demo clients
        $this->info('Searching for demo borrowers...');
        $demoClients = Client::where(function ($query) {
            $query->where('first_name', 'Demo')
                  ->orWhere('email', 'like', '%demo%')
                  ->orWhere('phone', '+255700000001')
                  ->orWhere('phone', '255700000001')
                  ->orWhere('last_name', 'Client');
        })->get();

        if ($demoClients->isNotEmpty()) {
            $this->info("Found {$demoClients->count()} demo borrowers:");
            foreach ($demoClients as $client) {
                $this->line("  - ID: {$client->id}, Name: {$client->first_name} {$client->last_name}, Phone: {$client->phone}");
            }
        } else {
            $this->info('No demo borrowers found.');
        }

        // 2. Find associated loans (including demo loans by amount)
        $clientIds = $demoClients->pluck('id');
        $loans = Loan::where(function ($query) use ($clientIds) {
            $query->whereIn('client_id', $clientIds)
                  ->orWhere('principal', 1000000); // Demo loan amount
        })->get();
        
        if ($loans->isNotEmpty()) {
            $this->newLine();
            $this->info("Found {$loans->count()} demo/associated loans:");
            foreach ($loans as $loan) {
                $this->line("  - Loan: {$loan->loan_number}, Principal: TZS " . number_format($loan->principal) . ", Status: {$loan->status}");
            }
        }

        // 3. Find demo staff
        $this->newLine();
        $this->info('Searching for demo staff...');
        $demoStaff = User::where(function ($query) {
            $query->where('email', 'user@phidtech.com')
                  ->orWhere('email', 'like', '%demo%')
                  ->orWhere('name', 'like', '%Demo%');
        })->where('email', '!=', 'phidtechnology@gmail.com') // Never delete super admin
          ->get();

        if ($demoStaff->isNotEmpty()) {
            $this->info("Found {$demoStaff->count()} demo staff:");
            foreach ($demoStaff as $staff) {
                $this->line("  - ID: {$staff->id}, Name: {$staff->name}, Email: {$staff->email}");
            }
        } else {
            $this->info('No demo staff found.');
        }

        // Check if anything to delete
        if ($demoClients->isEmpty() && $loans->isEmpty() && $demoStaff->isEmpty()) {
            $this->newLine();
            $this->info('No demo data found to delete.');
            return 0;
        }

        // Confirm deletion
        $this->newLine();
        if (!$this->option('confirm')) {
            if (!$this->confirm('Are you sure you want to delete all demo data? This action cannot be undone.')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->newLine();
        $this->info('Deleting demo data...');

        DB::transaction(function () use ($clientIds, $loans, $demoClients, $demoStaff) {
            // Delete loan schedules first
            if ($loans->isNotEmpty()) {
                $loanIds = $loans->pluck('id');
                $schedulesDeleted = LoanSchedule::whereIn('loan_id', $loanIds)->delete();
                $this->info("  ✓ Deleted {$schedulesDeleted} loan schedules");

                // Delete loan approvals if exists
                DB::table('loan_approvals')->whereIn('loan_id', $loanIds)->delete();
                
                // Delete loans
                $loansDeleted = Loan::whereIn('id', $loanIds)->delete();
                $this->info("  ✓ Deleted {$loansDeleted} loans");
            }

            // Delete clients
            if ($demoClients->isNotEmpty()) {
                $clientsDeleted = Client::whereIn('id', $clientIds)->delete();
                $this->info("  ✓ Deleted {$clientsDeleted} demo borrowers");
            }

            // Delete demo staff (detach roles first)
            if ($demoStaff->isNotEmpty()) {
                foreach ($demoStaff as $staff) {
                    $staff->roles()->detach();
                }
                $staffDeleted = User::whereIn('id', $demoStaff->pluck('id'))->delete();
                $this->info("  ✓ Deleted {$staffDeleted} demo staff");
            }
        });

        $this->newLine();
        $this->info('✓ Demo data cleanup completed successfully!');
        return 0;
    }
}