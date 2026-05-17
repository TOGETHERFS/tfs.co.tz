<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
     * Display the admin reports dashboard.
     */
    public function index()
    {
        return view('admin.reports.index');
    }

    /**
     * Generate loan portfolio report.
     */
    public function loanPortfolio(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        // Calculate portfolio value including interest (total_amount)
        $portfolioValue = Loan::whereIn('status', ['disbursed', 'active'])
            ->sum(DB::raw('total_amount - COALESCE((SELECT SUM(amount) FROM repayments r WHERE r.loan_id = loans.id), 0)'));
        
        // Fallback to outstanding_balance if above calculation fails
        if ($portfolioValue <= 0) {
            $portfolioValue = Loan::whereIn('status', ['disbursed', 'active'])->sum('outstanding_balance');
        }

        $data = [
            'total_loans' => Loan::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'total_disbursed' => Loan::whereBetween('disbursed_at', [$dateFrom, $dateTo])->sum('principal'),
            'active_loans' => Loan::whereIn('status', ['disbursed', 'active'])->count(),
            'completed_loans' => Loan::where('status', 'completed')->count(),
            'overdue_loans' => Loan::overdue()->count(),
            'total_outstanding' => $portfolioValue, // Use portfolio value including interest
            'portfolio_at_risk' => $this->calculatePortfolioAtRisk(),
            'loans_by_product' => $this->getLoansByProduct($dateFrom, $dateTo),
            'loans_by_status' => $this->getLoansByStatus($dateFrom, $dateTo),
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

        return view('admin.reports.loan-portfolio', compact('data', 'dateFrom', 'dateTo'));
    }

    /**
     * Generate profit and loss report.
     */
    public function profitLoss(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $data = [
            'income' => $this->calculateIncome($dateFrom, $dateTo),
            'expenses' => $this->calculateExpenses($dateFrom, $dateTo),
            'net_profit' => 0, // Will be calculated
        ];

        $data['net_profit'] = $data['income']['total'] - $data['expenses']['total'];

        return view('admin.reports.profit-loss', compact('data', 'dateFrom', 'dateTo'));
    }

    /**
     * Generate arrears aging report.
     */
    public function arrearsAging(Request $request)
    {
        $now = now();
        
        // Current (not overdue) - loans with no overdue schedules
        $current = Loan::whereDoesntHave('schedules', function ($query) use ($now) {
            $query->where('status', 'pending')
                  ->where('due_date', '<', $now);
        })->sum('outstanding_balance');

        // 1-30 days overdue
        $days1_30 = Loan::whereHas('schedules', function ($query) use ($now) {
            $query->where('status', 'pending')
                  ->where('due_date', '>=', $now->copy()->subDays(30))
                  ->where('due_date', '<', $now);
        })->sum('outstanding_balance');

        // 31-60 days overdue
        $days31_60 = Loan::whereHas('schedules', function ($query) use ($now) {
            $query->where('status', 'pending')
                  ->where('due_date', '>=', $now->copy()->subDays(60))
                  ->where('due_date', '<', $now->copy()->subDays(30));
        })->sum('outstanding_balance');

        // 61-90 days overdue
        $days61_90 = Loan::whereHas('schedules', function ($query) use ($now) {
            $query->where('status', 'pending')
                  ->where('due_date', '>=', $now->copy()->subDays(90))
                  ->where('due_date', '<', $now->copy()->subDays(60));
        })->sum('outstanding_balance');

        // Over 90 days overdue
        $over90 = Loan::whereHas('schedules', function ($query) use ($now) {
            $query->where('status', 'pending')
                  ->where('due_date', '<', $now->copy()->subDays(90));
        })->sum('outstanding_balance');

        $data = [
            'current' => $current,
            '1_30_days' => $days1_30,
            '31_60_days' => $days31_60,
            '61_90_days' => $days61_90,
            'over_90_days' => $over90,
        ];

        return view('admin.reports.arrears-aging', compact('data'));
    }

    /**
     * Generate collections report.
     */
    public function collections(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $data = [
            'total_collected' => Repayment::whereBetween('payment_date', [$dateFrom, $dateTo])->sum('amount'),
            'collections_by_method' => $this->getCollectionsByMethod($dateFrom, $dateTo),
            'daily_collections' => $this->getDailyCollections($dateFrom, $dateTo),
        ];

        return view('admin.reports.collections', compact('data', 'dateFrom', 'dateTo'));
    }

    /**
     * Generate balance sheet report.
     */
    public function balanceSheet(Request $request)
    {
        $data = [
            'assets' => $this->calculateAssets(),
            'liabilities' => $this->calculateLiabilities(),
            'equity' => $this->calculateEquity(),
        ];

        return view('admin.reports.balance-sheet', compact('data'));
    }

    /**
     * Generate client analysis report.
     */
    public function clientAnalysis(Request $request)
    {
        $data = [
            'total_clients' => Client::count(),
            'active_clients' => Client::where('status', 'active')->count(),
            'clients_with_loans' => Client::whereHas('loans')->count(),
            'client_demographics' => $this->getClientDemographics(),
        ];

        return view('admin.reports.client-analysis', compact('data'));
    }

    /**
     * Accounts index page.
     */
    public function accountsIndex()
    {
        return view('admin.reports.accounts.index');
    }

    /**
     * General ledger report.
     */
    public function generalLedger(Request $request)
    {
        return view('admin.reports.accounts.general-ledger');
    }

    /**
     * Trial balance report.
     */
    public function trialBalance(Request $request)
    {
        return view('admin.reports.accounts.trial-balance');
    }

    /**
     * Cash book report.
     */
    public function cashBook(Request $request)
    {
        return view('admin.reports.accounts.cashbook');
    }

    /**
     * Bank book report.
     */
    public function bankBook(Request $request)
    {
        return view('admin.reports.accounts.bankbook');
    }

    /**
     * Client ledger report.
     */
    public function clientLedger(Request $request)
    {
        return view('admin.reports.accounts.client-ledger');
    }

    /**
     * Income categories report.
     */
    public function incomeCategories(Request $request)
    {
        return view('admin.reports.accounts.income-categories');
    }

    /**
     * Expenditure categories report.
     */
    public function expenditureCategories(Request $request)
    {
        return view('admin.reports.accounts.expenditure-categories');
    }

    /**
     * Assets index.
     */
    public function assetsIndex(Request $request)
    {
        return view('admin.reports.accounts.assets');
    }

    /**
     * Store asset.
     */
    public function storeAsset(Request $request)
    {
        // Implementation for storing assets
        return redirect()->back()->with('success', 'Asset created successfully');
    }

    /**
     * Chart of accounts.
     */
    public function chartOfAccounts(Request $request)
    {
        return view('admin.reports.accounts.chart-of-accounts');
    }

    /**
     * Journal entries.
     */
    public function journalEntries(Request $request)
    {
        return view('admin.reports.accounts.journal-entries');
    }

    /**
     * Export report as PDF.
     */
    public function exportPdf($type)
    {
        // Implementation for PDF export
        return response()->json(['message' => 'Export functionality coming soon']);
    }

    // Helper methods
    private function calculatePortfolioAtRisk()
    {
        $totalOutstanding = Loan::whereIn('status', ['disbursed', 'active'])->sum('outstanding_balance');
        $overdueOutstanding = Loan::whereHas('schedules', function ($query) {
            $query->where('status', 'pending')
                  ->where('due_date', '<', now());
        })->sum('outstanding_balance');
        
        return $totalOutstanding > 0 ? ($overdueOutstanding / $totalOutstanding) * 100 : 0;
    }

    private function getLoansByProduct($dateFrom, $dateTo)
    {
        return Loan::whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('product_id', DB::raw('count(*) as count'))
            ->groupBy('product_id')
            ->get();
    }

    private function getLoansByStatus($dateFrom, $dateTo)
    {
        return Loan::whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();
    }

    private function calculateIncome($dateFrom, $dateTo)
    {
        $totalAmount = Repayment::whereBetween('payment_date', [$dateFrom, $dateTo])->sum('amount');
        
        return [
            'interest' => 0,
            'fees' => 0,
            'total' => $totalAmount,
        ];
    }

    private function calculateExpenses($dateFrom, $dateTo)
    {
        return [
            'operational' => 0, // Implement based on your expense tracking
            'total' => 0,
        ];
    }

    private function getCollectionsByMethod($dateFrom, $dateTo)
    {
        return Repayment::whereBetween('payment_date', [$dateFrom, $dateTo])
            ->select('payment_method', DB::raw('sum(amount) as total'))
            ->groupBy('payment_method')
            ->get();
    }

    private function getDailyCollections($dateFrom, $dateTo)
    {
        return Repayment::whereBetween('payment_date', [$dateFrom, $dateTo])
            ->select(DB::raw('DATE(payment_date) as date'), DB::raw('sum(amount) as total'))
            ->groupBy(DB::raw('DATE(payment_date)'))
            ->orderBy('date')
            ->get();
    }

    private function calculateAssets()
    {
        return [
            'cash' => 0, // Implement based on your accounting system
            'loans_receivable' => Loan::whereIn('status', ['disbursed', 'active'])->sum('outstanding_balance'),
            'total' => 0,
        ];
    }

    private function calculateLiabilities()
    {
        return [
            'deposits' => 0, // Implement based on your system
            'total' => 0,
        ];
    }

    private function calculateEquity()
    {
        return [
            'retained_earnings' => 0, // Implement based on your system
            'total' => 0,
        ];
    }

    private function getClientDemographics()
    {
        return [
            'by_gender' => Client::select('gender', DB::raw('count(*) as count'))->groupBy('gender')->get(),
            'by_age_group' => [], // Implement age grouping logic
        ];
    }
}