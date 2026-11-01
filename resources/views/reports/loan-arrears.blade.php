@extends('layouts.app')
@section('title', 'Loan Arrears Report')
@push('styles')
<style>
@media print { .no-print { display:none !important; } .print-only { display:block !important; } body { background:white !important; } @page { margin:1cm; size:A4 landscape; } }
.print-only { display:none; }
input[type="date"] { background-color:#ffffff !important; color:#111827 !important; color-scheme:light; }
</style>
@endpush

@section('content')
<div class="space-y-6">

    <div class="print-only mb-4 text-center">
        <h1 class="text-2xl font-bold">{{ auth()->user()->tenant->name ?? 'Microfinance' }}</h1>
        <p class="text-lg font-semibold">Loan Arrears Report</p>
        <p class="text-sm text-gray-500">As of: {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }}</p>
        <p class="text-xs text-gray-400">Generated: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reports</a>
                <span class="text-gray-400">/</span>
                <span class="text-sm font-medium text-gray-900">Loan Arrears</span>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Loan Arrears Report</h1>
            <p class="mt-1 text-sm text-gray-500">Loans with overdue installments based on actual payment schedules</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <a href="{{ route('reports.loan-arrears.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 no-print">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                CSV
            </a>
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 no-print">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print
            </button>
        </div>
    </div>

    <form method="GET" class="bg-white rounded-lg border border-gray-200 p-5 no-print">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">As of Date</label>
                <input type="date" name="as_of_date" value="{{ $asOfDate }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Min Days Overdue</label>
                <select name="min_days" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    @foreach([1=>'1+ day',7=>'7+ days',30=>'30+ days',60=>'60+ days',90=>'90+ days'] as $val=>$lbl)
                    <option value="{{ $val }}" @selected($minDays==$val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Loan No / Client Name" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Apply Filters</button>
        </div>
    </form>

    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-lg border border-red-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Loans in Arrears</p>
            <p class="text-3xl font-bold text-red-600 mt-1">{{ number_format($totals['loan_count']) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-red-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Total Overdue Amount</p>
            <p class="text-3xl font-bold text-red-600 mt-1">TZS {{ number_format($totals['overdue_amount'], 2) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Loan No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Loan Officer</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Overdue Installments</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Oldest Due Date</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Days Overdue</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Overdue Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Outstanding Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($loans as $loan)
                    @php
                        $daysClass = $loan->days_overdue >= 90 ? 'bg-red-100 text-red-800' : ($loan->days_overdue >= 30 ? 'bg-orange-100 text-orange-800' : 'bg-yellow-100 text-yellow-800');
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">
                            <a href="{{ route('loans.show', $loan) }}" class="text-blue-600 hover:underline no-print">{{ $loan->loan_number }}</a>
                            <span class="print-only">{{ $loan->loan_number }}</span>
                        </td>
                        <td class="px-4 py-3">{{ optional($loan->client)->full_name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ optional($loan->user)->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">{{ $loan->overdue_count }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $loan->oldest_due_date ? \Carbon\Carbon::parse($loan->oldest_due_date)->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $daysClass }}">{{ $loan->days_overdue }}d</span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-red-600">{{ number_format($loan->overdue_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($loan->outstanding_balance, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No loans in arrears found.</td></tr>
                    @endforelse
                </tbody>
                @if($loans->count())
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-right text-gray-700">Total:</td>
                        <td class="px-4 py-3 text-right text-red-600">{{ number_format($totals['overdue_amount'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
