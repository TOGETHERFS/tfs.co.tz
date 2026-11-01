@extends('layouts.app')
@section('title', 'Daily Transactions Report')
@push('styles')
<style>
@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    body { background: white !important; }
    @page { margin: 1cm; size: A4 landscape; }
}
.print-only { display: none; }
input[type="date"] { background-color: #ffffff !important; color: #111827 !important; color-scheme: light; }
</style>
@endpush

@section('content')
<div class="space-y-6">

    <!-- Print Header -->
    <div class="print-only mb-4 text-center">
        <h1 class="text-2xl font-bold">{{ auth()->user()->tenant->name ?? 'Microfinance' }}</h1>
        <p class="text-lg font-semibold">Daily Transactions Report</p>
        <p class="text-sm text-gray-500">Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
        <p class="text-xs text-gray-400">Generated: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reports</a>
                <span class="text-gray-400">/</span>
                <span class="text-sm font-medium text-gray-900">Daily Transactions</span>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Daily Transactions Report</h1>
            <p class="mt-1 text-sm text-gray-500">All repayments and disbursements in the selected period</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <a href="{{ route('reports.daily-transactions.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                CSV
            </a>
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print
            </button>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white rounded-lg border border-gray-200 p-5 no-print">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                <select name="type" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Types</option>
                    <option value="repayment" @selected($type==='repayment')>Repayments</option>
                    <option value="disbursement" @selected($type==='disbursement')>Disbursements</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Payment Method</label>
                <select name="payment_method" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Methods</option>
                    @foreach(['cash'=>'Cash','bank_transfer'=>'Bank Transfer','mobile_money'=>'Mobile Money','cheque'=>'Cheque'] as $val=>$label)
                    <option value="{{ $val }}" @selected($method===$val)>{{ $label }}</option>
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

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Total Repayments</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($totals['total_repayments'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ number_format($totals['repayment_count']) }} transactions</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Total Disbursed</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($totals['total_disbursed'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ number_format($totals['disbursement_count']) }} loans</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Net Cash Flow</p>
            @php $net = $totals['total_repayments'] - $totals['total_disbursed']; @endphp
            <p class="text-2xl font-bold {{ $net >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">{{ number_format($net, 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Inflow minus outflow</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Total Transactions</p>
            <p class="text-2xl font-bold text-gray-700 mt-1">{{ number_format($totals['repayment_count'] + $totals['disbursement_count']) }}</p>
            <p class="text-xs text-gray-400 mt-1">All types combined</p>
        </div>
    </div>

    <!-- Repayments Table -->
    @if(!$type || $type === 'repayment')
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Repayments ({{ $repayments->count() }})</h3>
            <span class="text-sm font-bold text-green-600">TZS {{ number_format($totals['total_repayments'], 2) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Receipt No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Loan No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Method</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($repayments as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ optional($r->payment_date)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">{{ $r->receipt_number }}</td>
                        <td class="px-4 py-3">{{ optional(optional($r->loan)->client)->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-xs">{{ optional($r->loan)->loan_number }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">{{ ucfirst(str_replace('_',' ',$r->payment_method)) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold">{{ number_format($r->amount, 2) }}</td>
                        <td class="px-4 py-3">{{ optional($r->user)->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No repayments found for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Disbursements Table -->
    @if(!$type || $type === 'disbursement')
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Disbursements ({{ $disbursements->count() }})</h3>
            <span class="text-sm font-bold text-blue-600">TZS {{ number_format($totals['total_disbursed'], 2) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Loan No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Principal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Disbursed By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($disbursements as $l)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ optional($l->disbursed_at)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">{{ $l->loan_number }}</td>
                        <td class="px-4 py-3">{{ optional($l->client)->full_name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">{{ ucfirst($l->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold">{{ number_format($l->principal, 2) }}</td>
                        <td class="px-4 py-3">{{ optional($l->user)->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No disbursements found for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
