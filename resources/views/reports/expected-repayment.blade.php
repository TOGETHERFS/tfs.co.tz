@extends('layouts.app')
@section('title', 'Expected Loan Repayment Report')
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
        <p class="text-lg font-semibold">Expected Loan Repayment Report</p>
        <p class="text-xs text-gray-400">Generated: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reports</a>
                <span class="text-gray-400">/</span>
                <span class="text-sm font-medium text-gray-900">Expected Repayments</span>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Expected Loan Repayment Report</h1>
            <p class="mt-1 text-sm text-gray-500">Installment schedules with actual payment status</p>
        </div>
        <button onclick="window.print()" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 no-print">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
    </div>

    <form method="GET" class="bg-white rounded-lg border border-gray-200 p-5 no-print">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Status Filter</label>
                <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All (by date range)</option>
                    <option value="overdue" @selected($status==='overdue')>Overdue</option>
                    <option value="due_today" @selected($status==='due_today')>Due Today</option>
                    <option value="upcoming" @selected($status==='upcoming')>Upcoming</option>
                    <option value="partially_paid" @selected($status==='partially_paid')>Partially Paid</option>
                    <option value="paid" @selected($status==='paid')>Paid (by date range)</option>
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

    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Total Expected</p>
            <p class="text-xl font-bold text-gray-700 mt-1">{{ number_format($totals['total_expected'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-green-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Total Paid</p>
            <p class="text-xl font-bold text-green-600 mt-1">{{ number_format($totals['total_paid'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-red-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Remaining</p>
            <p class="text-xl font-bold text-red-600 mt-1">{{ number_format($totals['total_remaining'], 2) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Due Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Loan No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Installment #</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Expected</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Paid</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Remaining</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($schedules as $s)
                    @php
                        $dueDate   = \Carbon\Carbon::parse($s->due_date);
                        $remaining = max(0, $s->total_amount - $s->paid_amount);
                        if ($s->status === 'paid') {
                            $badge = 'bg-green-100 text-green-700';
                            $label = 'Paid';
                        } elseif ($s->status === 'partial') {
                            $badge = 'bg-yellow-100 text-yellow-700';
                            $label = 'Partially Paid';
                        } elseif ($dueDate->toDateString() === $today) {
                            $badge = 'bg-orange-100 text-orange-700';
                            $label = 'Due Today';
                        } elseif ($dueDate->toDateString() < $today) {
                            $badge = 'bg-red-100 text-red-700';
                            $label = 'Overdue';
                        } else {
                            $badge = 'bg-blue-100 text-blue-700';
                            $label = 'Upcoming';
                        }
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $dueDate->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">{{ optional($s->loan)->loan_number }}</td>
                        <td class="px-4 py-3">{{ optional(optional($s->loan)->client)->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">{{ $s->installment_number }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($s->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right text-green-600">{{ number_format($s->paid_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right {{ $remaining > 0 ? 'text-red-600 font-semibold' : 'text-gray-500' }}">{{ number_format($remaining, 2) }}</td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $badge }}">{{ $label }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No installments found for the selected criteria.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($schedules->hasPages())
        <div class="px-5 py-4 border-t border-gray-200 no-print">{{ $schedules->links() }}</div>
        @endif
    </div>

</div>
@endsection
