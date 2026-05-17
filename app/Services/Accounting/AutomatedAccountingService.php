<?php

namespace App\Services\Accounting;

use App\Models\Loan;
use App\Models\Repayment;
use App\Models\Salary;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\SavingsAccount;
use App\Models\Accounting\SavingsTransaction;
use App\Models\Accounting\Expense;
use App\Models\Accounting\FixedAsset;
use Illuminate\Support\Facades\DB;

class AutomatedAccountingService
{
    protected JournalEntryService $journalService;

    public function __construct(JournalEntryService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function recordLoanDisbursement(Loan $loan): ?JournalEntry
    {
        // Idempotency: skip if a disbursement entry already exists for this loan
        $exists = JournalEntry::where('entry_type', 'loan_disbursement')
            ->where('reference_type', Loan::class)
            ->where('reference_id', $loan->id)
            ->exists();

        if ($exists) {
            return null;
        }

        $loanPortfolioAccount = $this->getAccount('1400');
        $cashAccount = $this->getAccount('1200');
        $processingFeeAccount = $this->getAccount('4200');

        if (!$loanPortfolioAccount || !$cashAccount) {
            throw new \Exception('Required accounts not configured for loan disbursement.');
        }

        $netDisbursement = $loan->principal - ($loan->processing_fee ?? 0);

        $lines = [
            [
                'account_id' => $loanPortfolioAccount->id,
                'description' => "Loan disbursement - {$loan->loan_number}",
                'debit_amount' => $loan->principal,
                'credit_amount' => 0,
                'reference_type' => Loan::class,
                'reference_id' => $loan->id,
            ],
            [
                'account_id' => $cashAccount->id,
                'description' => "Cash disbursed - {$loan->loan_number}",
                'debit_amount' => 0,
                'credit_amount' => $netDisbursement,
                'reference_type' => Loan::class,
                'reference_id' => $loan->id,
            ],
        ];

        if ($loan->processing_fee > 0 && $processingFeeAccount) {
            $lines[] = [
                'account_id' => $processingFeeAccount->id,
                'description' => "Processing fee - {$loan->loan_number}",
                'debit_amount' => 0,
                'credit_amount' => $loan->processing_fee,
                'reference_type' => Loan::class,
                'reference_id' => $loan->id,
            ];
        }

        $entry = $this->journalService->createEntry([
            'entry_date' => $loan->disbursed_at ?? now(),
            'entry_type' => 'loan_disbursement',
            'reference_type' => Loan::class,
            'reference_id' => $loan->id,
            'description' => "Loan disbursement for " . ($loan->client->first_name ?? '') . " " . ($loan->client->last_name ?? '') . " - {$loan->loan_number}",
            'status' => 'posted',
            'is_auto_generated' => true,
            'prefix' => 'LD',
        ], $lines);

        $this->journalService->postEntry($entry, auth()->id() ?? 1);

        return $entry;
    }

    public function recordLoanRepayment(Repayment $repayment): ?JournalEntry
    {
        $loan = $repayment->loan;
        $schedule = $repayment->schedule;

        $loanPortfolioAccount = $this->getAccount('1400');
        $interestIncomeAccount = $this->getAccount('4100');
        $penaltyIncomeAccount = $this->getAccount('4300');
        $cashAccount = $this->getAccount('1200');

        if (!$loanPortfolioAccount || !$interestIncomeAccount || !$cashAccount) {
            throw new \Exception('Required accounts not configured for loan repayment.');
        }

        if ($schedule) {
            $principalAmount = (float) $schedule->principal_amount;
            $interestAmount  = (float) $schedule->interest_amount;
        } else {
            // No linked schedule — derive split from the loan's own payment breakdown.
            // Use the loan's monthly_payment to find the interest ratio; avoid treating
            // the entire repayment as penalty which inflates Penalty Income incorrectly.
            $loanPrincipalPerInstalment = $loan->term > 0 ? round((float)$loan->principal / $loan->term, 2) : (float)$loan->principal;
            $loanMonthlyPayment = (float)($loan->monthly_payment ?: $loan->total_amount / max(1, $loan->term));
            $loanInterestPerInstalment = max(0, $loanMonthlyPayment - $loanPrincipalPerInstalment);

            if ($loanMonthlyPayment > 0) {
                $ratio = min(1, $repayment->amount / $loanMonthlyPayment);
                $principalAmount = round($loanPrincipalPerInstalment * $ratio, 2);
                $interestAmount  = round($loanInterestPerInstalment * $ratio, 2);
            } else {
                $principalAmount = $repayment->amount;
                $interestAmount  = 0;
            }
        }

        // Only record penalty if repayment genuinely exceeds principal + interest
        $penaltyAmount = max(0, round($repayment->amount - $principalAmount - $interestAmount, 2));

        if ($repayment->amount < ($principalAmount + $interestAmount)) {
            $ratio = $repayment->amount / ($principalAmount + $interestAmount);
            $principalAmount = round($principalAmount * $ratio, 2);
            $interestAmount  = round($interestAmount * $ratio, 2);
            $penaltyAmount   = 0;
        }

        $lines = [
            [
                'account_id' => $cashAccount->id,
                'description' => "Loan repayment received - {$loan->loan_number}",
                'debit_amount' => $repayment->amount,
                'credit_amount' => 0,
                'reference_type' => Repayment::class,
                'reference_id' => $repayment->id,
            ],
        ];

        if ($principalAmount > 0) {
            $lines[] = [
                'account_id' => $loanPortfolioAccount->id,
                'description' => "Principal repayment - {$loan->loan_number}",
                'debit_amount' => 0,
                'credit_amount' => $principalAmount,
                'reference_type' => Repayment::class,
                'reference_id' => $repayment->id,
            ];
        }

        if ($interestAmount > 0) {
            $lines[] = [
                'account_id' => $interestIncomeAccount->id,
                'description' => "Interest income - {$loan->loan_number}",
                'debit_amount' => 0,
                'credit_amount' => $interestAmount,
                'reference_type' => Repayment::class,
                'reference_id' => $repayment->id,
            ];
        }

        if ($penaltyAmount > 0 && $penaltyIncomeAccount) {
            $lines[] = [
                'account_id' => $penaltyIncomeAccount->id,
                'description' => "Penalty income - {$loan->loan_number}",
                'debit_amount' => 0,
                'credit_amount' => $penaltyAmount,
                'reference_type' => Repayment::class,
                'reference_id' => $repayment->id,
            ];
        }

        $entry = $this->journalService->createEntry([
            'entry_date' => $repayment->payment_date,
            'entry_type' => 'loan_repayment',
            'reference_type' => Repayment::class,
            'reference_id' => $repayment->id,
            'description' => "Loan repayment from " . ($loan->client->first_name ?? '') . " " . ($loan->client->last_name ?? '') . " - {$repayment->receipt_number}",
            'status' => 'posted',
            'is_auto_generated' => true,
            'prefix' => 'LR',
        ], $lines);

        $this->journalService->postEntry($entry, auth()->id() ?? 1);

        return $entry;
    }

    public function recordSavingsDeposit(SavingsTransaction $transaction): ?JournalEntry
    {
        $savingsAccount = $transaction->savingsAccount;
        $clientSavingsAccount = $this->getAccount('2100');
        $cashAccount = $this->getAccount('1200');

        if (!$clientSavingsAccount || !$cashAccount) {
            throw new \Exception('Required accounts not configured for savings deposit.');
        }

        $lines = [
            [
                'account_id' => $cashAccount->id,
                'description' => "Savings deposit - {$savingsAccount->account_number}",
                'debit_amount' => $transaction->amount,
                'credit_amount' => 0,
                'reference_type' => SavingsTransaction::class,
                'reference_id' => $transaction->id,
            ],
            [
                'account_id' => $clientSavingsAccount->id,
                'description' => "Client savings liability - {$savingsAccount->account_number}",
                'debit_amount' => 0,
                'credit_amount' => $transaction->amount,
                'reference_type' => SavingsTransaction::class,
                'reference_id' => $transaction->id,
            ],
        ];

        $entry = $this->journalService->createEntry([
            'entry_date' => $transaction->transaction_date,
            'entry_type' => 'savings_deposit',
            'reference_type' => SavingsTransaction::class,
            'reference_id' => $transaction->id,
            'description' => "Savings deposit - {$transaction->transaction_number}",
            'status' => 'posted',
            'is_auto_generated' => true,
            'prefix' => 'SD',
        ], $lines);

        $this->journalService->postEntry($entry, auth()->id() ?? 1);

        return $entry;
    }

    public function recordSavingsWithdrawal(SavingsTransaction $transaction): ?JournalEntry
    {
        $savingsAccount = $transaction->savingsAccount;
        $clientSavingsAccount = $this->getAccount('2100');
        $cashAccount = $this->getAccount('1200');

        if (!$clientSavingsAccount || !$cashAccount) {
            throw new \Exception('Required accounts not configured for savings withdrawal.');
        }

        $lines = [
            [
                'account_id' => $clientSavingsAccount->id,
                'description' => "Savings withdrawal - {$savingsAccount->account_number}",
                'debit_amount' => $transaction->amount,
                'credit_amount' => 0,
                'reference_type' => SavingsTransaction::class,
                'reference_id' => $transaction->id,
            ],
            [
                'account_id' => $cashAccount->id,
                'description' => "Cash paid out - {$savingsAccount->account_number}",
                'debit_amount' => 0,
                'credit_amount' => $transaction->amount,
                'reference_type' => SavingsTransaction::class,
                'reference_id' => $transaction->id,
            ],
        ];

        $entry = $this->journalService->createEntry([
            'entry_date' => $transaction->transaction_date,
            'entry_type' => 'savings_withdrawal',
            'reference_type' => SavingsTransaction::class,
            'reference_id' => $transaction->id,
            'description' => "Savings withdrawal - {$transaction->transaction_number}",
            'status' => 'posted',
            'is_auto_generated' => true,
            'prefix' => 'SW',
        ], $lines);

        $this->journalService->postEntry($entry, auth()->id() ?? 1);

        return $entry;
    }

    public function recordExpense(Expense $expense): ?JournalEntry
    {
        $lines = [
            [
                'account_id' => $expense->account_id,
                'description' => $expense->description,
                'debit_amount' => $expense->amount,
                'credit_amount' => 0,
                'reference_type' => Expense::class,
                'reference_id' => $expense->id,
                'branch_id' => $expense->branch_id,
            ],
            [
                'account_id' => $expense->payment_account_id,
                'description' => "Payment for: {$expense->description}",
                'debit_amount' => 0,
                'credit_amount' => $expense->amount,
                'reference_type' => Expense::class,
                'reference_id' => $expense->id,
                'branch_id' => $expense->branch_id,
            ],
        ];

        $entry = $this->journalService->createEntry([
            'entry_date' => $expense->expense_date,
            'entry_type' => 'expense',
            'reference_type' => Expense::class,
            'reference_id' => $expense->id,
            'description' => "Expense: {$expense->description}",
            'status' => 'posted',
            'is_auto_generated' => true,
            'prefix' => 'EXP',
        ], $lines);

        $expense->update(['journal_entry_id' => $entry->id]);

        $this->journalService->postEntry($entry, auth()->id() ?? 1);

        return $entry;
    }

    public function recordAssetPurchase(FixedAsset $asset): ?JournalEntry
    {
        $category = $asset->category;
        $assetAccount = $category->assetAccount ?? $this->getAccount('1800');
        $cashAccount = $this->getAccount('1200');

        if (!$assetAccount || !$cashAccount) {
            throw new \Exception('Required accounts not configured for asset purchase.');
        }

        $lines = [
            [
                'account_id' => $assetAccount->id,
                'description' => "Asset purchase: {$asset->asset_name}",
                'debit_amount' => $asset->purchase_price,
                'credit_amount' => 0,
                'reference_type' => FixedAsset::class,
                'reference_id' => $asset->id,
                'branch_id' => $asset->branch_id,
            ],
            [
                'account_id' => $cashAccount->id,
                'description' => "Payment for asset: {$asset->asset_name}",
                'debit_amount' => 0,
                'credit_amount' => $asset->purchase_price,
                'reference_type' => FixedAsset::class,
                'reference_id' => $asset->id,
                'branch_id' => $asset->branch_id,
            ],
        ];

        $entry = $this->journalService->createEntry([
            'entry_date' => $asset->purchase_date,
            'entry_type' => 'asset_purchase',
            'reference_type' => FixedAsset::class,
            'reference_id' => $asset->id,
            'description' => "Fixed asset purchase: {$asset->asset_name} ({$asset->asset_code})",
            'status' => 'posted',
            'is_auto_generated' => true,
            'prefix' => 'AP',
        ], $lines);

        $asset->update(['purchase_journal_id' => $entry->id]);

        $this->journalService->postEntry($entry, auth()->id() ?? 1);

        return $entry;
    }

    public function recordDepreciation(FixedAsset $asset, float $amount, $depreciationDate = null): ?JournalEntry
    {
        $category = $asset->category;
        $depreciationExpenseAccount = $category->depreciationAccount ?? $this->getAccount('5800');
        $accumulatedDepreciationAccount = $category->accumulatedDepreciationAccount ?? $this->getAccount('1890');

        if (!$depreciationExpenseAccount || !$accumulatedDepreciationAccount) {
            throw new \Exception('Required accounts not configured for depreciation.');
        }

        $depreciationDate = $depreciationDate ?? now();

        $lines = [
            [
                'account_id' => $depreciationExpenseAccount->id,
                'description' => "Depreciation expense: {$asset->asset_name}",
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'reference_type' => FixedAsset::class,
                'reference_id' => $asset->id,
                'branch_id' => $asset->branch_id,
            ],
            [
                'account_id' => $accumulatedDepreciationAccount->id,
                'description' => "Accumulated depreciation: {$asset->asset_name}",
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'reference_type' => FixedAsset::class,
                'reference_id' => $asset->id,
                'branch_id' => $asset->branch_id,
            ],
        ];

        $entry = $this->journalService->createEntry([
            'entry_date' => $depreciationDate,
            'entry_type' => 'asset_depreciation',
            'reference_type' => FixedAsset::class,
            'reference_id' => $asset->id,
            'description' => "Monthly depreciation: {$asset->asset_name} ({$asset->asset_code})",
            'status' => 'posted',
            'is_auto_generated' => true,
            'prefix' => 'DEP',
        ], $lines);

        $asset->update([
            'accumulated_depreciation' => $asset->accumulated_depreciation + $amount,
            'current_value' => $asset->current_value - $amount,
            'last_depreciation_date' => $depreciationDate,
        ]);

        $this->journalService->postEntry($entry, auth()->id() ?? 1);

        return $entry;
    }

    public function recordSalaryPayment(Salary $salary): ?JournalEntry
    {
        $salaryExpenseAccount = $this->getAccount('5110');
        $cashAccount          = $this->getAccount('1200');

        if (!$salaryExpenseAccount || !$cashAccount) {
            throw new \Exception('Required accounts not configured for salary payment (5110 / 1120).');
        }

        $staffName  = $salary->user->name ?? "User #{$salary->user_id}";
        $entryDate  = $salary->payment_date
            ? $salary->payment_date->toDateString()
            : now()->toDateString();

        $lines = [
            [
                'account_id'     => $salaryExpenseAccount->id,
                'description'    => "Salary expense - {$staffName} ({$salary->month})",
                'debit_amount'   => $salary->net_salary,
                'credit_amount'  => 0,
                'reference_type' => Salary::class,
                'reference_id'   => $salary->id,
            ],
            [
                'account_id'     => $cashAccount->id,
                'description'    => "Salary paid - {$staffName} ({$salary->month})",
                'debit_amount'   => 0,
                'credit_amount'  => $salary->net_salary,
                'reference_type' => Salary::class,
                'reference_id'   => $salary->id,
            ],
        ];

        $entry = $this->journalService->createEntry([
            'entry_date'     => $entryDate,
            'entry_type'     => 'expense',
            'reference_type' => Salary::class,
            'reference_id'   => $salary->id,
            'description'    => "Salary payment - {$staffName} ({$salary->month})",
            'status'         => 'posted',
            'is_auto_generated' => true,
            'prefix'         => 'SAL',
        ], $lines);

        $this->journalService->postEntry($entry, auth()->id() ?? 1);

        return $entry;
    }

    protected function getAccount(string $code): ?ChartOfAccount
    {
        $tenantId = session('tenant_id') ?? (auth()->check() ? auth()->user()->tenant_id : null);

        // Canonical code map: service uses legacy codes, map to actual COA codes in use
        $codeAliases = [
            '1200' => ['1200', '1120', '1110'],  // Cash at Bank / Cash in Hand
            '1300' => ['1300', '1120', '1110'],  // Mobile Money → cash fallback
            '1400' => ['1400', '1140'],           // Loan Portfolio / Loan Receivables
            '1500' => ['1500', '1150'],           // Interest Receivable
            '1600' => ['1600', '1130'],           // Fees Receivable → AR fallback
            '2100' => ['2100', '2120'],           // Client Savings / Client Deposits
            '4100' => ['4100'],
            '4200' => ['4200'],
            '4300' => ['4300'],
            '5110' => ['5110'],                   // Salaries & Wages
            '5400' => ['5400', '5500'],           // Loan Loss Provision
        ];

        $candidates = $codeAliases[$code] ?? [$code];

        $query = ChartOfAccount::whereIn('account_code', $candidates);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        foreach ($candidates as $candidate) {
            $account = (clone $query)->where('account_code', $candidate)->first();
            if ($account) return $account;
        }

        return null;
    }
}
