@extends('layouts.app')
@section('title', 'Loan Transaction Report')
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
        <p class="text-lg font-semibold">Loan Transaction Report</p>
        <p class="text-sm text-gray-500">Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
        <p class="text-xs text-gray-400">Generated: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reports</a>
                <span class="text-gray-400">/</span>
                <span class="text-sm font-medium text-gray-900">Loan Transactions</span>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Loan Transaction Report</h1>
            <p class="mt-1 text-sm text-gray-500">Detailed repayment transactions by date range and payment method</p>
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
                <label class="block text-xs font-medium text-gray-700 mb-1">Payment Method</label>
                <select name="payment_method" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Methods</option>
                    @foreach(['cash'=>'Cash','bank_transfer'=>'Bank Transfer','mobile_money'=>'Mobile Money','cheque'=>'Cheque'] as $val=>$lbl)
                    <option value="{{ $val }}" @selected($method===$val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Receipt / Loan / Name" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Apply Filters</button>
        </div>
    </form>

    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-lg border border-green-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Total Collected</p>
            <p class="text-3xl font-bold text-green-600 mt-1">TZS {{ number_format($totals['total_amount'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Total Transactions</p>
            <p class="text-3xl font-bold text-gray-700 mt-1">{{ number_format($totals['count']) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Receipt No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Loan No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Method</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Reference</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $t)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ optional($t->payment_date)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">{{ $t->receipt_number }}</td>
                        <td class="px-4 py-3">{{ optional(optional($t->loan)->client)->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">{{ optional($t->loan)->loan_number }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">{{ ucfirst(str_replace('_',' ',$t->payment_method)) }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs">{{ $t->reference ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-green-600">{{ number_format($t->amount, 2) }}</td>
                        <td class="px-4 py-3">{{ optional($t->user)->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No transactions found for this period.</td></tr>
                    @endforelse
                </tbody>
                @if($transactions->count())
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-right text-gray-700">Page Total:</td>
                        <td class="px-4 py-3 text-right text-green-600">{{ number_format($transactions->getCollection()->sum('amount'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if($transactions->hasPages())
        <div class="px-5 py-4 border-t border-gray-200 no-print">{{ $transactions->links() }}</div>
        @endif
    </div>

</div>
@endsection
