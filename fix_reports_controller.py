#!/usr/bin/env python3
path = '/var/www/phidlms/app/Http/Controllers/ReportsController.php'

new_methods = r"""
    // =========================================================
    // NEW MICROFINANCE REPORT METHODS
    // =========================================================

    /**
     * Daily Portfolio Report.
     */
    public function dailyPortfolio(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $activeLoans        = Loan::whereIn('status', ['disbursed', 'active']);
        $totalActive        = (clone $activeLoans)->count();
        $totalOutstanding   = (clone $activeLoans)->sum('outstanding_balance');

        $disbursedToday     = Loan::whereDate('disbursed_at', $date);
        $disbursementCount  = (clone $disbursedToday)->count();
        $disbursementAmount = (clone $disbursedToday)->sum('principal');

        $collectionToday    = Repayment::whereDate('payment_date', $date)->sum('amount');
        $collectionCount    = Repayment::whereDate('payment_date', $date)->count();

        $overdueLoans = Loan::whereIn('status', ['disbursed', 'active'])
            ->whereHas('schedules', fn($q) => $q->where('status', 'pending')->where('due_date', '<', now()));
        $overdueCount  = (clone $overdueLoans)->count();
        $overdueAmount = (clone $overdueLoans)->sum('outstanding_balance');

        $parPercent = $totalOutstanding > 0
            ? round(($overdueAmount / $totalOutstanding) * 100, 2)
            : 0;

        $trend = collect();
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $trend->push([
                'date'        => $d,
                'disbursed'   => Loan::whereDate('disbursed_at', $d)->sum('principal'),
                'collections' => Repayment::whereDate('payment_date', $d)->sum('amount'),
            ]);
        }

        return view('reports.daily-portfolio', compact(
            'date', 'totalActive', 'totalOutstanding',
            'disbursementCount', 'disbursementAmount',
            'collectionToday', 'collectionCount',
            'overdueCount', 'overdueAmount', 'parPercent', 'trend'
        ));
    }

    /**
     * Portfolio at Risk (PAR) Report.
     */
    public function parReport(Request $request)
    {
        $asOf = $request->get('as_of_date', now()->toDateString());

        $totalPortfolio = Loan::whereIn('status', ['disbursed', 'active'])->sum('outstanding_balance');

        $par = [];
        foreach ([1, 7, 30, 90] as $days) {
            $cutoff = Carbon::parse($asOf)->subDays($days);
            $loans  = Loan::whereIn('status', ['disbursed', 'active'])
                ->whereHas('schedules', fn($q) => $q->where('status', 'pending')->where('due_date', '<', $cutoff));
            $count  = (clone $loans)->count();
            $amount = (clone $loans)->sum('outstanding_balance');
            $par["par_{$days}"] = [
                'days'    => $days,
                'count'   => $count,
                'amount'  => $amount,
                'percent' => $totalPortfolio > 0 ? round(($amount / $totalPortfolio) * 100, 2) : 0,
            ];
        }

        $parByProduct = Loan::whereIn('status', ['disbursed', 'active'])
            ->whereHas('schedules', fn($q) => $q->where('status', 'pending')->where('due_date', '<', now()))
            ->with('product')
            ->select('product_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(outstanding_balance) as amount'))
            ->groupBy('product_id')
            ->get();

        return view('reports.par-report', compact('par', 'totalPortfolio', 'asOf', 'parByProduct'));
    }

    /**
     * Branch Performance Summary.
     */
    public function branchPerformance(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->get('date_to', now()->toDateString());

        $branches = \App\Models\Branch::where('is_active', true)->get();

        $branchData = $branches->map(function ($branch) use ($dateFrom, $dateTo) {
            $officerIds = \App\Models\User::where('branch_id', $branch->id)->pluck('id');

            $activeLoans     = Loan::whereIn('user_id', $officerIds)->whereIn('status', ['disbursed', 'active']);
            $disbursedPeriod = Loan::whereIn('user_id', $officerIds)
                ->whereDate('disbursed_at', '>=', $dateFrom)
                ->whereDate('disbursed_at', '<=', $dateTo);
            $repaymentsPeriod = Repayment::whereHas('loan', fn($q) => $q->whereIn('user_id', $officerIds))
                ->whereBetween('payment_date', [$dateFrom, $dateTo]);
            $activeBorrowers = Client::whereHas('loans', fn($q) =>
                $q->whereIn('user_id', $officerIds)->whereIn('status', ['disbursed', 'active'])
            )->count();

            return [
                'branch'           => $branch,
                'active_borrowers' => $activeBorrowers,
                'active_loans'     => (clone $activeLoans)->count(),
                'outstanding'      => (clone $activeLoans)->sum('outstanding_balance'),
                'disbursed_count'  => (clone $disbursedPeriod)->count(),
                'disbursed_amount' => (clone $disbursedPeriod)->sum('principal'),
                'collection_total' => (clone $repaymentsPeriod)->sum('amount'),
                'collection_count' => (clone $repaymentsPeriod)->count(),
                'officer_count'    => $officerIds->count(),
            ];
        });

        $totals = [
            'active_borrowers' => $branchData->sum('active_borrowers'),
            'active_loans'     => $branchData->sum('active_loans'),
            'outstanding'      => $branchData->sum('outstanding'),
            'disbursed_amount' => $branchData->sum('disbursed_amount'),
            'collection_total' => $branchData->sum('collection_total'),
        ];

        return view('reports.branch-performance', compact('branchData', 'totals', 'dateFrom', 'dateTo'));
    }

    /**
     * Cash Position Report.
     */
    public function cashPosition(Request $request)
    {
        $asOf = $request->get('as_of_date', now()->toDateString());

        $bankAccounts   = \App\Models\Accounting\BankAccount::all();
        $cashInBank     = $bankAccounts->sum('balance');
        $cashInOffice   = Repayment::whereDate('payment_date', '<=', $asOf)
            ->where('payment_method', 'cash')->sum('amount');
        $totalLiquidity = $cashInBank + $cashInOffice;

        $collectionsToday   = Repayment::whereDate('payment_date', $asOf)->sum('amount');
        $disbursementsToday = Loan::whereDate('disbursed_at', $asOf)->sum('principal');

        $cashFlow = collect();
        for ($i = 13; $i >= 0; $i--) {
            $d = Carbon::parse($asOf)->subDays($i)->toDateString();
            $cashFlow->push([
                'date'    => $d,
                'inflow'  => Repayment::whereDate('payment_date', $d)->sum('amount'),
                'outflow' => Loan::whereDate('disbursed_at', $d)->sum('principal'),
            ]);
        }

        return view('reports.cash-position', compact(
            'asOf', 'cashInOffice', 'cashInBank', 'totalLiquidity',
            'collectionsToday', 'disbursementsToday', 'cashFlow', 'bankAccounts'
        ));
    }

    /**
     * Staff Activity Report.
     */
    public function staffActivity(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->get('date_to', now()->toDateString());
        $tenantId = session('tenant_id') ?? optional(auth()->user())->tenant_id;

        $staffQuery = \App\Models\User::query();
        if ($tenantId) {
            $staffQuery->where('tenant_id', $tenantId);
        }
        $staff = $staffQuery->with('branch')->get();

        $staffData = $staff->map(function ($user) use ($dateFrom, $dateTo) {
            $loansCreated   = Loan::where('user_id', $user->id)->whereBetween('created_at', [$dateFrom, $dateTo]);
            $loansDisbursed = Loan::where('user_id', $user->id)
                ->whereDate('disbursed_at', '>=', $dateFrom)->whereDate('disbursed_at', '<=', $dateTo);
            $collections    = Repayment::where('user_id', $user->id)->whereBetween('payment_date', [$dateFrom, $dateTo]);
            $activeLoans    = Loan::where('user_id', $user->id)->whereIn('status', ['disbursed', 'active']);
            $overdueLoans   = Loan::where('user_id', $user->id)->whereIn('status', ['disbursed', 'active'])
                ->whereHas('schedules', fn($q) => $q->where('status', 'pending')->where('due_date', '<', now()));

            return [
                'user'               => $user,
                'loans_created'      => (clone $loansCreated)->count(),
                'loans_disbursed'    => (clone $loansDisbursed)->count(),
                'disbursed_amount'   => (clone $loansDisbursed)->sum('principal'),
                'collections_count'  => (clone $collections)->count(),
                'collections_amount' => (clone $collections)->sum('amount'),
                'active_portfolio'   => (clone $activeLoans)->sum('outstanding_balance'),
                'overdue_loans'      => (clone $overdueLoans)->count(),
            ];
        });

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
"""

with open(path, 'r') as f:
    content = f.read()

# Insert before the final closing brace of the class
marker = "\n}"
last_idx = content.rfind(marker)
if last_idx == -1:
    print("ERROR: could not find closing brace")
else:
    content = content[:last_idx] + new_methods + content[last_idx:]
    with open(path, 'w') as f:
        f.write(content)
    print("DONE - methods appended")
