<?php

namespace App\Observers;

use App\Models\Loan;
use App\Models\Repayment;
use App\Services\Accounting\AutomatedAccountingService;
use App\Services\Accounting\JournalEntryService;
use Illuminate\Support\Facades\Log;

class AccountingObserver
{
    protected AutomatedAccountingService $accountingService;

    public function __construct()
    {
        $this->accountingService = new AutomatedAccountingService(
            new JournalEntryService()
        );
    }

    public function loanDisbursed(Loan $loan): void
    {
        if ($loan->status !== 'disbursed' || !$loan->disbursed_at) {
            return;
        }

        try {
            $this->accountingService->recordLoanDisbursement($loan);
            Log::info("Accounting entry created for loan disbursement: {$loan->loan_number}");
        } catch (\Exception $e) {
            Log::error("Failed to create accounting entry for loan disbursement: {$e->getMessage()}", [
                'loan_id' => $loan->id,
                'loan_number' => $loan->loan_number,
            ]);
        }
    }

    public function repaymentCreated(Repayment $repayment): void
    {
        try {
            $this->accountingService->recordLoanRepayment($repayment);
            Log::info("Accounting entry created for repayment: {$repayment->receipt_number}");
        } catch (\Exception $e) {
            Log::error("Failed to create accounting entry for repayment: {$e->getMessage()}", [
                'repayment_id' => $repayment->id,
                'receipt_number' => $repayment->receipt_number,
            ]);
        }
    }
}
