<?php

namespace App\Services\Accounting;

use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\GeneralLedger;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\FiscalYear;
use App\Models\Accounting\AccountingPeriod;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class FinancialReportingService
{
    protected function getTenantId(): ?int
    {
        $id = session('tenant_id') ?? (auth()->check() ? auth()->user()->tenant_id : null);
        return $id ? (int)$id : null;
    }

    public function getTrialBalance(?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now()->format('Y-m-d');
        $tenantId = $this->getTenantId();

        $query = ChartOfAccount::active()->orderBy('account_code');
        if ($tenantId) $query->where('tenant_id', $tenantId);
        $accounts = $query->get();

        $trialBalance = [];
        $totalDebits = 0;
        $totalCredits = 0;
        $hasAccounts = $accounts->count() > 0;

        foreach ($accounts as $account) {
            $ledgerData = GeneralLedger::where('account_id', $account->id)
                ->where('transaction_date', '<=', $asOfDate)
                ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                ->selectRaw('SUM(debit_amount) as total_debit, SUM(credit_amount) as total_credit')
                ->first();

            $debit = floatval($ledgerData->total_debit ?? 0) + ($account->normal_balance === 'debit' ? floatval($account->opening_balance) : 0);
            $credit = floatval($ledgerData->total_credit ?? 0) + ($account->normal_balance === 'credit' ? floatval($account->opening_balance) : 0);

            $balance = $debit - $credit;

            if ($balance != 0 || $account->opening_balance != 0) {
                $trialBalance[] = [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'account_type' => $account->account_type,
                    'debit_balance' => $balance > 0 ? $balance : 0,
                    'credit_balance' => $balance < 0 ? abs($balance) : 0,
                ];

                if ($balance > 0) {
                    $totalDebits += $balance;
                } else {
                    $totalCredits += abs($balance);
                }
            }
        }

        return [
            'as_of_date' => $asOfDate,
            'accounts' => $trialBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
            'has_accounts' => $hasAccounts,
        ];
    }

    public function getBalanceSheet(?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now()->format('Y-m-d');

        $assets = $this->getAccountBalancesByType('asset', $asOfDate);
        $liabilities = $this->getAccountBalancesByType('liability', $asOfDate);
        $equity = $this->getAccountBalancesByType('equity', $asOfDate);

        $totalAssets = collect($assets)->sum('balance');
        $totalLiabilities = collect($liabilities)->sum('balance');
        $totalEquity = collect($equity)->sum('balance');

        $retainedEarnings = $this->calculateRetainedEarnings($asOfDate);
        $totalEquity += $retainedEarnings;

        return [
            'as_of_date' => $asOfDate,
            'assets' => [
                'accounts' => $assets,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'accounts' => $liabilities,
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $equity,
                'retained_earnings' => $retainedEarnings,
                'total' => $totalEquity,
            ],
            'total_liabilities_and_equity' => $totalLiabilities + $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }

    public function getIncomeStatement(string $startDate, string $endDate): array
    {
        $income = $this->getAccountBalancesByType('income', $endDate, $startDate);
        $expenses = $this->getAccountBalancesByType('expense', $endDate, $startDate);

        $totalIncome = collect($income)->sum('balance');
        $totalExpenses = collect($expenses)->sum('balance');
        $netIncome = $totalIncome - $totalExpenses;

        return [
            'period_start' => $startDate,
            'period_end' => $endDate,
            'income' => [
                'accounts' => $income,
                'total' => $totalIncome,
            ],
            'expenses' => [
                'accounts' => $expenses,
                'total' => $totalExpenses,
            ],
            'net_income' => $netIncome,
            'is_profitable' => $netIncome > 0,
        ];
    }

    public function getCashFlowStatement(string $startDate, string $endDate): array
    {
        $operatingActivities = $this->getOperatingCashFlows($startDate, $endDate);
        $investingActivities = $this->getInvestingCashFlows($startDate, $endDate);
        $financingActivities = $this->getFinancingCashFlows($startDate, $endDate);

        $netCashFlow = $operatingActivities['total'] + $investingActivities['total'] + $financingActivities['total'];

        $openingCash = $this->getCashBalance($startDate);
        $closingCash = $this->getCashBalance($endDate);

        return [
            'period_start' => $startDate,
            'period_end' => $endDate,
            'operating_activities' => $operatingActivities,
            'investing_activities' => $investingActivities,
            'financing_activities' => $financingActivities,
            'net_cash_flow' => $netCashFlow,
            'opening_cash_balance' => $openingCash,
            'closing_cash_balance' => $closingCash,
            'cash_change' => $closingCash - $openingCash,
        ];
    }

    public function getGeneralLedger(int $accountId, ?string $startDate = null, ?string $endDate = null): array
    {
        $account = ChartOfAccount::findOrFail($accountId);
        
        $query = GeneralLedger::where('account_id', $accountId)
            ->orderBy('transaction_date')
            ->orderBy('id');

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $entries = $query->get();

        $openingBalance = $account->opening_balance;
        if ($startDate) {
            $priorBalance = GeneralLedger::where('account_id', $accountId)
                ->where('transaction_date', '<', $startDate)
                ->selectRaw('SUM(debit_amount) - SUM(credit_amount) as balance')
                ->value('balance') ?? 0;
            
            if ($account->normal_balance === 'debit') {
                $openingBalance += $priorBalance;
            } else {
                $openingBalance -= $priorBalance;
            }
        }

        $runningBalance = $openingBalance;
        $ledgerEntries = [];

        foreach ($entries as $entry) {
            if ($account->normal_balance === 'debit') {
                $runningBalance += ($entry->debit_amount - $entry->credit_amount);
            } else {
                $runningBalance += ($entry->credit_amount - $entry->debit_amount);
            }

            $ledgerEntries[] = [
                'date' => $entry->transaction_date->format('Y-m-d'),
                'entry_number' => $entry->entry_number,
                'description' => $entry->description,
                'debit' => $entry->debit_amount,
                'credit' => $entry->credit_amount,
                'balance' => $runningBalance,
            ];
        }

        return [
            'account' => [
                'code' => $account->account_code,
                'name' => $account->account_name,
                'type' => $account->account_type,
            ],
            'period_start' => $startDate,
            'period_end' => $endDate,
            'opening_balance' => $openingBalance,
            'entries' => $ledgerEntries,
            'closing_balance' => $runningBalance,
            'total_debits' => collect($ledgerEntries)->sum('debit'),
            'total_credits' => collect($ledgerEntries)->sum('credit'),
        ];
    }

    public function getAccountBalancesByType(string $type, string $asOfDate, ?string $startDate = null): array
    {
        $tenantId = $this->getTenantId();

        $accountQuery = ChartOfAccount::active()
            ->where('account_type', $type)
            ->orderBy('account_code');
        if ($tenantId) $accountQuery->where('tenant_id', $tenantId);
        $accounts = $accountQuery->get();

        $balances = [];

        foreach ($accounts as $account) {
            $query = GeneralLedger::where('account_id', $account->id)
                ->where('transaction_date', '<=', $asOfDate)
                ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId));

            if ($startDate) {
                $query->where('transaction_date', '>=', $startDate);
            }

            $ledgerData = $query->selectRaw('SUM(debit_amount) as total_debit, SUM(credit_amount) as total_credit')
                ->first();

            $debit = floatval($ledgerData->total_debit ?? 0);
            $credit = floatval($ledgerData->total_credit ?? 0);

            if (!$startDate) {
                if ($account->normal_balance === 'debit') {
                    $debit += floatval($account->opening_balance);
                } else {
                    $credit += floatval($account->opening_balance);
                }
            }

            $balance = $account->normal_balance === 'debit' 
                ? ($debit - $credit) 
                : ($credit - $debit);

            if (abs($balance) > 0.001) {
                $balances[] = [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'balance' => abs($balance),
                    'is_normal' => $balance >= 0,
                ];
            }
        }

        return $balances;
    }

    protected function calculateRetainedEarnings(string $asOfDate): float
    {
        $startOfYear = Carbon::parse($asOfDate)->startOfYear()->format('Y-m-d');
        
        $incomeStatement = $this->getIncomeStatement($startOfYear, $asOfDate);
        
        return $incomeStatement['net_income'];
    }

    protected function getOperatingCashFlows(string $startDate, string $endDate): array
    {
        $items = [];
        $total = 0;

        $loanRepayments = GeneralLedger::whereBetween('transaction_date', [$startDate, $endDate])
            ->whereHas('journalEntry', fn($q) => $q->where('entry_type', 'loan_repayment'))
            ->whereHas('account', fn($q) => $q->where('is_cash_account', true)->orWhere('is_bank_account', true))
            ->sum('debit_amount');

        if ($loanRepayments > 0) {
            $items[] = ['description' => 'Loan Repayments Received', 'amount' => $loanRepayments];
            $total += $loanRepayments;
        }

        $loanDisbursements = GeneralLedger::whereBetween('transaction_date', [$startDate, $endDate])
            ->whereHas('journalEntry', fn($q) => $q->where('entry_type', 'loan_disbursement'))
            ->whereHas('account', fn($q) => $q->where('is_cash_account', true)->orWhere('is_bank_account', true))
            ->sum('credit_amount');

        if ($loanDisbursements > 0) {
            $items[] = ['description' => 'Loan Disbursements', 'amount' => -$loanDisbursements];
            $total -= $loanDisbursements;
        }

        $savingsDeposits = GeneralLedger::whereBetween('transaction_date', [$startDate, $endDate])
            ->whereHas('journalEntry', fn($q) => $q->where('entry_type', 'savings_deposit'))
            ->whereHas('account', fn($q) => $q->where('is_cash_account', true)->orWhere('is_bank_account', true))
            ->sum('debit_amount');

        if ($savingsDeposits > 0) {
            $items[] = ['description' => 'Savings Deposits Received', 'amount' => $savingsDeposits];
            $total += $savingsDeposits;
        }

        $savingsWithdrawals = GeneralLedger::whereBetween('transaction_date', [$startDate, $endDate])
            ->whereHas('journalEntry', fn($q) => $q->where('entry_type', 'savings_withdrawal'))
            ->whereHas('account', fn($q) => $q->where('is_cash_account', true)->orWhere('is_bank_account', true))
            ->sum('credit_amount');

        if ($savingsWithdrawals > 0) {
            $items[] = ['description' => 'Savings Withdrawals', 'amount' => -$savingsWithdrawals];
            $total -= $savingsWithdrawals;
        }

        $expenses = GeneralLedger::whereBetween('transaction_date', [$startDate, $endDate])
            ->whereHas('journalEntry', fn($q) => $q->where('entry_type', 'expense'))
            ->whereHas('account', fn($q) => $q->where('is_cash_account', true)->orWhere('is_bank_account', true))
            ->sum('credit_amount');

        if ($expenses > 0) {
            $items[] = ['description' => 'Operating Expenses Paid', 'amount' => -$expenses];
            $total -= $expenses;
        }

        return ['items' => $items, 'total' => $total];
    }

    protected function getInvestingCashFlows(string $startDate, string $endDate): array
    {
        $items = [];
        $total = 0;

        $assetPurchases = GeneralLedger::whereBetween('transaction_date', [$startDate, $endDate])
            ->whereHas('journalEntry', fn($q) => $q->where('entry_type', 'asset_purchase'))
            ->whereHas('account', fn($q) => $q->where('is_cash_account', true)->orWhere('is_bank_account', true))
            ->sum('credit_amount');

        if ($assetPurchases > 0) {
            $items[] = ['description' => 'Purchase of Fixed Assets', 'amount' => -$assetPurchases];
            $total -= $assetPurchases;
        }

        $assetDisposals = GeneralLedger::whereBetween('transaction_date', [$startDate, $endDate])
            ->whereHas('journalEntry', fn($q) => $q->where('entry_type', 'asset_disposal'))
            ->whereHas('account', fn($q) => $q->where('is_cash_account', true)->orWhere('is_bank_account', true))
            ->sum('debit_amount');

        if ($assetDisposals > 0) {
            $items[] = ['description' => 'Proceeds from Asset Disposal', 'amount' => $assetDisposals];
            $total += $assetDisposals;
        }

        return ['items' => $items, 'total' => $total];
    }

    protected function getFinancingCashFlows(string $startDate, string $endDate): array
    {
        $items = [];
        $total = 0;

        return ['items' => $items, 'total' => $total];
    }

    protected function getCashBalance(string $asOfDate): float
    {
        $cashAccounts = ChartOfAccount::where(function ($query) {
            $query->where('is_cash_account', true)
                  ->orWhere('is_bank_account', true);
        })->get();

        $totalBalance = 0;

        foreach ($cashAccounts as $account) {
            $ledgerBalance = GeneralLedger::where('account_id', $account->id)
                ->where('transaction_date', '<=', $asOfDate)
                ->selectRaw('SUM(debit_amount) - SUM(credit_amount) as balance')
                ->value('balance') ?? 0;

            $totalBalance += floatval($account->opening_balance) + floatval($ledgerBalance);
        }

        return $totalBalance;
    }

    public function getAccountSummary(): array
    {
        $summary = [];
        
        foreach (ChartOfAccount::getAccountTypes() as $type => $label) {
            $accounts = ChartOfAccount::active()
                ->where('account_type', $type)
                ->get();

            $totalBalance = 0;
            foreach ($accounts as $account) {
                $totalBalance += $account->current_balance;
            }

            $summary[$type] = [
                'label' => $label,
                'count' => $accounts->count(),
                'total_balance' => $totalBalance,
            ];
        }

        return $summary;
    }
}
