<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\FiscalYear;
use App\Models\Accounting\AccountingPeriod;
use App\Models\Accounting\AccountingAuditTrail;
use Illuminate\Http\Request;

class FiscalYearController extends Controller
{
    public function index()
    {
        $fiscalYears = FiscalYear::with('periods')
            ->orderBy('start_date', 'desc')
            ->get();

        return view('accounting.fiscal-years.index', compact('fiscalYears'));
    }

    public function create()
    {
        return view('accounting.fiscal-years.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:fiscal_years,name,NULL,id,tenant_id,' . session('tenant_id'),
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $fiscalYear = FiscalYear::create($validated);
        $fiscalYear->generatePeriods();

        AccountingAuditTrail::log('create', FiscalYear::class, $fiscalYear->id, null, $validated);

        return redirect()->route('accounting.fiscal-years.show', $fiscalYear)
            ->with('success', 'Fiscal year created with ' . $fiscalYear->periods()->count() . ' periods.');
    }

    public function show(FiscalYear $fiscalYear)
    {
        $fiscalYear->load('periods');

        return view('accounting.fiscal-years.show', compact('fiscalYear'));
    }

    public function closePeriod(AccountingPeriod $period)
    {
        if ($period->is_closed) {
            return back()->with('error', 'This period is already closed.');
        }

        $period->close(auth()->id());

        AccountingAuditTrail::log('close_period', AccountingPeriod::class, $period->id, ['is_closed' => false], ['is_closed' => true]);

        return back()->with('success', 'Accounting period closed successfully.');
    }

    public function reopenPeriod(AccountingPeriod $period)
    {
        if (!$period->is_closed) {
            return back()->with('error', 'This period is not closed.');
        }

        if ($period->fiscalYear->is_closed) {
            return back()->with('error', 'Cannot reopen period in a closed fiscal year.');
        }

        $period->update([
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
        ]);

        AccountingAuditTrail::log('reopen_period', AccountingPeriod::class, $period->id, ['is_closed' => true], ['is_closed' => false]);

        return back()->with('success', 'Accounting period reopened.');
    }

    public function closeFiscalYear(FiscalYear $fiscalYear)
    {
        if ($fiscalYear->is_closed) {
            return back()->with('error', 'This fiscal year is already closed.');
        }

        $fiscalYear->close(auth()->id());

        AccountingAuditTrail::log('close_fiscal_year', FiscalYear::class, $fiscalYear->id, ['is_closed' => false], ['is_closed' => true]);

        return back()->with('success', 'Fiscal year closed successfully. All periods have been locked.');
    }

    public function reopenFiscalYear(FiscalYear $fiscalYear)
    {
        if (!$fiscalYear->is_closed) {
            return back()->with('error', 'This fiscal year is not closed.');
        }

        $fiscalYear->update([
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
        ]);

        AccountingAuditTrail::log('reopen_fiscal_year', FiscalYear::class, $fiscalYear->id, ['is_closed' => true], ['is_closed' => false]);

        return back()->with('success', 'Fiscal year reopened.');
    }
}
