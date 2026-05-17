<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\Expense;
use App\Models\Accounting\FixedAsset;
use App\Services\Accounting\FinancialReportingService;
use Illuminate\Http\Request;

class AccountingDashboardController extends Controller
{
    protected FinancialReportingService $reportingService;

    public function __construct(FinancialReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    public function index()
    {
        $accountSummary = $this->reportingService->getAccountSummary();
        
        $recentEntries = JournalEntry::with(['createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $pendingApprovals = JournalEntry::where('status', 'pending_approval')->count();
        
        $pendingExpenses = Expense::where('status', 'pending_approval')->count();

        $today = now()->format('Y-m-d');
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = now()->endOfMonth()->format('Y-m-d');

        $monthlyIncome = $this->getMonthlyTotal('income', $startOfMonth, $endOfMonth);
        $monthlyExpenses = $this->getMonthlyTotal('expense', $startOfMonth, $endOfMonth);

        $cashBalance = $this->getCashAndBankBalance();

        $assetCount = FixedAsset::where('status', 'active')->count();
        $totalAssetValue = FixedAsset::where('status', 'active')->sum('current_value');

        return view('accounting.dashboard', compact(
            'accountSummary',
            'recentEntries',
            'pendingApprovals',
            'pendingExpenses',
            'monthlyIncome',
            'monthlyExpenses',
            'cashBalance',
            'assetCount',
            'totalAssetValue'
        ));
    }

    protected function getMonthlyTotal(string $type, string $startDate, string $endDate): float
    {
        $accounts = ChartOfAccount::where('account_type', $type)->pluck('id');
        
        return \App\Models\Accounting\GeneralLedger::whereIn('account_id', $accounts)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('SUM(CASE WHEN "' . $type . '" = "income" THEN credit_amount - debit_amount ELSE debit_amount - credit_amount END) as total')
            ->value('total') ?? 0;
    }

    protected function getCashAndBankBalance(): float
    {
        return ChartOfAccount::where(function ($q) {
            $q->where('is_cash_account', true)->orWhere('is_bank_account', true);
        })->sum('current_balance');
    }
}
