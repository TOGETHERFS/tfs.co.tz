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
        return redirect()->route('reports.daily-transactions');
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

    // =========================================================================
    // A. TRANSACTION REPORTS
    // =========================================================================

    /**
     * Daily Transactions Report — repayments + disbursements in a date range.
     */
    public function dailyTransactions(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->format('Y-m-d'));
        $dateTo   = $request->get('date_to',   now()->format('Y-m-d'));
        $type     = $request->get('type', '');      // repayment|disbursement|''
        $method   = $request->get('payment_method', '');
        $search   = $request->get('search', '');

        // ── REPAYMENTS ────────────────────────────────────────────────────────
        $repQuery = Repayment::with(['loan.client', 'user'])
            ->whereBetween('payment_date', [$dateFrom, $dateTo]);

        if ($method) $repQuery->where('payment_method', $method);

        if ($search) {
            $repQuery->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhereHas('loan', fn($lq) => $lq->where('loan_number', 'like', "%{$search}%")
                      ->orWhereHas('client', fn($cq) => $cq->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")));
            });
        }

        $repayments = (!$type || $type === 'repayment') ? $repQuery->get() : collect();

        // ── DISBURSEMENTS ─────────────────────────────────────────────────────
        $disQuery = Loan::with(['client', 'user'])
            ->whereNotNull('disbursed_at')
            ->whereBetween('disbursed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        if ($search) {
            $disQuery->where(function ($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($cq) => $cq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        $disbursements = (!$type || $type === 'disbursement') ? $disQuery->get() : collect();

        // ── TOTALS ────────────────────────────────────────────────────────────
        $totals = [
            'total_repayments'   => $repayments->sum('amount'),
            'repayment_count'    => $repayments->count(),
            'total_disbursed'    => $disbursements->sum('principal'),
            'disbursement_count' => $disbursements->count(),
        ];

        return view('reports.daily-transactions', compact(
            'repayments', 'disbursements', 'totals',
            'dateFrom', 'dateTo', 'type', 'method', 'search'
        ));
    }

    /**
     * Expense Report.
     */
    public function expenseReport(Request $request)
    {
        $dateFrom   = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo     = $request->get('date_to',   now()->format('Y-m-d'));
        $categoryId = $request->get('category_id', '');
        $search     = $request->get('search', '');

        try {
            $query = \App\Models\Accounting\Expense::with(['category', 'createdBy'])
                ->whereBetween('expense_date', [$dateFrom, $dateTo]);

            if ($categoryId) $query->where('category_id', $categoryId);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('expense_number', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('payee', 'like', "%{$search}%");
                });
            }

            $expenses   = $query->orderBy('expense_date', 'desc')->paginate(25)->withQueryString();
            $totalAmount = \App\Models\Accounting\Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
                ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
                ->sum('amount');

            $categories = \App\Models\Accounting\ExpenseCategory::orderBy('name')->get();
        } catch (\Exception $e) {
            $expenses    = collect();
            $totalAmount = 0;
            $categories  = collect();
        }

        return view('reports.expense-report', compact(
            'expenses', 'totalAmount', 'categories',
            'dateFrom', 'dateTo', 'categoryId', 'search'
        ));
    }

    /**
     * Fee Income Report — processing fees collected on loans.
     * Excludes principal and interest repayments to prevent double-counting.
     */
    public function feeIncome(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->get('date_to',   now()->format('Y-m-d'));
        $search   = $request->get('search', '');

        // Fee income = processing_fee on disbursed loans in the period
        $query = Loan::with(['client', 'user'])
            ->whereNotNull('disbursed_at')
            ->where('processing_fee', '>', 0)
            ->whereBetween('disbursed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($cq) => $cq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        $feeLoans       = $query->orderBy('disbursed_at', 'desc')->paginate(25)->withQueryString();
        $totalFeeIncome = Loan::withoutGlobalScope('tenant')->where('tenant_id', auth()->user()->tenant_id)->whereNotNull('disbursed_at')
            ->where('processing_fee', '>', 0)
            ->whereBetween('disbursed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->sum('processing_fee');

        return view('reports.fee-income', compact(
            'feeLoans', 'totalFeeIncome', 'dateFrom', 'dateTo', 'search'
        ));
    }

    // =========================================================================
    // B. LOAN REPORTS
    // =========================================================================

    /**
     * Loan Arrears Report — overdue installments only.
     * Uses schedule-based calculation; a loan is in arrears only when it has
     * past-due unpaid/partial schedules.
     */
    public function loanArrears(Request $request)
    {
        $asOfDate = $request->get('as_of_date', now()->format('Y-m-d'));
        $search   = $request->get('search', '');
        $minDays  = (int) $request->get('min_days', 1);

        $query = Loan::with(['client', 'schedules', 'user'])
            ->whereIn('status', ['disbursed', 'active', 'partially_paid'])
            ->whereHas('schedules', function ($q) use ($asOfDate) {
                $q->whereIn('status', ['pending', 'partial'])
                  ->where('due_date', '<', $asOfDate);
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($cq) => $cq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        $loans = $query->get()->map(function ($loan) use ($asOfDate) {
            $overdueSchedules = $loan->schedules->filter(fn($s) =>
                in_array($s->status, ['pending', 'partial']) && $s->due_date < $asOfDate
            );
            $loan->overdue_amount  = $overdueSchedules->sum(fn($s) => $s->total_amount - $s->paid_amount);
            $loan->overdue_count   = $overdueSchedules->count();
            $loan->oldest_due_date = $overdueSchedules->min('due_date');
            $loan->days_overdue    = $loan->oldest_due_date
                ? (int) now()->diffInDays(\Carbon\Carbon::parse($loan->oldest_due_date), false) * -1
                : 0;
            return $loan;
        })->filter(fn($l) => $l->days_overdue >= $minDays)->values();

        $totals = [
            'loan_count'     => $loans->count(),
            'overdue_amount' => $loans->sum('overdue_amount'),
        ];

        return view('reports.loan-arrears', compact(
            'loans', 'totals', 'asOfDate', 'search', 'minDays'
        ));
    }

    /**
     * Loan Outstanding Report.
     */
    public function loanOutstanding(Request $request)
    {
        $search    = $request->get('search', '');
        $productId = $request->get('product_id', '');
        $status    = $request->get('status', '');

        $query = Loan::with(['client', 'product', 'schedules'])
            ->whereIn('status', ['disbursed', 'active', 'partially_paid']);

        if ($productId) $query->where('product_id', $productId);
        if ($status)    $query->where('status', $status);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($cq) => $cq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        $loans = $query->orderBy('disbursed_at', 'desc')->paginate(25)->withQueryString();

        // Compute per-loan outstanding using schedule logic
        $loans->getCollection()->transform(function ($loan) {
            $schedPaid     = (float) $loan->schedules->sum('paid_amount');
            $totalRepayable = (float) $loan->total_amount;
            $principalRepaid = $loan->schedules
                ->where('status', 'paid')
                ->sum('principal_amount');
            $interestRepaid  = $loan->schedules
                ->where('status', 'paid')
                ->sum('interest_amount');
            $outstandingPrincipal = max(0, (float)$loan->principal - $principalRepaid);
            $outstandingInterest  = max(0, ($totalRepayable - (float)$loan->principal) - $interestRepaid);
            $loan->principal_repaid       = round($principalRepaid, 2);
            $loan->outstanding_principal  = round($outstandingPrincipal, 2);
            $loan->outstanding_interest   = round($outstandingInterest, 2);
            $loan->total_outstanding      = round($outstandingPrincipal + $outstandingInterest, 2);
            return $loan;
        });

        $products = \App\Models\LoanProduct::orderBy('name')->get();

        $totals = [
            'original_principal'  => $loans->getCollection()->sum('principal'),
            'principal_repaid'    => $loans->getCollection()->sum('principal_repaid'),
            'outstanding_principal' => $loans->getCollection()->sum('outstanding_principal'),
            'outstanding_interest'  => $loans->getCollection()->sum('outstanding_interest'),
            'total_outstanding'     => $loans->getCollection()->sum('total_outstanding'),
        ];

        return view('reports.loan-outstanding', compact(
            'loans', 'totals', 'products', 'productId', 'status', 'search'
        ));
    }

    /**
     * Loan Disbursement Report — actual disbursements only.
     */
    public function loanDisbursement(Request $request)
    {
        $dateFrom  = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo    = $request->get('date_to',   now()->format('Y-m-d'));
        $productId = $request->get('product_id', '');
        $search    = $request->get('search', '');

        $query = Loan::with(['client', 'product', 'user'])
            ->whereNotNull('disbursed_at')
            ->whereBetween('disbursed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        if ($productId) $query->where('product_id', $productId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($cq) => $cq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        $loans    = $query->orderBy('disbursed_at', 'desc')->paginate(25)->withQueryString();
        $products = \App\Models\LoanProduct::orderBy('name')->get();

        $totals = [
            'count'            => Loan::whereNotNull('disbursed_at')
                ->whereBetween('disbursed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->when($productId, fn($q) => $q->where('product_id', $productId))
                ->count(),
            'total_disbursed'  => Loan::whereNotNull('disbursed_at')
                ->whereBetween('disbursed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->when($productId, fn($q) => $q->where('product_id', $productId))
                ->sum('principal'),
            'total_fees'       => Loan::whereNotNull('disbursed_at')
                ->whereBetween('disbursed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->when($productId, fn($q) => $q->where('product_id', $productId))
                ->sum('processing_fee'),
        ];

        return view('reports.loan-disbursement', compact(
            'loans', 'totals', 'products', 'productId', 'dateFrom', 'dateTo', 'search'
        ));
    }

    /**
     * Expected Loan Repayment Report — installment schedule with status.
     */
    public function expectedRepayment(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->format('Y-m-d'));
        $dateTo   = $request->get('date_to',   now()->addDays(30)->format('Y-m-d'));
        $status   = $request->get('status', '');   // upcoming|due_today|partially_paid|paid|overdue
        $search   = $request->get('search', '');

        $today = now()->format('Y-m-d');

        $query = LoanSchedule::with(['loan.client'])
            ->whereHas('loan', fn($lq) => $lq->whereIn('status', ['disbursed', 'active', 'partially_paid']));

        if (!$status) {
            $query->whereBetween('due_date', [$dateFrom, $dateTo]);
        } else {
            switch ($status) {
                case 'upcoming':
                    $query->where('due_date', '>', $today)->whereIn('status', ['pending', 'partial']);
                    break;
                case 'due_today':
                    $query->where('due_date', $today)->whereIn('status', ['pending', 'partial']);
                    break;
                case 'partially_paid':
                    $query->where('status', 'partial');
                    break;
                case 'paid':
                    $query->where('status', 'paid')
                          ->whereBetween('due_date', [$dateFrom, $dateTo]);
                    break;
                case 'overdue':
                    $query->whereIn('status', ['pending', 'partial'])->where('due_date', '<', $today);
                    break;
                default:
                    $query->whereBetween('due_date', [$dateFrom, $dateTo]);
            }
        }

        if ($search) {
            $query->whereHas('loan', fn($lq) =>
                $lq->where('loan_number', 'like', "%{$search}%")
                   ->orWhereHas('client', fn($cq) => $cq->where('first_name', 'like', "%{$search}%")
                       ->orWhere('last_name', 'like', "%{$search}%"))
            );
        }

        $schedules = $query->orderBy('due_date')->paginate(25)->withQueryString();

        $totals = [
            'total_expected' => $schedules->getCollection()->sum('total_amount'),
            'total_paid'     => $schedules->getCollection()->sum('paid_amount'),
            'total_remaining'=> $schedules->getCollection()->sum(fn($s) => max(0, $s->total_amount - $s->paid_amount)),
        ];

        return view('reports.expected-repayment', compact(
            'schedules', 'totals', 'dateFrom', 'dateTo', 'status', 'search', 'today'
        ));
    }

    /**
     * Loan Transaction Report — repayments with breakdown.
     */
    public function loanTransactions(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->get('date_to',   now()->format('Y-m-d'));
        $method   = $request->get('payment_method', '');
        $search   = $request->get('search', '');

        $query = Repayment::with(['loan.client', 'user'])
            ->whereBetween('payment_date', [$dateFrom, $dateTo]);

        if ($method) $query->where('payment_method', $method);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhereHas('loan', fn($lq) => $lq->where('loan_number', 'like', "%{$search}%")
                      ->orWhereHas('client', fn($cq) => $cq->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")));
            });
        }

        $transactions = $query->orderBy('payment_date', 'desc')->paginate(25)->withQueryString();

        $totals = [
            'total_amount' => Repayment::whereBetween('payment_date', [$dateFrom, $dateTo])
                ->when($method, fn($q) => $q->where('payment_method', $method))
                ->sum('amount'),
            'count'        => Repayment::whereBetween('payment_date', [$dateFrom, $dateTo])
                ->when($method, fn($q) => $q->where('payment_method', $method))
                ->count(),
        ];

        return view('reports.loan-transactions', compact(
            'transactions', 'totals', 'dateFrom', 'dateTo', 'method', 'search'
        ));
    }

    /**
     * Summarized PAR Report.
     * PAR = outstanding balance of loans with at least 1 overdue unpaid installment /
     *       total outstanding balance of all active loans.
     */
    public function parSummary(Request $request)
    {
        $asOf = $request->get('as_of_date', now()->format('Y-m-d'));

        $activeLoanIds = Loan::whereIn('status', ['disbursed', 'active', 'partially_paid'])->pluck('id');

        // Total outstanding (schedule-based)
        $schedPaidTotal = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)->sum('paid_amount');
        if ($schedPaidTotal > 0) {
            $totalOutstanding = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
                ->whereIn('status', ['pending', 'partial'])
                ->selectRaw('SUM(total_amount - paid_amount) as rem')->value('rem') ?? 0;
        } else {
            $totalOutstanding = max(0, (float) Loan::whereIn('id', $activeLoanIds)->sum('total_amount')
                - (float) Repayment::whereIn('loan_id', $activeLoanIds)->sum('amount'));
        }

        // PAR brackets — based on loan outstanding balance for loans meeting the threshold
        $brackets = [
            'par_1'  => 1,
            'par_30' => 30,
            'par_60' => 60,
            'par_90' => 90,
        ];

        $parData = [];
        foreach ($brackets as $key => $days) {
            $cutoff = \Carbon\Carbon::parse($asOf)->subDays($days)->format('Y-m-d');
            // Loans in arrears for >= $days: have an overdue schedule older than $cutoff
            $loanIdsAtRisk = LoanSchedule::whereIn('loan_id', $activeLoanIds)
                ->whereIn('status', ['pending', 'partial'])
                ->where('due_date', '<=', $cutoff)
                ->distinct()->pluck('loan_id');

            // Outstanding balance for those loans (schedule-based)
            if ($schedPaidTotal > 0) {
                $atRiskBalance = (float) LoanSchedule::whereIn('loan_id', $loanIdsAtRisk)
                    ->whereIn('status', ['pending', 'partial'])
                    ->selectRaw('SUM(total_amount - paid_amount) as rem')->value('rem') ?? 0;
            } else {
                $atRiskBalance = max(0, (float) Loan::whereIn('id', $loanIdsAtRisk)->sum('total_amount')
                    - (float) Repayment::whereIn('loan_id', $loanIdsAtRisk)->sum('amount'));
            }

            $parData[$key] = [
                'days'       => $days,
                'loan_count' => $loanIdsAtRisk->count(),
                'amount'     => round($atRiskBalance, 2),
                'percent'    => $totalOutstanding > 0 ? round($atRiskBalance / $totalOutstanding * 100, 2) : 0,
            ];
        }

        $summary = [
            'total_active_loans'  => $activeLoanIds->count(),
            'total_outstanding'   => round($totalOutstanding, 2),
            'loans_in_arrears'    => LoanSchedule::whereIn('loan_id', $activeLoanIds)
                ->whereIn('status', ['pending', 'partial'])
                ->where('due_date', '<', $asOf)
                ->distinct()->count('loan_id'),
        ];

        return view('reports.par-summary', compact('parData', 'summary', 'asOf'));
    }

    /**
     * Accrued Interest Report.
     * Accrued interest = total_amount - principal on active loans.
     * Interest paid = sum of (interest_amount) on paid schedules.
     */
    public function accruedInterest(Request $request)
    {
        $search    = $request->get('search', '');
        $productId = $request->get('product_id', '');

        $query = Loan::with(['client', 'product', 'schedules'])
            ->whereIn('status', ['disbursed', 'active', 'partially_paid']);

        if ($productId) $query->where('product_id', $productId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($cq) => $cq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        $loans = $query->orderBy('disbursed_at', 'desc')->paginate(25)->withQueryString();

        $loans->getCollection()->transform(function ($loan) {
            $totalInterest   = max(0, (float)$loan->total_amount - (float)$loan->principal);
            $interestPaid    = (float) $loan->schedules->whereIn('status', ['paid'])->sum('interest_amount');
            $interestAccrued = $totalInterest;           // total contracted interest
            $interestOut     = max(0, $totalInterest - $interestPaid);
            $loan->total_interest       = round($totalInterest, 2);
            $loan->interest_paid        = round($interestPaid, 2);
            $loan->interest_accrued     = round($interestAccrued, 2);
            $loan->interest_outstanding = round($interestOut, 2);
            return $loan;
        });

        $products = \App\Models\LoanProduct::orderBy('name')->get();

        $totals = [
            'total_interest'       => $loans->getCollection()->sum('total_interest'),
            'interest_paid'        => $loans->getCollection()->sum('interest_paid'),
            'interest_outstanding' => $loans->getCollection()->sum('interest_outstanding'),
        ];

        return view('reports.accrued-interest', compact(
            'loans', 'totals', 'products', 'productId', 'search'
        ));
    }

    // =========================================================================
    // C. STATEMENT REPORTS
    // =========================================================================

    /**
     * Account Statement Report — loan-level repayment history as a statement.
     */
    public function accountStatement(Request $request)
    {
        $loanId   = $request->get('loan_id', '');
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->get('date_to',   now()->format('Y-m-d'));

        $loan         = null;
        $transactions = collect();
        $openingBalance = 0;
        $closingBalance = 0;

        $loans = Loan::with('client')
            ->whereIn('status', ['disbursed', 'active', 'partially_paid', 'completed'])
            ->orderBy('loan_number')
            ->get();

        if ($loanId) {
            $loan = Loan::with(['client', 'schedules', 'repayments.user'])->findOrFail($loanId);

            // Opening balance = outstanding just before dateFrom
            $paidBefore = (float) Repayment::where('loan_id', $loanId)
                ->where('payment_date', '<', $dateFrom)
                ->sum('amount');
            $openingBalance = max(0, (float)$loan->total_amount - $paidBefore);

            // Transactions in range
            $transactions = Repayment::with('user')
                ->where('loan_id', $loanId)
                ->whereBetween('payment_date', [$dateFrom, $dateTo])
                ->orderBy('payment_date')
                ->get();

            // Compute running balance
            $running = $openingBalance;
            $transactions = $transactions->map(function ($t) use (&$running) {
                $t->running_balance = max(0, $running - (float)$t->amount);
                $running = $t->running_balance;
                return $t;
            });
            $closingBalance = $running;
        }

        return view('reports.account-statement', compact(
            'loan', 'loans', 'transactions', 'openingBalance', 'closingBalance',
            'loanId', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Member Statement Report — all loans + transactions for a client.
     */
    public function memberStatement(Request $request)
    {
        $clientId = $request->get('client_id', '');
        $loanId   = $request->get('loan_id', '');
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->get('date_to',   now()->format('Y-m-d'));

        $clients = Client::orderBy('first_name')->get();
        $client  = $clientId ? Client::with(['loans.repayments.user', 'loans.schedules'])->find($clientId) : null;

        $loans        = collect();
        $repayments   = collect();
        $selectedLoan = null;

        if ($client) {
            $loanQuery = $client->loans()->with(['repayments', 'schedules'])
                ->whereIn('status', ['disbursed', 'active', 'partially_paid', 'completed']);
            $loans = $loanQuery->get();

            if ($loanId) {
                $selectedLoan = $loans->firstWhere('id', $loanId);
                $repayments = Repayment::with('user')
                    ->where('loan_id', $loanId)
                    ->whereBetween('payment_date', [$dateFrom, $dateTo])
                    ->orderBy('payment_date')
                    ->get();
            } else {
                $repayments = Repayment::with(['loan', 'user'])
                    ->whereIn('loan_id', $loans->pluck('id'))
                    ->whereBetween('payment_date', [$dateFrom, $dateTo])
                    ->orderBy('payment_date')
                    ->get();
            }
        }

        return view('reports.member-statement', compact(
            'clients', 'client', 'loans', 'selectedLoan', 'repayments',
            'clientId', 'loanId', 'dateFrom', 'dateTo'
        ));
    }

    // =========================================================================
    // CSV EXPORTS
    // =========================================================================

    public function exportDailyTransactions(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->format('Y-m-d'));
        $dateTo   = $request->get('date_to',   now()->format('Y-m-d'));

        $repayments = Repayment::with(['loan.client', 'user'])
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderBy('payment_date')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="daily_transactions_' . $dateFrom . '_' . $dateTo . '.csv"',
        ];

        $callback = function () use ($repayments) {
            $h = fopen('php://output', 'w');
            fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($h, ['Date','Receipt No','Client','Loan No','Amount','Method','Recorded By']);
            foreach ($repayments as $r) {
                fputcsv($h, [
                    optional($r->payment_date)->format('d/m/Y'),
                    $r->receipt_number,
                    optional(optional($r->loan)->client)->full_name ?? '—',
                    optional($r->loan)->loan_number,
                    $r->amount,
                    $r->payment_method,
                    optional($r->user)->name ?? '—',
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportLoanArrears(Request $request)
    {
        $asOfDate = $request->get('as_of_date', now()->format('Y-m-d'));

        $loans = Loan::with(['client', 'schedules'])
            ->whereIn('status', ['disbursed', 'active', 'partially_paid'])
            ->whereHas('schedules', fn($q) => $q->whereIn('status', ['pending','partial'])->where('due_date', '<', $asOfDate))
            ->get()->map(function ($loan) use ($asOfDate) {
                $overdue = $loan->schedules->filter(fn($s) => in_array($s->status, ['pending','partial']) && $s->due_date < $asOfDate);
                $loan->overdue_amount  = $overdue->sum(fn($s) => $s->total_amount - $s->paid_amount);
                $loan->oldest_due_date = $overdue->min('due_date');
                $loan->days_overdue    = $loan->oldest_due_date ? (int) now()->diffInDays(\Carbon\Carbon::parse($loan->oldest_due_date), false) * -1 : 0;
                return $loan;
            });

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="loan_arrears_' . $asOfDate . '.csv"',
        ];

        $callback = function () use ($loans) {
            $h = fopen('php://output', 'w');
            fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($h, ['Loan No','Client','Overdue Amount','Days Overdue','Oldest Due Date']);
            foreach ($loans as $l) {
                fputcsv($h, [
                    $l->loan_number,
                    optional($l->client)->full_name ?? optional($l->client)->first_name . ' ' . optional($l->client)->last_name,
                    $l->overdue_amount,
                    $l->days_overdue,
                    $l->oldest_due_date,
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportLoanOutstanding(Request $request)
    {
        $loans = Loan::with(['client', 'schedules'])
            ->whereIn('status', ['disbursed', 'active', 'partially_paid'])
            ->get()->map(function ($loan) {
                $principalRepaid = $loan->schedules->where('status','paid')->sum('principal_amount');
                $interestRepaid  = $loan->schedules->where('status','paid')->sum('interest_amount');
                $loan->outstanding_principal = max(0, (float)$loan->principal - $principalRepaid);
                $loan->outstanding_interest  = max(0, ((float)$loan->total_amount - (float)$loan->principal) - $interestRepaid);
                $loan->total_outstanding     = $loan->outstanding_principal + $loan->outstanding_interest;
                return $loan;
            });

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="loan_outstanding_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($loans) {
            $h = fopen('php://output', 'w');
            fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($h, ['Loan No','Client','Principal','Outstanding Principal','Outstanding Interest','Total Outstanding','Disbursed At','Maturity']);
            foreach ($loans as $l) {
                fputcsv($h, [
                    $l->loan_number,
                    optional($l->client)->full_name ?? optional($l->client)->first_name . ' ' . optional($l->client)->last_name,
                    $l->principal,
                    $l->outstanding_principal,
                    $l->outstanding_interest,
                    $l->total_outstanding,
                    optional($l->disbursed_at)?->format('d/m/Y'),
                    optional($l->first_payment_date)?->format('d/m/Y'),
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportLoanDisbursement(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->get('date_to',   now()->format('Y-m-d'));

        $loans = Loan::with(['client', 'product', 'user'])
            ->whereNotNull('disbursed_at')
            ->whereBetween('disbursed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->orderBy('disbursed_at')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="loan_disbursement_' . $dateFrom . '_' . $dateTo . '.csv"',
        ];

        $callback = function () use ($loans) {
            $h = fopen('php://output', 'w');
            fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($h, ['Loan No','Client','Product','Principal','Processing Fee','Disbursed At','Status','Disbursed By']);
            foreach ($loans as $l) {
                fputcsv($h, [
                    $l->loan_number,
                    optional($l->client)->full_name ?? optional($l->client)->first_name . ' ' . optional($l->client)->last_name,
                    optional($l->product)->name,
                    $l->principal,
                    $l->processing_fee,
                    optional($l->disbursed_at)?->format('d/m/Y'),
                    $l->status,
                    optional($l->user)->name ?? '—',
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }
}