@extends('layouts.app')

@section('title', 'Arrears Aging Report')

@push('styles')
<style>
@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    body { background: white !important; }
    .shadow-sm, .shadow { box-shadow: none !important; }
    @page { margin: 1cm; size: A4; }
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
@php
    $buckets = [
        ['key' => 'current',      'label' => 'Current',      'color' => 'bg-green-500',  'text' => 'text-green-700'],
        ['key' => '1_30_days',    'label' => '1-30 Days',    'color' => 'bg-yellow-400', 'text' => 'text-yellow-700'],
        ['key' => '31_60_days',   'label' => '31-60 Days',   'color' => 'bg-orange-500', 'text' => 'text-orange-700'],
        ['key' => '61_90_days',   'label' => '61-90 Days',   'color' => 'bg-red-400',    'text' => 'text-red-700'],
        ['key' => 'over_90_days', 'label' => 'Over 90 Days', 'color' => 'bg-red-700',    'text' => 'text-red-900'],
    ];
    $totalAmount   = $data['total']['amount'] ?: 1;
    $overdueAmount = ($data['1_30_days']['amount'] ?? 0) + ($data['31_60_days']['amount'] ?? 0)
                   + ($data['61_90_days']['amount'] ?? 0) + ($data['over_90_days']['amount'] ?? 0);
    $overdueCount  = ($data['1_30_days']['count'] ?? 0) + ($data['31_60_days']['count'] ?? 0)
                   + ($data['61_90_days']['count'] ?? 0) + ($data['over_90_days']['count'] ?? 0);
    $parPct = $totalAmount > 0 ? round($overdueAmount / $totalAmount * 100, 1) : 0;
@endphp

<div class="space-y-6">

    <!-- Print Header -->
    <div class="print-only mb-4 text-center">
        <h1 class="text-2xl font-bold">{{ auth()->user()->tenant->name ?? 'Microfinance' }}</h1>
        <p class="text-lg">Arrears Aging Report</p>
        <p class="text-sm text-gray-500">As of: {{ \Carbon\Carbon::parse($asOfDate)->format('M d, Y') }}</p>
        <p class="text-xs text-gray-400">Generated: {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reports</a>
                <span class="text-gray-400">/</span>
                <span class="text-sm font-medium text-gray-900">Arrears Aging</span>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Arrears Aging Report</h1>
            <p class="mt-1 text-sm text-gray-500">As of: {{ \Carbon\Carbon::parse($asOfDate)->format('M d, Y') }}</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print / PDF
            </button>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 no-print">
        <form action="{{ route('reports.arrears-aging') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">As of Date</label>
                <input type="date" name="as_of_date" value="{{ \Carbon\Carbon::parse($asOfDate)->format('Y-m-d') }}"
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-md hover:bg-primary-700">Apply Filter</button>
        </form>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Schedules</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($data['total']['count']) }}</p>
            <p class="mt-1 text-sm text-gray-500">All aging buckets</p>
        </div>
        <div class="bg-white shadow-sm rounded-lg border-l-4 border-l-red-500 border border-red-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Overdue</p>
            <p class="mt-2 text-xl font-bold text-red-700">TZS {{ number_format($overdueAmount, 0) }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ number_format($overdueCount) }} overdue loans</p>
        </div>
        <div class="bg-white shadow-sm rounded-lg border-l-4 border-l-orange-500 border border-orange-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Critical (&gt;90 days)</p>
            <p class="mt-2 text-xl font-bold text-orange-700">TZS {{ number_format($data['over_90_days']['amount'] ?? 0, 0) }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ number_format($data['over_90_days']['count'] ?? 0) }} loans</p>
        </div>
        <div class="bg-white shadow-sm rounded-lg border-l-4 border-l-purple-500 border border-purple-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">PAR Rate</p>
            <p class="mt-2 text-3xl font-bold {{ $parPct > 10 ? 'text-red-600' : 'text-green-600' }}">{{ $parPct }}%</p>
            <p class="mt-1 text-sm text-gray-500">Portfolio at risk</p>
        </div>
    </div>

    <!-- Aging Bars -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Aging Distribution by Amount</h3>
        <div class="space-y-3">
            @foreach($buckets as $b)
            @php $pct = $totalAmount > 0 ? round(($data[$b['key']]['amount'] ?? 0) / $totalAmount * 100, 1) : 0; @endphp
            <div>
                <div class="flex justify-between text-xs text-gray-600 mb-1">
                    <span class="font-medium">{{ $b['label'] }}</span>
                    <span>TZS {{ number_format($data[$b['key']]['amount'] ?? 0, 0) }} &middot; {{ $data[$b['key']]['count'] ?? 0 }} loans &middot; {{ $pct }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-3">
                    <div class="{{ $b['color'] }} h-3 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Aging Table -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900">Detailed Aging Table</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aging Bucket</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">No. of Loans</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount (TZS)</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">% of Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($buckets as $b)
                @php $pct = $totalAmount > 0 ? round(($data[$b['key']]['amount'] ?? 0) / $totalAmount * 100, 1) : 0; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full {{ $b['color'] }}"></span>
                            <span class="font-medium {{ $b['text'] }}">{{ $b['label'] }}</span>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right text-gray-700">{{ number_format($data[$b['key']]['count'] ?? 0) }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">{{ number_format($data[$b['key']]['amount'] ?? 0, 0) }}</td>
                    <td class="px-5 py-3 text-right text-gray-500">{{ $pct }}%</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td class="px-5 py-3 text-gray-900">Total</td>
                    <td class="px-5 py-3 text-right text-gray-900">{{ number_format($data['total']['count']) }}</td>
                    <td class="px-5 py-3 text-right text-gray-900">{{ number_format($data['total']['amount'], 0) }}</td>
                    <td class="px-5 py-3 text-right text-gray-500">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <p class="text-xs text-gray-400 no-print">Amounts reflect pending schedules past due as of the selected date. Generated {{ now()->format('M d, Y H:i') }}.</p>
</div>
@endsection