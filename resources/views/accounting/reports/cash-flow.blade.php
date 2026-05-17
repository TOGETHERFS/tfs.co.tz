@extends('layouts.app')
@section('title', 'Cash Flow Report')
@section('page-title', 'Cash Flow Report')

@section('content')
@php
    $periodLabel = ['daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly','yearly'=>'Yearly','custom'=>'Custom Range'][$period] ?? 'Monthly';
    $tenant = auth()->user()->tenant ?? null;
    $tenantName = optional($tenant)->company_name ?? optional($tenant)->name ?? 'MICROFINANCE';
@endphp

<style>
@media print {
    .no-print { display: none !important; }
    body { font-size: 12px; }
}
</style>

<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Cash Flow Report</h1>
            <p class="mt-1 text-sm text-gray-500">Cash In &amp; Cash Out statement with opening and closing balance</p>
        </div>
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print / Export
        </button>
    </div>

    {{-- Period Filter --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 no-print">
        <form method="GET" action="{{ route('accounting.reports.cash-flow') }}" id="cashFlowForm" class="space-y-4">

            {{-- Quick period buttons --}}
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Quick Period:</span>
                <div class="flex rounded-lg overflow-hidden border border-gray-300 divide-x divide-gray-300">
                    @foreach(['daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly','yearly'=>'Yearly'] as $val => $lbl)
                    <button type="button" onclick="setPeriod('{{ $val }}')"
                            class="period-btn px-4 py-2 text-sm font-medium transition {{ ($period === $val) ? 'bg-cyan-600 text-white' : 'bg-white text-gray-700 hover:bg-cyan-50' }}"
                            data-period="{{ $val }}">
                        {{ $lbl }}
                    </button>
                    @endforeach
                </div>
                <input type="hidden" name="period" id="periodInput" value="{{ $period }}">
            </div>

            {{-- Date Range --}}
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">From Date</label>
                    <input type="date" name="from_date" id="fromDate" value="{{ $fromDate }}"
                           class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-cyan-500 focus:border-cyan-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">To Date</label>
                    <input type="date" name="to_date" id="toDate" value="{{ $toDate }}"
                           class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-cyan-500 focus:border-cyan-500">
                </div>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-5 py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Generate Report
                </button>
            </div>
        </form>
    </div>

    <script>
    function setPeriod(val) {
        var today = new Date();
        var from, to;
        if (val === 'daily') {
            from = to = today.toISOString().slice(0,10);
        } else if (val === 'weekly') {
            var day = today.getDay();
            var mon = new Date(today); mon.setDate(today.getDate() - ((day+6)%7));
            var sun = new Date(mon); sun.setDate(mon.getDate() + 6);
            from = mon.toISOString().slice(0,10);
            to   = sun.toISOString().slice(0,10);
        } else if (val === 'monthly') {
            from = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0,10);
            to   = new Date(today.getFullYear(), today.getMonth()+1, 0).toISOString().slice(0,10);
        } else if (val === 'yearly') {
            from = today.getFullYear() + '-01-01';
            to   = today.getFullYear() + '-12-31';
        }
        document.getElementById('fromDate').value = from;
        document.getElementById('toDate').value   = to;
        document.getElementById('periodInput').value = val;
        // Highlight active button
        document.querySelectorAll('.period-btn').forEach(function(btn) {
            if (btn.dataset.period === val) {
                btn.classList.add('bg-cyan-600','text-white');
                btn.classList.remove('bg-white','text-gray-700');
            } else {
                btn.classList.remove('bg-cyan-600','text-white');
                btn.classList.add('bg-white','text-gray-700');
            }
        });
    }
    </script>

    {{-- Report Title Banner --}}
    <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white rounded-xl px-6 py-5 text-center print:rounded-none">
        <p class="text-base font-bold uppercase tracking-widest">{{ strtoupper($tenantName) }}</p>
        <p class="text-xl font-bold mt-1">{{ strtoupper($periodLabel) }} CASH FLOW REPORT</p>
        <p class="text-sm mt-1 opacity-75">
            @if($period === 'daily')
                {{ \Carbon\Carbon::parse($startDate)->format('l, d F Y') }}
            @else
                {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            @endif
        </p>
    </div>

    {{-- Excel-Style Cash Flow Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm border-collapse">

            {{-- Header --}}
            <thead>
                <tr class="bg-gray-800 text-white text-xs uppercase tracking-wider">
                    <th class="px-6 py-3 text-left font-bold border border-gray-700 w-1/2">DETAILS</th>
                    <th class="px-6 py-3 text-right font-bold border border-gray-700">AMOUNT IN (TZS)</th>
                    <th class="px-6 py-3 text-right font-bold border border-gray-700">AMOUNT OUT (TZS)</th>
                    <th class="px-6 py-3 text-right font-bold border border-gray-700">BALANCE (TZS)</th>
                </tr>
            </thead>

            <tbody>
                {{-- Opening Balance --}}
                <tr class="bg-blue-50 font-semibold border-b-2 border-blue-200">
                    <td class="px-6 py-3 text-gray-800 border border-gray-200">OPENING BALANCE</td>
                    <td class="px-6 py-3 text-right text-gray-400 border border-gray-200"></td>
                    <td class="px-6 py-3 text-right text-gray-400 border border-gray-200"></td>
                    <td class="px-6 py-3 text-right font-bold text-blue-800 border border-gray-200">
                        {{ number_format($report['opening_balance'], 0) }}
                    </td>
                </tr>

                {{-- ===== CASH IN SECTION ===== --}}
                <tr class="bg-green-700">
                    <td colspan="4" class="px-6 py-2 text-white font-bold text-xs uppercase tracking-wider">
                        &#9660; CASH IN
                    </td>
                </tr>

                @foreach($report['cash_in_rows'] as $row)
                <tr class="hover:bg-green-50 border-b border-gray-100">
                    <td class="px-6 py-3 text-gray-800 border border-gray-200">{{ strtoupper($row['label']) }}</td>
                    <td class="px-6 py-3 text-right font-semibold text-green-700 border border-gray-200">
                        {{ $row['amount'] > 0 ? number_format($row['amount'], 0) : '' }}
                    </td>
                    <td class="px-6 py-3 border border-gray-200"></td>
                    <td class="px-6 py-3 border border-gray-200"></td>
                </tr>
                @endforeach

                {{-- ===== CASH OUT SECTION ===== --}}
                <tr class="bg-red-700">
                    <td colspan="4" class="px-6 py-2 text-white font-bold text-xs uppercase tracking-wider">
                        &#9660; CASH OUT
                    </td>
                </tr>

                @foreach($report['cash_out_rows'] as $row)
                <tr class="hover:bg-red-50 border-b border-gray-100">
                    <td class="px-6 py-3 text-gray-800 border border-gray-200">{{ strtoupper($row['label']) }}</td>
                    <td class="px-6 py-3 border border-gray-200"></td>
                    <td class="px-6 py-3 text-right font-semibold text-red-700 border border-gray-200">
                        {{ $row['amount'] > 0 ? number_format($row['amount'], 0) : '' }}
                    </td>
                    <td class="px-6 py-3 border border-gray-200"></td>
                </tr>
                @endforeach

                {{-- Empty spacer rows like Excel --}}
                @for($i = 0; $i < 3; $i++)
                <tr class="border-b border-gray-100">
                    <td class="px-6 py-3 border border-gray-200">&nbsp;</td>
                    <td class="px-6 py-3 border border-gray-200"></td>
                    <td class="px-6 py-3 border border-gray-200"></td>
                    <td class="px-6 py-3 border border-gray-200"></td>
                </tr>
                @endfor

                {{-- ===== CLOSING BALANCE ROW ===== --}}
                <tr class="bg-gray-800 text-white font-bold text-sm border-t-4 border-gray-600">
                    <td class="px-6 py-4 border border-gray-600 uppercase tracking-wide">CLOSING BALANCE</td>
                    <td class="px-6 py-4 text-right border border-gray-600 text-green-300">
                        {{ number_format($report['total_cash_in'], 0) }}
                    </td>
                    <td class="px-6 py-4 text-right border border-gray-600 text-red-300">
                        {{ number_format($report['total_cash_out'], 0) }}
                    </td>
                    <td class="px-6 py-4 text-right border border-gray-600 text-xl
                        {{ $report['closing_balance'] >= 0 ? 'text-yellow-300' : 'text-red-400' }}">
                        {{ number_format($report['closing_balance'], 0) }}
                    </td>
                </tr>

            </tbody>
        </table>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 no-print">
        <div class="bg-green-50 border border-green-200 rounded-xl p-5 text-center">
            <p class="text-xs font-semibold text-green-600 uppercase tracking-wider mb-1">Total Cash In</p>
            <p class="text-2xl font-bold text-green-700">TZS {{ number_format($report['total_cash_in'], 0) }}</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-5 text-center">
            <p class="text-xs font-semibold text-red-600 uppercase tracking-wider mb-1">Total Cash Out</p>
            <p class="text-2xl font-bold text-red-700">TZS {{ number_format($report['total_cash_out'], 0) }}</p>
        </div>
        <div class="{{ $report['closing_balance'] >= 0 ? 'bg-blue-50 border-blue-200' : 'bg-orange-50 border-orange-200' }} border rounded-xl p-5 text-center">
            <p class="text-xs font-semibold {{ $report['closing_balance'] >= 0 ? 'text-blue-600' : 'text-orange-600' }} uppercase tracking-wider mb-1">Closing Balance</p>
            <p class="text-2xl font-bold {{ $report['closing_balance'] >= 0 ? 'text-blue-700' : 'text-orange-700' }}">TZS {{ number_format($report['closing_balance'], 0) }}</p>
        </div>
    </div>

</div>
@endsection
