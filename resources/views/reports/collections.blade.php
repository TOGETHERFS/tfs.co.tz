@extends('layouts.app')

@section('title', 'Collections Report')

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
    $totalCollections = $data['total_collections']  ?? 0;
    $collectionsCount = $data['collections_count']  ?? 0;
    $averagePayment   = $data['average_payment']    ?? 0;
    $collectionRate   = $data['collection_rate']    ?? 0;
    $byMethod         = $data['collections_by_method'] ?? [];
@endphp

<div class="space-y-6">

    <!-- Print Header -->
    <div class="print-only mb-4 text-center">
        <h1 class="text-2xl font-bold">{{ auth()->user()->tenant->name ?? 'Microfinance' }}</h1>
        <p class="text-lg">Collections Report</p>
        <p class="text-sm text-gray-500">Period: {{ ($dateFrom instanceof \Carbon\Carbon ? $dateFrom : \Carbon\Carbon::parse($dateFrom))->format('M d, Y') }} - {{ ($dateTo instanceof \Carbon\Carbon ? $dateTo : \Carbon\Carbon::parse($dateTo))->format('M d, Y') }}</p>
        <p class="text-xs text-gray-400">Generated: {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reports</a>
                <span class="text-gray-400">/</span>
                <span class="text-sm font-medium text-gray-900">Collections</span>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Collections Report</h1>
            <p class="mt-1 text-sm text-gray-500">Track repayment collections and payment trends</p>
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
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ $dateFrom instanceof \Carbon\Carbon ? $dateFrom->format('Y-m-d') : $dateFrom }}"
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ $dateTo instanceof \Carbon\Carbon ? $dateTo->format('Y-m-d') : $dateTo }}"
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-md hover:bg-primary-700">Apply Filter</button>
        </form>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white shadow-sm rounded-lg border-l-4 border-l-green-500 border border-green-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Collections</p>
            <p class="mt-2 text-2xl font-bold text-green-700">TZS {{ number_format($totalCollections, 0) }}</p>
            <p class="mt-1 text-sm text-gray-500">In selected period</p>
        </div>
        <div class="bg-white shadow-sm rounded-lg border-l-4 border-l-blue-500 border border-blue-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">No. of Payments</p>
            <p class="mt-2 text-3xl font-bold text-blue-700">{{ number_format($collectionsCount) }}</p>
            <p class="mt-1 text-sm text-gray-500">Transactions received</p>
        </div>
        <div class="bg-white shadow-sm rounded-lg border-l-4 border-l-purple-500 border border-purple-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Average Payment</p>
            <p class="mt-2 text-2xl font-bold text-purple-700">TZS {{ number_format($averagePayment, 0) }}</p>
            <p class="mt-1 text-sm text-gray-500">Per transaction</p>
        </div>
        <div class="bg-white shadow-sm rounded-lg border-l-4 {{ $collectionRate >= 80 ? 'border-l-emerald-500 border-emerald-200' : ($collectionRate >= 60 ? 'border-l-yellow-500 border-yellow-200' : 'border-l-red-500 border-red-200') }} border p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Collection Rate</p>
            <p class="mt-2 text-3xl font-bold {{ $collectionRate >= 80 ? 'text-emerald-700' : ($collectionRate >= 60 ? 'text-yellow-700' : 'text-red-700') }}">{{ number_format($collectionRate, 1) }}%</p>
            <p class="mt-1 text-sm text-gray-500">Of scheduled due</p>
        </div>
    </div>

    <!-- Collection Rate Bar -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Collection Rate</h3>
        <div class="flex items-center gap-4">
            <div class="flex-1 bg-gray-100 rounded-full h-5">
                <div class="{{ $collectionRate >= 80 ? 'bg-emerald-500' : ($collectionRate >= 60 ? 'bg-yellow-400' : 'bg-red-500') }} h-5 rounded-full transition-all"
                     style="width: {{ min(100, $collectionRate) }}%"></div>
            </div>
            <span class="text-lg font-bold text-gray-900 w-16 text-right">{{ number_format($collectionRate, 1) }}%</span>
        </div>
        <p class="mt-2 text-xs text-gray-500">
            @if($collectionRate >= 80) Excellent collection performance
            @elseif($collectionRate >= 60) Acceptable  room for improvement
            @else Needs attention  collection rate is low
            @endif
        </p>
    </div>

    <!-- Collections by Payment Method -->
    @if(!empty($byMethod))
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900">Collections by Payment Method</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount (TZS)</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Count</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">% Share</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($byMethod as $method)
                @php $share = $totalCollections > 0 ? round(($method->total ?? 0) / $totalCollections * 100, 1) : 0; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-900">{{ ucfirst($method->payment_method ?? 'Unknown') }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">{{ number_format($method->total ?? 0, 0) }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">{{ $method->count ?? 0 }}</td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <div class="w-16 bg-gray-100 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $share }}%"></div>
                            </div>
                            <span class="text-gray-500 text-xs">{{ $share }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td class="px-5 py-3 text-gray-900">Total</td>
                    <td class="px-5 py-3 text-right text-gray-900">TZS {{ number_format($totalCollections, 0) }}</td>
                    <td class="px-5 py-3 text-right text-gray-900">{{ number_format($collectionsCount) }}</td>
                    <td class="px-5 py-3 text-right text-gray-500">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @else
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-8 text-center text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <p class="text-sm">No payment method data available for this period.</p>
    </div>
    @endif

    <p class="text-xs text-gray-400 no-print">All amounts in TZS. Report generated {{ now()->format('M d, Y H:i') }}.</p>
</div>
@endsection