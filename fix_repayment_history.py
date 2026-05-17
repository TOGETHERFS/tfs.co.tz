#!/usr/bin/env python3
path = '/var/www/phidlms/app/Http/Controllers/RepaymentController.php'
with open(path, 'r') as f:
    content = f.read()

# Find the closing brace of loanRepayments and insert history method before final }
old_end = """    public function loanRepayments(Loan $loan)
    {
        $loan->load(['client', 'product', 'schedules', 'repayments.user']);
        
        return view('repayments.loan', compact('loan'));
    }
}"""

new_end = """    public function loanRepayments(Loan $loan)
    {
        $loan->load(['client', 'product', 'schedules', 'repayments.user']);
        
        return view('repayments.loan', compact('loan'));
    }

    /**
     * Display repayment history grouped by daily, weekly, or monthly period.
     */
    public function history(Request $request)
    {
        $tenantId = session('tenant_id') ?? optional(auth()->user())->tenant_id;
        $period   = $request->get('period', 'daily');
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        if (!$dateFrom || !$dateTo) {
            switch ($period) {
                case 'weekly':
                    $dateFrom = now()->startOfMonth()->toDateString();
                    $dateTo   = now()->toDateString();
                    break;
                case 'monthly':
                    $dateFrom = now()->startOfYear()->toDateString();
                    $dateTo   = now()->toDateString();
                    break;
                default:
                    $dateFrom = now()->subDays(29)->toDateString();
                    $dateTo   = now()->toDateString();
            }
        }

        $query = Repayment::with(['loan.client', 'loan.product', 'user'])
            ->whereBetween('payment_date', [$dateFrom, $dateTo]);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $allRepayments = $query->orderBy('payment_date', 'asc')->get();

        switch ($period) {
            case 'weekly':
                $grouped = $allRepayments->groupBy(function ($r) {
                    return \\Carbon\\Carbon::parse($r->payment_date)->startOfWeek()->toDateString();
                });
                break;
            case 'monthly':
                $grouped = $allRepayments->groupBy(function ($r) {
                    return \\Carbon\\Carbon::parse($r->payment_date)->format('Y-m');
                });
                break;
            default:
                $grouped = $allRepayments->groupBy(function ($r) {
                    return \\Carbon\\Carbon::parse($r->payment_date)->toDateString();
                });
        }

        $summary = $grouped->map(function ($items) {
            return [
                'count' => $items->count(),
                'total' => $items->sum('amount'),
                'items' => $items,
            ];
        });

        $grandTotal = $allRepayments->sum('amount');
        $totalCount = $allRepayments->count();

        if ($request->get('export') === 'csv') {
            return $this->exportHistoryCsv($allRepayments, $period, $dateFrom, $dateTo);
        }

        return view('repayments.history', compact(
            'summary', 'period', 'dateFrom', 'dateTo', 'grandTotal', 'totalCount'
        ));
    }

    private function exportHistoryCsv($repayments, $period, $dateFrom, $dateTo)
    {
        $filename = 'repayment_history_' . $period . '_' . $dateFrom . '_to_' . $dateTo . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\\"{$filename}\\"",
        ];

        $callback = function () use ($repayments) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['Receipt No.', 'Date', 'Borrower', 'Loan No.', 'Amount (TZS)', 'Method', 'Recorded By']);
            foreach ($repayments as $r) {
                fputcsv($file, [
                    $r->receipt_number,
                    $r->payment_date,
                    optional($r->loan->client)->full_name ?? '-',
                    optional($r->loan)->loan_number ?? '-',
                    number_format($r->amount, 2),
                    ucfirst($r->payment_method ?? '-'),
                    optional($r->user)->name ?? '-',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}"""

# Try stripping trailing whitespace from lines to find match
import re
content_stripped = re.sub(r' +\n', '\n', content)
if old_end.strip() in content_stripped:
    content_stripped = content_stripped.replace(old_end.strip(), new_end)
    with open(path, 'w') as f:
        f.write(content_stripped)
    print("FIXED OK via stripped match")
elif old_end in content:
    content = content.replace(old_end, new_end)
    with open(path, 'w') as f:
        f.write(content)
    print("FIXED OK via direct match")
else:
    print("ERROR: pattern not found, showing end of file:")
    print(repr(content[-400:]))
