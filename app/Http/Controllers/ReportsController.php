<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\LoanSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /**
     * Display the reports dashboard.
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * Generate loan portfolio report.
     */
    public function loanPortfolio(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo   = $request->get('date_to',   now()->endOfMonth());

        // Use same schedule-based formula as main dashboard
        $activeLoanIds     = Loan::whereIn('status', ['disbursed', 'active', 'partially_paid'])->pluck('id');
        $schedPaidTotal    = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)->sum('paid_amount');
        $totalRepaidActive = (float) Repayment::whereIn('loan_id', $activeLoanIds)->sum('amount');

        if ($schedPaidTotal > 0 || $totalRepaidActive == 0) {
            $outstandingBalance = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
                ->whereIn('status', ['pending', 'partial'])
                ->selectRaw('SUM(total_amount - paid_amount) as remaining')
                ->value('remaining') ?? 0.0;
        } else {
            $totalRepayable     = (float) Loan::whereIn('id', $activeLoanIds)->sum('total_amount');
            $outstandingBalance = max(0.0, $totalRepayable - $totalRepaidActive);
        }
        $outstandingBalance = max(0.0, $outstandingBalance);

        $data = [
            'total_loans'       => Loan::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'total_disbursed'   => Loan::whereBetween('disbursed_at', [$dateFrom, $dateTo])->sum('principal'),
            'active_loans'      => Loan::whereIn('status', ['disbursed', 'active', 'partially_paid'])->count(),
            'completed_loans'   => Loan::where('status', 'completed')->count(),
            'overdue_loans'     => Loan::overdue()->count(),
            'total_outstanding' => $outstandingBalance,
            'portfolio_at_risk' => $this->calculatePortfolioAtRisk(),
            'loans_by_product'  => $this->getLoansByProduct($dateFrom, $dateTo),
            'loans_by_status'   => $this->getLoansByStatus($dateFrom, $dateTo),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
                'date_range' => [
                    'from' => $dateFrom,
                    'to' => $dateTo
                ]
            ]);
        }

        return view('reports.loan-portfolio', compact('data', 'dateFrom', 'dateTo'));
    }

    /**
     * Generate collections report.
     */
    public function collections(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $data = [
            'total_collections' => Repayment::whereBetween('payment_date', [$dateFrom, $dateTo])->sum('amount'),
            'collections_count' => Repayment::whereBetween('payment_date', [$dateFrom, $dateTo])->count(),
            'average_payment' => Repayment::whereBetween('payment_date', [$dateFrom, $dateTo])->avg('amount'),
            'collections_by_method' => $this->getCollectionsByMethod($dateFrom, $dateTo),
            'daily_collections' => $this->getDailyCollections($dateFrom, $dateTo),
            'collection_rate' => $this->calculateCollectionRate($dateFrom, $dateTo),
        ];

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('reports.collections', compact('data', 'dateFrom', 'dateTo'));
    }

    /**
     * Generate arrears aging report.
     */
    public function arrearsAging(Request $request)
    {
        $asOfDate = $request->get('as_of_date', now());

        $data = [
            'current' => $this->getArrearsAging(0, 0, $asOfDate),
            '1_30_days' => $this->getArrearsAging(1, 30, $asOfDate),
            '31_60_days' => $this->getArrearsAging(31, 60, $asOfDate),
            '61_90_days' => $this->getArrearsAging(61, 90, $asOfDate),
            'over_90_days' => $this->getArrearsAging(91, null, $asOfDate),
        ];

        $data['total'] = [
            'count' => array_sum(array_column($data, 'count')),
            'amount' => array_sum(array_column($data, 'amount')),
        ];

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('reports.arrears-aging', compact('data', 'asOfDate'));
    }

    /**
     * Generate profit and loss report.
     */
    public function profitLoss(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->get('date_to',   now()->endOfMonth()->toDateString());

        // ── REVENUE ──────────────────────────────────────────────────────────
        $interestIncome = $this->getInterestIncome($dateFrom, $dateTo);
        $feesIncome     = $this->getFeeIncome($dateFrom, $dateTo);
        $penaltyIncome  = $this->getPenaltyIncome($dateFrom, $dateTo);

        // ── EXPENSES – from accounting expenses table grouped by category ────
        try {
            $expensesByCategory = \App\Models\Accounting\Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
                ->whereIn('status', ['approved', 'paid'])
                ->with('category')
                ->get()
                ->groupBy(fn($e) => optional($e->category)->name ?? 'Other')
                ->map(fn($group) => $group->sum('amount'))
                ->sortByDesc(fn($v) => $v);
        } catch (\Exception $e) {
            $expensesByCategory = collect();
        }

        // ── EXPENSES – salaries paid in period ───────────────────────────────
        try {
            $salaryExpenses = \App\Models\Salary::whereBetween('payment_date', [$dateFrom, $dateTo])
                ->where('status', 'paid')
                ->sum('net_salary');
        } catch (\Exception $e) {
            $salaryExpenses = 0;
        }

        // ── EXPENSES – loan loss provision (schedule-based, no accessor) ─────
        $loanLossProvision = $this->getLoanLossProvision($dateFrom, $dateTo);

        // ── TOTALS ────────────────────────────────────────────────────────────
        $operatingExpenses = $expensesByCategory->sum();
        $totalExpenses     = $operatingExpenses + $salaryExpenses + $loanLossProvision;
        $totalRevenue      = $interestIncome + $feesIncome + $penaltyIncome;
        $netProfit         = $totalRevenue - $totalExpenses;

        $data = [
            'interest_income'      => $interestIncome,
            'fees_income'          => $feesIncome,
            'penalty_income'       => $penaltyIncome,
            'total_revenue'        => $totalRevenue,

            'expenses_by_category' => $expensesByCategory,
            'salary_expenses'      => $salaryExpenses,
            'provisions'           => $loanLossProvision,
            'operating_expenses'   => $operatingExpenses,
            'total_expenses'       => $totalExpenses,

            'net_profit'           => $netProfit,
        ];

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('reports.profit-loss', compact('data', 'dateFrom', 'dateTo'));
    }

    /**
     * Generate balance sheet report.
     */
    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->get('as_of_date', now());

        $data = [
            'assets' => [
                'cash' => 0, // To be implemented based on cash management
                'loan_portfolio' => Loan::whereIn('status', ['disbursed', 'active'])->sum('outstanding_balance'),
                'other_assets' => 0,
            ],
            'liabilities' => [
                'client_deposits' => 0, // To be implemented if deposit services are offered
                'other_liabilities' => 0,
            ],
            'equity' => [
                'retained_earnings' => 0, // To be calculated from P&L
                'capital' => 0,
            ],
        ];

        $data['total_assets'] = array_sum($data['assets']);
        $data['total_liabilities'] = array_sum($data['liabilities']);
        $data['total_equity'] = array_sum($data['equity']);

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('reports.balance-sheet', compact('data', 'asOfDate'));
    }

    /**
     * Generate client analysis report.
     */
    public function clientAnalysis(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $data = [
            'total_clients' => Client::count(),
            'active_clients' => Client::where('status', 'active')->count(),
            'new_clients' => Client::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'clients_with_loans' => Client::whereHas('loans')->count(),
            'clients_by_gender' => $this->getClientsByGender(),
            'top_clients' => $this->getTopClients($dateFrom, $dateTo),
        ];

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('reports.client-analysis', compact('data', 'dateFrom', 'dateTo'));
    }

    /**
     * Daily Portfolio Report.
     */
    public function dailyPortfolio(Request $request)
    {
        $date     = $request->get('date', now()->format('Y-m-d'));
        $tenantId = auth()->user()->tenant_id;

        $activeLoans = Loan::where('tenant_id', $tenantId)
            ->whereIn('status', ['disbursed', 'active']);

        $totalActive      = (clone $activeLoans)->count();
        $totalOutstanding = (clone $activeLoans)->sum('outstanding_balance');

        $disbursementAmount = Loan::where('tenant_id', $tenantId)
            ->whereDate('disbursed_at', $date)->sum('principal');
        $disbursementCount  = Loan::where('tenant_id', $tenantId)
            ->whereDate('disbursed_at', $date)->count();

        $collectionToday = Repayment::whereHas('loan', fn($q) => $q->where('tenant_id', $tenantId))
            ->whereDate('payment_date', $date)->sum('amount');
        $collectionCount = Repayment::whereHas('loan', fn($q) => $q->where('tenant_id', $tenantId))
            ->whereDate('payment_date', $date)->count();

        $overdueLoans   = Loan::where('tenant_id', $tenantId)->overdue();
        $overdueCount   = (clone $overdueLoans)->count();
        $overdueAmount  = (clone $overdueLoans)->sum('outstanding_balance');
        $parPercent     = $totalOutstanding > 0
            ? round($overdueAmount / $totalOutstanding * 100, 2) : 0;

        // 7-day trend
        $trend = collect();
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::parse($date)->subDays($i)->format('Y-m-d');
            $trend->push([
                'date'        => $d,
                'disbursed'   => Loan::where('tenant_id', $tenantId)->whereDate('disbursed_at', $d)->sum('principal'),
                'collections' => Repayment::whereHas('loan', fn($q) => $q->where('tenant_id', $tenantId))
                    ->whereDate('payment_date', $d)->sum('amount'),
            ]);
        }

        return view('reports.daily-portfolio', compact(
            'date', 'totalActive', 'totalOutstanding',
            'disbursementAmount', 'disbursementCount',
            'collectionToday', 'collectionCount',
            'overdueCount', 'overdueAmount', 'parPercent', 'trend'
        ));
    }

    /**
     * PAR (Portfolio at Risk) Report.
     */
    public function parReport(Request $request)
    {
        $asOf     = $request->get('as_of_date', now()->format('Y-m-d'));
        $tenantId = auth()->user()->tenant_id;

        $totalPortfolio = Loan::where('tenant_id', $tenantId)
            ->whereIn('status', ['disbursed', 'active'])
            ->sum('outstanding_balance');

        $parBrackets = [
            'par_1'  => ['days' => 1,  'min_days' => 1],
            'par_7'  => ['days' => 7,  'min_days' => 7],
            'par_30' => ['days' => 30, 'min_days' => 30],
            'par_90' => ['days' => 90, 'min_days' => 90],
        ];

        $par = [];
        foreach ($parBrackets as $key => $cfg) {
            $cutoff = Carbon::parse($asOf)->subDays($cfg['min_days']);
            $q = LoanSchedule::whereHas('loan', fn($lq) => $lq->where('tenant_id', $tenantId))
                ->where('status', 'pending')
                ->where('due_date', '<=', $cutoff);
            $amount  = $q->sum(DB::raw('total_amount - paid_amount'));
            $count   = $q->distinct('loan_id')->count('loan_id');
            $percent = $totalPortfolio > 0 ? round($amount / $totalPortfolio * 100, 2) : 0;
            $par[$key] = ['days' => $cfg['min_days'], 'count' => $count, 'amount' => $amount, 'percent' => $percent];
        }

        // PAR by product
        $parByProduct = Loan::where('tenant_id', $tenantId)
            ->whereHas('schedules', fn($q) => $q->where('status', 'pending')->where('due_date', '<', $asOf))
            ->selectRaw('product_id, COUNT(*) as count, SUM(outstanding_balance) as amount')
            ->with('product')
            ->groupBy('product_id')
            ->get();

        return view('reports.par-report', compact('asOf', 'totalPortfolio', 'par', 'parByProduct'));
    }

    /**
     * Branch Performance Report.
     */
    public function branchPerformance(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->get('date_to', now()->format('Y-m-d'));
        $tenantId = auth()->user()->tenant_id;

        $branches = \App\Models\Branch::where('tenant_id', $tenantId)->get();

        $branchData = $branches->map(function ($branch) use ($dateFrom, $dateTo) {
            $disbursedLoans = Loan::where('branch_id', $branch->id)
                ->whereBetween('disbursed_at', [$dateFrom, $dateTo])->get();
            $collectionQ = Repayment::whereHas('loan', fn($q) => $q->where('branch_id', $branch->id))
                ->whereBetween('payment_date', [$dateFrom, $dateTo]);

            return [
                'branch'           => $branch,
                'officer_count'    => \App\Models\User::where('branch_id', $branch->id)->count(),
                'active_borrowers' => Client::whereHas('loans', fn($q) => $q->where('branch_id', $branch->id)->whereIn('status', ['disbursed', 'active']))->count(),
                'active_loans'     => Loan::where('branch_id', $branch->id)->whereIn('status', ['disbursed', 'active'])->count(),
                'outstanding'      => Loan::where('branch_id', $branch->id)->whereIn('status', ['disbursed', 'active'])->sum('outstanding_balance'),
                'disbursed_count'  => $disbursedLoans->count(),
                'disbursed_amount' => $disbursedLoans->sum('principal'),
                'collection_count' => (clone $collectionQ)->count(),
                'collection_total' => (clone $collectionQ)->sum('amount'),
            ];
        });

        $totals = [
            'active_borrowers'  => $branchData->sum('active_borrowers'),
            'active_loans'      => $branchData->sum('active_loans'),
            'outstanding'       => $branchData->sum('outstanding'),
            'disbursed_amount'  => $branchData->sum('disbursed_amount'),
            'collection_total'  => $branchData->sum('collection_total'),
        ];

        return view('reports.branch-performance', compact('branchData', 'totals', 'dateFrom', 'dateTo'));
    }

    /**
     * Cash Position Report.
     */
    public function cashPosition(Request $request)
    {
        $asOf     = $request->get('as_of_date', now()->format('Y-m-d'));
        $tenantId = auth()->user()->tenant_id;

        // Cash in office = total collections - total disbursements (simplified)
        $totalCollections = Repayment::whereHas('loan', fn($q) => $q->where('tenant_id', $tenantId))
            ->whereDate('payment_date', '<=', $asOf)->sum('amount');
        $totalDisbursed   = Loan::where('tenant_id', $tenantId)
            ->whereDate('disbursed_at', '<=', $asOf)->sum('principal');

        $cashInOffice   = max(0, $totalCollections - $totalDisbursed);

        // Bank accounts from Chart of Accounts (bank/cash type)
        $bankAccounts = \App\Models\Accounting\ChartOfAccount::where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->where('account_code', 'like', '112%')
                  ->orWhere('account_code', 'like', '113%')
                  ->orWhere('account_name', 'like', '%bank%');
            })->get();

        $cashInBank     = $bankAccounts->sum('current_balance');
        $totalLiquidity = $cashInOffice + $cashInBank;

        $collectionsToday   = Repayment::whereHas('loan', fn($q) => $q->where('tenant_id', $tenantId))
            ->whereDate('payment_date', $asOf)->sum('amount');
        $disbursementsToday = Loan::where('tenant_id', $tenantId)
            ->whereDate('disbursed_at', $asOf)->sum('principal');

        // 14-day cash flow
        $cashFlow = collect();
        for ($i = 13; $i >= 0; $i--) {
            $d = Carbon::parse($asOf)->subDays($i)->format('Y-m-d');
            $cashFlow->push([
                'date'    => $d,
                'inflow'  => Repayment::whereHas('loan', fn($q) => $q->where('tenant_id', $tenantId))
                    ->whereDate('payment_date', $d)->sum('amount'),
                'outflow' => Loan::where('tenant_id', $tenantId)
                    ->whereDate('disbursed_at', $d)->sum('principal'),
            ]);
        }

        return view('reports.cash-position', compact(
            'asOf', 'cashInOffice', 'cashInBank', 'totalLiquidity',
            'bankAccounts', 'collectionsToday', 'disbursementsToday', 'cashFlow'
        ));
    }

    /**
     * Staff Activity Report.
     */
    public function staffActivity(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->get('date_to', now()->format('Y-m-d'));
        $tenantId = auth()->user()->tenant_id;

        $staff = \App\Models\User::where('tenant_id', $tenantId)->with('branch')->get();

        $staffData = $staff->map(function ($user) use ($dateFrom, $dateTo) {
            $loansCreated   = Loan::where('created_by', $user->id)->whereBetween('created_at', [$dateFrom, $dateTo]);
            $loansDisbursed = Loan::where('loan_officer_id', $user->id)->whereBetween('disbursed_at', [$dateFrom, $dateTo]);
            $collections    = Repayment::where('received_by', $user->id)->whereBetween('payment_date', [$dateFrom, $dateTo]);

            return [
                'user'               => $user,
                'loans_created'      => (clone $loansCreated)->count(),
                'loans_disbursed'    => (clone $loansDisbursed)->count(),
                'disbursed_amount'   => (clone $loansDisbursed)->sum('principal'),
                'collections_count'  => (clone $collections)->count(),
                'collections_amount' => (clone $collections)->sum('amount'),
                'active_portfolio'   => Loan::where('loan_officer_id', $user->id)->whereIn('status', ['disbursed', 'active'])->sum('outstanding_balance'),
                'overdue_loans'      => Loan::where('loan_officer_id', $user->id)->overdue()->count(),
            ];
        })->collect();

        $totals = [
            'loans_created'      => $staffData->sum('loans_created'),
            'loans_disbursed'    => $staffData->sum('loans_disbursed'),
            'disbursed_amount'   => $staffData->sum('disbursed_amount'),
            'collections_count'  => $staffData->sum('collections_count'),
            'collections_amount' => $staffData->sum('collections_amount'),
            'active_portfolio'   => $staffData->sum('active_portfolio'),
        ];

        return view('reports.staff-activity', compact('staffData', 'totals', 'dateFrom', 'dateTo'));
    }

    /**
     * Accounts reports index.
     */
    public function accountsIndex()
    {
        return view('reports.accounts.index');
    }

    /**
     * General Ledger report (placeholder).
     */
    public function generalLedger(Request $request)
    {
        return view('reports.accounts.general-ledger');
    }

    /**
     * Trial Balance report (placeholder).
     */
    public function trialBalance(Request $request)
    {
        return view('reports.accounts.trial-balance');
    }

    /**
     * Journal Entries report (admin only placeholder).
     */
    public function journalEntries(Request $request)
    {
        return view('reports.accounts.journal-entries');
    }

    /**
     * Cashbook report (placeholder).
     */
    public function cashBook(Request $request)
    {
        return view('reports.accounts.cashbook');
    }

    /**
     * Bankbook report (placeholder).
     */
    public function bankBook(Request $request)
    {
        return view('reports.accounts.bankbook');
    }

    /**
     * Chart of Accounts (admin only placeholder).
     */
    public function chartOfAccounts(Request $request)
    {
        return view('reports.accounts.chart-of-accounts');
    }

    /**
     * Client Ledger report (placeholder).
     */
    public function clientLedger(Request $request)
    {
        return view('reports.accounts.client-ledger');
    }

    /**
     * Income Categories (placeholder UI).
     */
    public function incomeCategories(Request $request)
    {
        return view('reports.accounts.income-categories');
    }

    /**
     * Expenditure Categories (placeholder UI).
     */
    public function expenditureCategories(Request $request)
    {
        return view('reports.accounts.expenditure-categories');
    }

    /**
     * Assets index + add asset form (placeholder UI).
     */
    public function assetsIndex(Request $request)
    {
        return view('reports.accounts.assets');
    }

    /**
     * Store asset (non-persistent placeholder: validates and flashes success).
     */
    public function storeAsset(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'acquired_on' => 'required|date',
            'description' => 'nullable|string|max:1000',
        ]);

        // Placeholder: no persistence implemented yet.
        // In a future iteration, this would create an Asset model record.

        return back()->with('status', 'Asset submitted successfully. (Storage to be implemented)');
    }

    /**
     * API: Dashboard metrics and chart data.
     */
    public function dashboardApi(Request $request)
    {
        $dateFrom = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('to', now()->format('Y-m-d'));
        $branchId = $request->get('branch_id');
        $staffId = $request->get('staff_id');
        $productId = $request->get('product_id');

        // Build base query with filters
        $loanQuery = Loan::query();
        $repaymentQuery = Repayment::query();

        if ($branchId) {
            $loanQuery->where('branch_id', $branchId);
            $repaymentQuery->whereHas('loan', fn($q) => $q->where('branch_id', $branchId));
        }
        if ($staffId) {
            $loanQuery->where('loan_officer_id', $staffId);
            $repaymentQuery->whereHas('loan', fn($q) => $q->where('loan_officer_id', $staffId));
        }
        if ($productId) {
            $loanQuery->where('product_id', $productId);
            $repaymentQuery->whereHas('loan', fn($q) => $q->where('product_id', $productId));
        }

        // Key metrics with filters
        $metrics = [
            'total_disbursed' => (clone $loanQuery)->whereBetween('disbursed_at', [$dateFrom, $dateTo])->sum('principal'),
            'disbursed_loans_count' => (clone $loanQuery)->whereBetween('disbursed_at', [$dateFrom, $dateTo])->count(),
            'total_collections' => (clone $repaymentQuery)->whereBetween('payment_date', [$dateFrom, $dateTo])->sum('amount'),
            'collection_rate' => $this->calculateCollectionRateFiltered($dateFrom, $dateTo, $branchId, $staffId, $productId),
            'outstanding_amount' => (clone $loanQuery)->whereIn('status', ['disbursed', 'active'])->sum('outstanding_balance'),
            'active_loans_count' => (clone $loanQuery)->whereIn('status', ['disbursed', 'active'])->count(),
            'overdue_amount' => (clone $loanQuery)->overdue()->sum('outstanding_balance'),
            'overdue_loans_count' => (clone $loanQuery)->overdue()->count(),
        ];

        // Chart data with filters
        $charts = [
            'disbursement' => $this->getDisbursementChartDataFiltered($dateFrom, $dateTo, $branchId, $staffId, $productId),
            'collection' => $this->getCollectionChartDataFiltered($dateFrom, $dateTo, $branchId, $staffId, $productId),
            'portfolio' => $this->getPortfolioChartDataFiltered($branchId, $staffId, $productId),
            'products' => $this->getProductsChartDataFiltered($dateFrom, $dateTo, $branchId, $staffId, $productId),
        ];

        return response()->json([
            'metrics' => $metrics,
            'charts' => $charts,
        ]);
    }

    /**
     * API: Generate specific report.
     */
    public function generateApi(Request $request)
    {
        $type = $request->get('type');
        $dateFrom = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('to', now()->format('Y-m-d'));

        // Route to appropriate report page
        $routes = [
            'profit_loss' => 'reports.profit-loss',
            'balance_sheet' => 'reports.balance-sheet',
            'cash_flow' => 'accounting.reports.cash-flow',
            'loan_portfolio' => 'reports.loan-portfolio',
            'collection_summary' => 'reports.collections',
            'client_analysis' => 'reports.client-analysis',
            'arrears_aging' => 'reports.arrears-aging',
            'par_analysis' => 'reports.loan-portfolio',
            'regulatory_report' => 'reports.index',
        ];

        $routeName = $routes[$type] ?? 'reports.index';

        return response()->json([
            'redirect' => route($routeName, ['date_from' => $dateFrom, 'date_to' => $dateTo]),
        ]);
    }

    /**
     * API: Export all reports.
     */
    public function exportAllApi(Request $request)
    {
        return response()->json([
            'message' => 'Export all reports functionality coming soon.',
        ]);
    }

    /**
     * Get disbursement chart data.
     */
    private function getDisbursementChartData($dateFrom, $dateTo)
    {
        $data = Loan::whereBetween('disbursed_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(disbursed_at) as date, SUM(principal) as amount')
            ->groupBy(DB::raw('DATE(disbursed_at)'))
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d'))->toArray(),
            'data' => $data->pluck('amount')->toArray(),
        ];
    }

    /**
     * Get collection chart data.
     */
    private function getCollectionChartData($dateFrom, $dateTo)
    {
        $data = Repayment::whereBetween('payment_date', [$dateFrom, $dateTo])
            ->selectRaw('DATE(payment_date) as date, SUM(amount) as amount')
            ->groupBy(DB::raw('DATE(payment_date)'))
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d'))->toArray(),
            'data' => $data->pluck('amount')->toArray(),
        ];
    }

    /**
     * Get portfolio quality chart data.
     */
    private function getPortfolioChartData()
    {
        $current = Loan::whereIn('status', ['disbursed', 'active'])
            ->whereDoesntHave('schedules', fn($q) => $q->where('status', 'pending')->where('due_date', '<', now()))
            ->sum('outstanding_balance');

        $overdue = Loan::overdue()->sum('outstanding_balance');

        return [
            'labels' => ['Current', 'Overdue'],
            'data' => [$current, $overdue],
        ];
    }

    /**
     * Get products chart data.
     */
    private function getProductsChartData($dateFrom, $dateTo)
    {
        $data = Loan::with('product')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('product_id, COUNT(*) as count')
            ->groupBy('product_id')
            ->get();

        return [
            'labels' => $data->map(fn($d) => $d->product->name ?? 'Unknown')->toArray(),
            'data' => $data->pluck('count')->toArray(),
        ];
    }

    /**
     * Calculate collection rate with filters.
     */
    private function calculateCollectionRateFiltered($dateFrom, $dateTo, $branchId, $staffId, $productId)
    {
        $scheduleQuery = LoanSchedule::whereBetween('due_date', [$dateFrom, $dateTo]);
        
        if ($branchId || $staffId || $productId) {
            $scheduleQuery->whereHas('loan', function ($q) use ($branchId, $staffId, $productId) {
                if ($branchId) $q->where('branch_id', $branchId);
                if ($staffId) $q->where('loan_officer_id', $staffId);
                if ($productId) $q->where('product_id', $productId);
            });
        }

        $expectedCollections = (clone $scheduleQuery)->sum('total_amount');
        if ($expectedCollections == 0) return 100;

        $actualCollections = (clone $scheduleQuery)->sum('paid_amount');
        return round(($actualCollections / $expectedCollections) * 100, 2);
    }

    /**
     * Get disbursement chart data with filters.
     */
    private function getDisbursementChartDataFiltered($dateFrom, $dateTo, $branchId, $staffId, $productId)
    {
        $query = Loan::whereBetween('disbursed_at', [$dateFrom, $dateTo]);
        if ($branchId) $query->where('branch_id', $branchId);
        if ($staffId) $query->where('loan_officer_id', $staffId);
        if ($productId) $query->where('product_id', $productId);

        $data = $query->selectRaw('DATE(disbursed_at) as date, SUM(principal) as amount')
            ->groupBy(DB::raw('DATE(disbursed_at)'))
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d'))->toArray(),
            'data' => $data->pluck('amount')->toArray(),
        ];
    }

    /**
     * Get collection chart data with filters.
     */
    private function getCollectionChartDataFiltered($dateFrom, $dateTo, $branchId, $staffId, $productId)
    {
        $query = Repayment::whereBetween('payment_date', [$dateFrom, $dateTo]);
        
        if ($branchId || $staffId || $productId) {
            $query->whereHas('loan', function ($q) use ($branchId, $staffId, $productId) {
                if ($branchId) $q->where('branch_id', $branchId);
                if ($staffId) $q->where('loan_officer_id', $staffId);
                if ($productId) $q->where('product_id', $productId);
            });
        }

        $data = $query->selectRaw('DATE(payment_date) as date, SUM(amount) as amount')
            ->groupBy(DB::raw('DATE(payment_date)'))
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d'))->toArray(),
            'data' => $data->pluck('amount')->toArray(),
        ];
    }

    /**
     * Get portfolio chart data with filters.
     */
    private function getPortfolioChartDataFiltered($branchId, $staffId, $productId)
    {
        $query = Loan::whereIn('status', ['disbursed', 'active']);
        if ($branchId) $query->where('branch_id', $branchId);
        if ($staffId) $query->where('loan_officer_id', $staffId);
        if ($productId) $query->where('product_id', $productId);

        $current = (clone $query)->whereDoesntHave('schedules', fn($q) => $q->where('status', 'pending')->where('due_date', '<', now()))->sum('outstanding_balance');
        $overdue = (clone $query)->overdue()->sum('outstanding_balance');

        return [
            'labels' => ['Current', 'Overdue'],
            'data' => [$current, $overdue],
        ];
    }

    /**
     * Get products chart data with filters.
     */
    private function getProductsChartDataFiltered($dateFrom, $dateTo, $branchId, $staffId, $productId)
    {
        $query = Loan::with('product')->whereBetween('created_at', [$dateFrom, $dateTo]);
        if ($branchId) $query->where('branch_id', $branchId);
        if ($staffId) $query->where('loan_officer_id', $staffId);
        if ($productId) $query->where('product_id', $productId);

        $data = $query->selectRaw('product_id, COUNT(*) as count')->groupBy('product_id')->get();

        return [
            'labels' => $data->map(fn($d) => $d->product->name ?? 'Unknown')->toArray(),
            'data' => $data->pluck('count')->toArray(),
        ];
    }

    /**
     * Export report to PDF.
     */
    public function exportPdf(Request $request)
    {
        $reportType = $request->get('type');
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        // Generate report data based on type
        switch ($reportType) {
            case 'loan-portfolio':
                $data = $this->loanPortfolio($request)->getData();
                break;
            case 'collections':
                $data = $this->collections($request)->getData();
                break;
            case 'arrears-aging':
                $data = $this->arrearsAging($request)->getData();
                break;
            default:
                abort(400, 'Invalid report type');
        }

        // Generate PDF (implementation depends on PDF library)
        // For now, return JSON
        return response()->json([
            'message' => 'PDF export functionality to be implemented',
            'data' => $data
        ]);
    }

    /**
     * Calculate Portfolio at Risk — same schedule-based formula as the main dashboard.
     */
    private function calculatePortfolioAtRisk()
    {
        $activeLoanIds = Loan::whereIn('status', ['disbursed', 'active', 'partially_paid'])->pluck('id');

        $schedPaidTotal    = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)->sum('paid_amount');
        $totalRepaidActive = (float) Repayment::whereIn('loan_id', $activeLoanIds)->sum('amount');

        if ($schedPaidTotal > 0 || $totalRepaidActive == 0) {
            $outstandingBalance = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
                ->whereIn('status', ['pending', 'partial'])
                ->selectRaw('SUM(total_amount - paid_amount) as remaining')
                ->value('remaining') ?? 0.0;
        } else {
            $totalRepayable     = (float) Loan::whereIn('id', $activeLoanIds)->sum('total_amount');
            $outstandingBalance = max(0.0, $totalRepayable - $totalRepaidActive);
        }
        $outstandingBalance = max(0.0, $outstandingBalance);

        $overdueScheduleAmount = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', now()->toDateString())
            ->selectRaw('SUM(total_amount - paid_amount) as overdue_remaining')
            ->value('overdue_remaining') ?? 0.0;

        return $outstandingBalance > 0
            ? round(($overdueScheduleAmount / $outstandingBalance) * 100, 2)
            : 0.0;
    }

    /**
     * Get loans by product.
     */
    private function getLoansByProduct($dateFrom, $dateTo)
    {
        return Loan::with('product')
                   ->whereBetween('created_at', [$dateFrom, $dateTo])
                   ->select('product_id', DB::raw('count(*) as count'), DB::raw('sum(principal) as amount'))
                   ->groupBy('product_id')
                   ->get();
    }

    /**
     * Get loans by status.
     */
    private function getLoansByStatus($dateFrom, $dateTo)
    {
        return Loan::whereBetween('created_at', [$dateFrom, $dateTo])
                   ->select('status', DB::raw('count(*) as count'), DB::raw('sum(principal) as amount'))
                   ->groupBy('status')
                   ->get();
    }

    /**
     * Get collections by payment method.
     */
    private function getCollectionsByMethod($dateFrom, $dateTo)
    {
        return Repayment::whereBetween('payment_date', [$dateFrom, $dateTo])
                       ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount) as amount'))
                       ->groupBy('payment_method')
                       ->get();
    }

    /**
     * Get daily collections.
     */
    private function getDailyCollections($dateFrom, $dateTo)
    {
        return Repayment::whereBetween('payment_date', [$dateFrom, $dateTo])
                       ->select(DB::raw('DATE(payment_date) as date'), DB::raw('sum(amount) as amount'))
                       ->groupBy(DB::raw('DATE(payment_date)'))
                       ->orderBy('date')
                       ->get();
    }

    /**
     * Calculate collection rate.
     */
    private function calculateCollectionRate($dateFrom, $dateTo)
    {
        $expectedCollections = LoanSchedule::whereBetween('due_date', [$dateFrom, $dateTo])
                                          ->sum('total_amount');

        if ($expectedCollections == 0) {
            return 100;
        }

        $actualCollections = LoanSchedule::whereBetween('due_date', [$dateFrom, $dateTo])
                                        ->sum('paid_amount');

        return round(($actualCollections / $expectedCollections) * 100, 2);
    }

    /**
     * Get arrears aging data.
     */
    private function getArrearsAging($minDays, $maxDays, $asOfDate)
    {
        $query = LoanSchedule::where('status', 'pending')
                            ->where('due_date', '<', $asOfDate);

        if ($minDays > 0) {
            $query->where('due_date', '<=', Carbon::parse($asOfDate)->subDays($minDays));
        }

        if ($maxDays) {
            $query->where('due_date', '>', Carbon::parse($asOfDate)->subDays($maxDays));
        }

        return [
            'count' => $query->count(),
            'amount' => $query->sum(DB::raw('total_amount - paid_amount')),
        ];
    }

    /**
     * Get interest income.
     */
    private function getInterestIncome($dateFrom, $dateTo)
    {
        // Method 1: Try to get from linked schedules
        $interestFromSchedules = Repayment::whereBetween('payment_date', [$dateFrom, $dateTo])
                       ->whereNotNull('schedule_id')
                       ->join('loan_schedules', 'repayments.schedule_id', '=', 'loan_schedules.id')
                       ->sum('loan_schedules.interest_amount');
        
        if ($interestFromSchedules > 0) {
            return $interestFromSchedules;
        }
        
        // Method 2: Calculate interest from repayments based on loan's monthly payment structure
        $repayments = Repayment::with('loan')
                       ->whereBetween('payment_date', [$dateFrom, $dateTo])
                       ->get();

        $totalInterest = 0;
        foreach ($repayments as $repayment) {
            if (!$repayment->loan) continue;
            $loan = $repayment->loan;

            $term             = (int) ($loan->term ?? 0);
            $principal        = (float) ($loan->principal ?? 0);
            $monthlyPayment   = (float) ($loan->monthly_payment ?? 0);

            if ($term <= 0 || $principal <= 0 || $monthlyPayment <= 0) continue;

            $principalPerMonth = $principal / $term;
            $interestPerMonth  = $monthlyPayment - $principalPerMonth;
            $repaymentRatio    = min(1, $repayment->amount / $monthlyPayment);
            $totalInterest    += max(0, $interestPerMonth * $repaymentRatio);
        }

        return round($totalInterest, 2);
    }

    /**
     * Get fee income.
     */
    private function getFeeIncome($dateFrom, $dateTo)
    {
        return Loan::whereBetween('created_at', [$dateFrom, $dateTo])
                   ->sum('processing_fee');
    }

    /**
     * Get penalty income.
     */
    private function getPenaltyIncome($dateFrom, $dateTo)
    {
        // Sum penalty/late fee amounts paid within the period
        $fromRepayments = Repayment::whereBetween('payment_date', [$dateFrom, $dateTo])
            ->where(function ($q) {
                $q->whereNotNull('penalty_amount')
                  ->where('penalty_amount', '>', 0);
            })
            ->sum('penalty_amount');

        if ($fromRepayments > 0) {
            return $fromRepayments;
        }

        // Fallback: sum from loan schedules penalty_amount paid in period
        return LoanSchedule::whereBetween('updated_at', [$dateFrom, $dateTo])
            ->where('status', 'paid')
            ->whereNotNull('penalty_amount')
            ->sum('penalty_amount');
    }

    /**
     * Get loan loss provision (5% of overdue schedule balance — real DB columns only).
     */
    private function getLoanLossProvision($dateFrom, $dateTo)
    {
        $activeLoanIds = Loan::whereIn('status', ['disbursed', 'active', 'partially_paid'])->pluck('id');

        $overdueAmount = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', now()->toDateString())
            ->selectRaw('SUM(total_amount - paid_amount) as overdue_remaining')
            ->value('overdue_remaining');

        return round($overdueAmount * 0.05, 2);
    }

    /**
     * Get clients by gender.
     */
    private function getClientsByGender()
    {
        return Client::select('gender', DB::raw('count(*) as count'))
                     ->groupBy('gender')
                     ->get();
    }

    /**
     * Get top clients by loan amount.
     */
    private function getTopClients($dateFrom, $dateTo)
    {
        return Client::with('loans')
                     ->whereHas('loans', function ($query) use ($dateFrom, $dateTo) {
                         $query->whereBetween('created_at', [$dateFrom, $dateTo]);
                     })
                     ->withSum(['loans' => function ($query) use ($dateFrom, $dateTo) {
                         $query->whereBetween('created_at', [$dateFrom, $dateTo]);
                     }], 'principal')
                     ->orderByDesc('loans_sum_principal')
                     ->take(10)
                     ->get();
    }
}