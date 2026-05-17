@extends('layouts.app')

@section('title', 'Loan Portfolio Report')

@push('styles')
<style>
@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    body { background: white !important; }
    .shadow-sm, .shadow { box-shadow: none !important; }
    @page { margin: 1cm; size: A4 landscape; }
}
.print-only { display: none; }
input[type="date"] {
    background-color: #ffffff !important;
    color: #111827 !important;
    color-scheme: light;
}
</style>
@endpush

@section('content')
<div class="space-y-6">

    <!-- Print Header -->
    <div class="print-only mb-4 text-center">
        <h1 class="text-2xl font-bold">{{ auth()->user()->tenant->name ?? 'Microfinance' }}</h1>
        <p class="text-sm text-gray-600">Loan Portfolio Report</p>
        <p class="text-sm text-gray-500">Period: {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</p>
        <p class="text-xs text-gray-400">Generated: {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reports</a>
                <span class="text-gray-400">/</span>
                <span class="text-sm font-medium text-gray-900">Loan Portfolio</span>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Loan Portfolio Report</h1>
            <p class="mt-1 text-sm text-gray-500">
                Period: {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print / PDF
            </button>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 no-print">
        <form action="{{ route('reports.loan-portfolio') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ \Carbon\Carbon::parse($dateFrom)->format('Y-m-d') }}"
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ \Carbon\Carbon::parse($dateTo)->format('Y-m-d') }}"
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-md hover:bg-primary-700">Apply Filter</button>
        </form>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Loans</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($data['total_loans']) }}</p>
            <p class="mt-1 text-sm text-gray-500">In selected period</p>
        </div>
        <div class="bg-white shadow-sm rounded-lg border border-blue-200 p-5 border-l-4 border-l-blue-500">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Disbursed</p>
            <p class="mt-2 text-2xl font-bold text-blue-700">TZS {{ number_format($data['total_disbursed'], 0) }}</p>
            <p class="mt-1 text-sm text-gray-500">Principal issued</p>
        </div>
        <div class="bg-white shadow-sm rounded-lg border border-yellow-200 p-5 border-l-4 border-l-yellow-500">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Outstanding</p>
            <p class="mt-2 text-2xl font-bold text-yellow-700">TZS {{ number_format($data['total_outstanding'], 0) }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ number_format($data['active_loans']) }} active loans</p>
        </div>
        <div class="bg-white shadow-sm rounded-lg border border-red-200 p-5 border-l-4 border-l-red-500">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Portfolio at Risk</p>
            <p class="mt-2 text-3xl font-bold text-red-600">{{ number_format($data['portfolio_at_risk'], 1) }}%</p>
            <p class="mt-1 text-sm text-gray-500">{{ number_format($data['overdue_loans']) }} overdue loans</p>
        </div>
    </div>

    <!-- Status breakdown bar -->
    @php
        $statuses = $data['loans_by_status'] ?? collect();
        $totalCount = $statuses->sum('count') ?: 1;
        $statusColors = ['active'=>'bg-green-500','disbursed'=>'bg-blue-500','completed'=>'bg-gray-400','pending'=>'bg-yellow-400','overdue'=>'bg-red-500','rejected'=>'bg-red-300','cancelled'=>'bg-gray-300'];
    @endphp
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Portfolio Status Breakdown</h3>
        <div class="flex rounded-full overflow-hidden h-5 mb-4">
            @foreach($statuses as $row)
            <div class="{{ $statusColors[$row->status] ?? 'bg-gray-400' }} h-5 transition-all"
                 style="width: {{ round($row->count / $totalCount * 100, 1) }}%"
                 title="{{ ucfirst($row->status) }}: {{ $row->count }}"></div>
            @endforeach
        </div>
        <div class="flex flex-wrap gap-4">
            @foreach($statuses as $row)
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <span class="w-3 h-3 rounded-full {{ $statusColors[$row->status] ?? 'bg-gray-400' }}"></span>
                {{ ucfirst($row->status) }}: <strong>{{ number_format($row->count) }}</strong>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- By Product -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">Loans by Product</h3>
                <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($dateFrom)->format('M d') }} – {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</span>
            </div>
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Loans</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount (TZS)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($data['loans_by_product'] as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ optional($row->product)->name ?? ('Product #'.$row->product_id) }}</td>
                        <td class="px-5 py-3 text-right text-gray-700">{{ number_format($row->count) }}</td>
                        <td class="px-5 py-3 text-right text-gray-700">{{ number_format($row->amount, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-5 py-6 text-center text-gray-400">No data for this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- By Status -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900">Loans by Status</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Count</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount (TZS)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($data['loans_by_status'] as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $row->status === 'active' || $row->status === 'disbursed' ? 'bg-green-100 text-green-800' :
                                   ($row->status === 'completed' ? 'bg-gray-100 text-gray-700' :
                                   ($row->status === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) }}">
                                {{ ucfirst($row->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right text-gray-700">{{ number_format($row->count) }}</td>
                        <td class="px-5 py-3 text-right text-gray-700">{{ number_format($row->amount, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-5 py-6 text-center text-gray-400">No data</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td class="px-5 py-3 font-semibold text-gray-700">Total</td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ number_format($statuses->sum('count')) }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ number_format($statuses->sum('amount'), 0) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Summary footer note -->
    <p class="text-xs text-gray-400 no-print">All amounts in Tanzanian Shillings (TZS). Report generated {{ now()->format('M d, Y H:i') }}.</p>
</div>
@endsection