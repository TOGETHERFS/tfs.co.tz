<?php

namespace App\Console\Commands;

use App\Models\Repayment;
use App\Models\Loan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckRepaymentData extends Command
{
    protected $signature = 'repayments:check {loan_id?}';
    protected $description = 'Check repayment data in database';

    public function handle()
    {
        $loanId = $this->argument('loan_id');
        
        $this->info("Checking repayments in database...");
        $this->newLine();
        
        // Get all repayments directly from DB without tenant scope
        $query = DB::table('repayments')
            ->join('loans', 'repayments.loan_id', '=', 'loans.id')
            ->join('clients', 'loans.client_id', '=', 'clients.id')
            ->select(
                'repayments.id',
                'repayments.tenant_id as rep_tenant_id',
                'repayments.loan_id',
                'repayments.amount',
                'repayments.payment_date',
                'repayments.receipt_number',
                'loans.loan_number',
                'loans.tenant_id as loan_tenant_id',
                DB::raw("CONCAT(clients.first_name, ' ', clients.last_name) as client_name")
            );
        
        if ($loanId) {
            $query->where('repayments.loan_id', $loanId);
        }
        
        $repayments = $query->orderBy('repayments.created_at', 'desc')->get();
        
        if ($repayments->isEmpty()) {
            $this->warn("No repayments found in database!");
            return 0;
        }
        
        $this->info("Found {$repayments->count()} repayment(s):");
        $this->newLine();
        
        $this->table(
            ['ID', 'Receipt #', 'Loan #', 'Client', 'Amount', 'Date', 'Rep Tenant', 'Loan Tenant'],
            $repayments->map(function ($rep) {
                return [
                    $rep->id,
                    $rep->receipt_number,
                    $rep->loan_number,
                    $rep->client_name,
                    number_format($rep->amount, 2),
                    $rep->payment_date,
                    $rep->rep_tenant_id ?? 'NULL',
                    $rep->loan_tenant_id,
                ];
            })->toArray()
        );
        
        // Check for tenant_id mismatches
        $mismatches = $repayments->filter(function ($rep) {
            return $rep->rep_tenant_id === null || $rep->rep_tenant_id != $rep->loan_tenant_id;
        });
        
        if ($mismatches->isNotEmpty()) {
            $this->newLine();
            $this->warn("Found {$mismatches->count()} repayment(s) with tenant_id issues!");
            $this->info("These repayments won't show up due to tenant filtering.");
            $this->newLine();
            
            if ($this->confirm('Do you want to fix the tenant_id for these repayments?')) {
                foreach ($mismatches as $rep) {
                    DB::table('repayments')
                        ->where('id', $rep->id)
                        ->update(['tenant_id' => $rep->loan_tenant_id]);
                    
                    $this->info("✓ Fixed repayment ID {$rep->id} - set tenant_id to {$rep->loan_tenant_id}");
                }
                
                $this->newLine();
                $this->info("✅ All tenant_id issues fixed!");
            }
        } else {
            $this->info("✓ All repayments have correct tenant_id");
        }
        
        return 0;
    }
}
