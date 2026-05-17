<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\FiscalYear;
use App\Services\Accounting\FinancialReportingService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class FinancialReportsController extends Controller
{
    protected FinancialReportingService $reportingService;

    public function __construct(FinancialReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    public function index()
    {
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        
        return view('accounting.reports.index', compact('fiscalYears'));
    }

    public function trialBalance(Request $request)
    {
        $asOfDate = $request->get('as_of_date', now()->format('Y-m-d'));
        
        $report = $this->reportingService->getTrialBalance($asOfDate);

        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('accounting.reports.trial-balance-pdf', compact('report'));
            return $pdf->download('trial-balance-' . $asOfDate . '.pdf');
        }

        if ($request->get('export') === 'excel') {
            return $this->exportTrialBalanceExcel($report);
        }

        return view('accounting.reports.trial-balance', compact('report', 'asOfDate'));
    }

    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->get('as_of_date', now()->format('Y-m-d'));
        
        $report = $this->reportingService->getBalanceSheet($asOfDate);

        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('accounting.reports.balance-sheet-pdf', compact('report'));
            return $pdf->download('balance-sheet-' . $asOfDate . '.pdf');
        }

        return view('accounting.reports.balance-sheet', compact('report', 'asOfDate'));
    }

    public function incomeStatement(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        
        $report = $this->reportingService->getIncomeStatement($startDate, $endDate);

        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('accounting.reports.income-statement-pdf', compact('report'));
            return $pdf->download('income-statement-' . $startDate . '-to-' . $endDate . '.pdf');
        }

        return view('accounting.reports.income-statement', compact('report', 'startDate', 'endDate'));
    }

    public function cashFlow(Request $request)
    {
        $period    = $request->get('period', 'monthly');
        $inputDate = $request->get('date', now()->format('Y-m-d'));

        // If user supplied explicit from_date/to_date use those directly
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $startDate = $request->get('from_date');
            $endDate   = $request->get('to_date');
            $period    = 'custom';
        } else {
            // Compute startDate / endDate based on selected period
            $date = \Carbon\Carbon::parse($inputDate);
            switch ($period) {
                case 'daily':
                    $startDate = $date->copy()->startOfDay()->format('Y-m-d');
                    $endDate   = $date->copy()->endOfDay()->format('Y-m-d');
                    break;
                case 'weekly':
                    $startDate = $date->copy()->startOfWeek()->format('Y-m-d');
                    $endDate   = $date->copy()->endOfWeek()->format('Y-m-d');
                    break;
                case 'yearly':
                    $startDate = $date->copy()->startOfYear()->format('Y-m-d');
                    $endDate   = $date->copy()->endOfYear()->format('Y-m-d');
                    break;
                default: // monthly
                    $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
                    $endDate   = $date->copy()->endOfMonth()->format('Y-m-d');
                    break;
            }
        }

        $tenantId = session('tenant_id') ?? (auth()->check() ? auth()->user()->tenant_id : null);

        // --- OPENING BALANCE ---
        // Sum opening_balance of cash/bank accounts + GL movements before start date
        $cashAccountIds = \DB::table('chart_of_accounts')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_cash_account', 1)
                  ->orWhere('is_bank_account', 1)
                  ->orWhere('account_code', 'like', '11%');
            })
            ->pluck('id');

        $openingBalance = (float) \DB::table('chart_of_accounts')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $cashAccountIds)
            ->sum('opening_balance');

        $priorGL = (float) \DB::table('general_ledger')
            ->where('tenant_id', $tenantId)
            ->whereIn('account_id', $cashAccountIds)
            ->where('transaction_date', '<', $startDate)
            ->selectRaw('COALESCE(SUM(debit_amount),0) - COALESCE(SUM(credit_amount),0) as bal')
            ->value('bal');

        $openingBalance += $priorGL;

        // --- CASH IN ---

        // 1. Loan Disbursements (principal given out) — cash OUT
        $loanDisbursements = (float) \DB::table('loans')
            ->where('tenant_id', $tenantId)
            ->whereBetween('disbursed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('disbursed_at')
            ->sum('principal');

        // 2. Repayments received (cash IN)
        $repayments = (float) \DB::table('repayments')
            ->join('loans', 'repayments.loan_id', '=', 'loans.id')
            ->where('loans.tenant_id', $tenantId)
            ->whereBetween('repayments.payment_date', [$startDate, $endDate])
            ->sum('repayments.amount');

        // 3. Penalty income — sourced from loan_schedules penalty_amount paid in period
        $penaltyIncome = (float) \DB::table('loan_schedules')
            ->join('loans', 'loan_schedules.loan_id', '=', 'loans.id')
            ->where('loans.tenant_id', $tenantId)
            ->where('loan_schedules.status', 'paid')
            ->whereBetween('loan_schedules.paid_date', [$startDate, $endDate])
            ->whereRaw('COALESCE(loan_schedules.penalty_amount, 0) > 0')
            ->sum('loan_schedules.penalty_amount');

        // 4. Fee income / management fees — sum processing_fee on disbursed loans
        $feeIncome = (float) \DB::table('loans')
            ->where('tenant_id', $tenantId)
            ->whereBetween('disbursed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('disbursed_at')
            ->sum('processing_fee');

        // 5. Capital injections — GL entries explicitly tagged
        $equityAccountIds = \DB::table('chart_of_accounts')
            ->where('tenant_id', $tenantId)
            ->where('account_type', 'equity')
            ->pluck('id');

        $capitalInjection = 0.0;
        if ($equityAccountIds->isNotEmpty()) {
            $capitalInjection = (float) \DB::table('general_ledger')
                ->where('tenant_id', $tenantId)
                ->whereIn('account_id', $equityAccountIds)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->where(function ($q) {
                    $q->where('description', 'like', '%capital injection%')
                      ->orWhere('description', 'like', '%director%')
                      ->orWhere('description', 'like', '%injection%');
                })
                ->sum('debit_amount');
        }

        // --- CASH OUT ---

        // 6. Expenses (approved/paid)
        $expenseRows = \DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->where('expenses.tenant_id', $tenantId)
            ->whereIn('expenses.status', ['approved', 'paid'])
            ->whereBetween('expenses.expense_date', [$startDate, $endDate])
            ->whereNull('expenses.deleted_at')
            ->select('expense_categories.name as category_name', 'expenses.amount')
            ->get();

        // --- BUILD REPORT ROWS ---
        $cashInRows  = [];
        $cashOutRows = [];

        if ($repayments > 0)
            $cashInRows[] = ['label' => 'Loan Repayments (Paid)', 'amount' => $repayments];
        if ($feeIncome > 0)
            $cashInRows[] = ['label' => 'Management Fees Collected', 'amount' => $feeIncome];
        if ($penaltyIncome > 0)
            $cashInRows[] = ['label' => 'Penalty / Late Fee Income', 'amount' => $penaltyIncome];
        if ($capitalInjection > 0)
            $cashInRows[] = ['label' => 'Capital Injection / Director Support', 'amount' => $capitalInjection];

        if ($loanDisbursements > 0)
            $cashOutRows[] = ['label' => 'Loan Disbursements (Principal)', 'amount' => $loanDisbursements];

        // Expenses grouped by category
        $expByCategory = $expenseRows->groupBy(fn($e) => $e->category_name ?? 'General Expenses');
        foreach ($expByCategory as $catName => $items) {
            $total = $items->sum('amount');
            if ($total > 0)
                $cashOutRows[] = ['label' => $catName, 'amount' => floatval($total)];
        }

        $totalCashIn    = collect($cashInRows)->sum('amount');
        $totalCashOut   = collect($cashOutRows)->sum('amount');
        $closingBalance = $openingBalance + $totalCashIn - $totalCashOut;

        $report = [
            'period'          => $period,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'opening_balance' => $openingBalance,
            'cash_in_rows'    => $cashInRows,
            'cash_out_rows'   => $cashOutRows,
            'total_cash_in'   => $totalCashIn,
            'total_cash_out'  => $totalCashOut,
            'closing_balance' => $closingBalance,
        ];

        $fromDate = $startDate;
        $toDate   = $endDate;

        return view('accounting.reports.cash-flow', compact('report', 'startDate', 'endDate', 'period', 'inputDate', 'fromDate', 'toDate'));
    }

    public function generalLedger(Request $request)
    {
        $accountId = $request->get('account_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $accounts = ChartOfAccount::active()->orderBy('account_code')->get();
        $report = null;

        if ($accountId) {
            $report = $this->reportingService->getGeneralLedger($accountId, $startDate, $endDate);

            if ($request->get('export') === 'pdf') {
                $pdf = Pdf::loadView('accounting.reports.general-ledger-pdf', compact('report'));
                return $pdf->download('general-ledger-' . $report['account']['code'] . '.pdf');
            }
        }

        return view('accounting.reports.general-ledger', compact('accounts', 'report', 'accountId', 'startDate', 'endDate'));
    }

    public function accountStatement(Request $request, ChartOfAccount $account)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', now()->format('Y-m-d'));

        $report = $this->reportingService->getGeneralLedger($account->id, $startDate, $endDate);

        return view('accounting.reports.account-statement', compact('report', 'account', 'startDate', 'endDate'));
    }

    public function booksOfAccounts(Request $request)
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;

        $allAccounts = ChartOfAccount::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $grouped = [
            'asset'     => ['label' => 'Assets',          'color' => 'blue',   'accounts' => $allAccounts->where('account_type', 'asset')],
            'liability' => ['label' => 'Liabilities',      'color' => 'red',    'accounts' => $allAccounts->where('account_type', 'liability')],
            'equity'    => ['label' => 'Equity / Capital', 'color' => 'green',  'accounts' => $allAccounts->where('account_type', 'equity')],
            'income'    => ['label' => 'Revenue / Income', 'color' => 'purple', 'accounts' => $allAccounts->where('account_type', 'income')],
            'expense'   => ['label' => 'Expenses',         'color' => 'orange', 'accounts' => $allAccounts->where('account_type', 'expense')],
        ];

        return view('accounting.reports.books-of-accounts', compact('grouped'));
    }

    protected function exportTrialBalanceExcel(array $report)
    {
        $data = [];
        $data[] = ['Account Code', 'Account Name', 'Debit', 'Credit'];
        
        foreach ($report['accounts'] as $account) {
            $data[] = [
                $account['account_code'],
                $account['account_name'],
                $account['debit_balance'] > 0 ? number_format($account['debit_balance'], 2) : '',
                $account['credit_balance'] > 0 ? number_format($account['credit_balance'], 2) : '',
            ];
        }

        $data[] = ['', 'TOTALS', number_format($report['total_debits'], 2), number_format($report['total_credits'], 2)];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'trial-balance-' . $report['as_of_date'] . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
