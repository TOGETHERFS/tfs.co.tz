<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\Repayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreRwizaPayment extends Command
{
    protected $signature = 'repayments:restore-rwiza {loan_id}';
    protected $description = 'Restore Rwiza payment of TZS 11,000';

    public function handle()
    {
        $loanId = $this->argument('loan_id');
        
        $loan = Loan::with(['client', 'repayments'])->find($loanId);
        
        if (!$loan) {
            $this->error("Loan #{$loanId} not found.");
            return 1;
        }
        
        $this->info("Loan #{$loan->loan_number} for {$loan->client->full_name}");
        $this->info("Current repayments: {$loan->repayments->count()}");
        
        if ($loan->repayments->count() > 0) {
            $this->warn("This loan already has repayments:");
            foreach ($loan->repayments as $rep) {
                $this->line("  - ID: {$rep->id}, Amount: {$rep->amount}, Date: {$rep->payment_date}");
            }
            
            if (!$this->confirm('Do you want to add another repayment?')) {
                return 0;
            }
        }
        
        DB::beginTransaction();
        try {
            $repayment = Repayment::create([
                'tenant_id' => session('tenant_id') ?? $loan->tenant_id,
                'loan_id' => $loan->id,
                'schedule_id' => null,
                'user_id' => auth()->id() ?? 1,
                'amount' => 11000,
                'payment_method' => 'cash',
                'reference' => 'RESTORED_' . now()->format('YmdHis'),
                'payment_date' => now()->toDateString(),
                'notes' => 'Payment restored - TZS 11,000',
            ]);
            
            DB::commit();
            
            $this->newLine();
            $this->info("✅ Payment restored successfully!");
            $this->info("Receipt Number: {$repayment->receipt_number}");
            $this->info("Amount: TZS {$repayment->amount}");
            $this->info("Payment Date: {$repayment->payment_date}");
            
            $loan = $loan->fresh();
            $this->newLine();
            $this->info("Loan Summary:");
            $this->line("  Outstanding Balance: TZS {$loan->outstanding_balance}");
            $this->line("  Total Paid: TZS {$loan->repayments->sum('amount')}");
            
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }
}
