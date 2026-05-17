@extends('layouts.app')

@section('title', 'Profit & Loss Report')

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
    $revenue   = $data['total_revenue']  ?? 0;
    $expenses  = $data['total_expenses'] ?? 0;
    $netProfit = $data['net_profit']     ?? 0;
    $margin    = $revenue > 0 ? round($netProfit / $revenue * 100, 1) : 0;
    $expCat    = $data['expenses_by_category'] ?? collect();
@endphp

<div class="space-y-6">

    <!-- Print Header -->
    <div class="print-only mb-4 text-center">
        <h1 class="text-2xl font-bold">{{ auth()->user()->tenant->name ?? 'Microfinance' }}</h1>
        <p class="text-lg">Profit &amp; Loss Report</p>
        <p class="text-sm text-gray-500">Period: {{ ($dateFrom instanceof \Carbon\Carbon ? $dateFrom : \Carbon\Carbon::parse($dateFrom))->format('M d, Y') }} - {{ ($dateTo instanceof \Carbon\Carbon ? $dateTo : \Carbon\Carbon::parse($dateTo))->format('M d, Y') }}</p>
        <p class="text-xs text-gray-400">Generated: {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reports</a>
                <span class="text-gray-400">/</span>
                <span class="text-sm font-medium text-gray-900">Profit &amp; Loss</span>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Profit &amp; Loss Report</h1>
            <p class="mt-1 text-sm text-gray-500">Financial performance overview</p>
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
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Revenue</p>
            <p class="mt-2 text-2xl font-bold text-green-700">TZS {{ number_format($revenue, 0) }}</p>
            <p class="mt-1 text-sm text-gray-500">All income sources</p>
        </div>
        <div class="bg-white shadow-sm rounded-lg border-l-4 border-l-blue-500 border border-blue-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Interest Income</p>
            <p class="mt-2 text-2xl font-bold text-blue-700">TZS {{ number_format($data['interest_income'] ?? 0, 0) }}</p>
            <p class="mt-1 text-sm text-gray-500">From loan interest</p>
        </div>
        <div class="bg-white shadow-sm rounded-lg border-l-4 border-l-red-500 border border-red-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Expenses</p>
            <p class="mt-2 text-2xl font-bold text-red-700">TZS {{ number_format($expenses, 0) }}</p>
            <p class="mt-1 text-sm text-gray-500">All expense categories</p>
        </div>
        <div class="bg-white shadow-sm rounded-lg border-l-4 {{ $netProfit >= 0 ? 'border-l-emerald-500 border-emerald-200' : 'border-l-red-600 border-red-200' }} border p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Net Profit</p>
            <p class="mt-2 text-2xl font-bold {{ $netProfit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">TZS {{ number_format($netProfit, 0) }}</p>
            <p class="mt-1 text-sm text-gray-500">Margin: {{ $margin }}%</p>
        </div>
    </div>

    <!-- Revenue vs Expenses Bar -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Revenue vs Expenses</h3>
        @php $maxVal = max($revenue, $expenses) ?: 1; @endphp
        <div class="space-y-3">
            <div>
                <div class="flex justify-between text-xs text-gray-600 mb-1">
                    <span class="font-medium text-green-700">Revenue</span>
                    <span>TZS {{ number_format($revenue, 0) }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-4">
                    <div class="bg-green-500 h-4 rounded-full" style="width: {{ round($revenue / $maxVal * 100, 1) }}%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-xs text-gray-600 mb-1">
                    <span class="font-medium text-red-700">Expenses</span>
                    <span>TZS {{ number_format($expenses, 0) }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-4">
                    <div class="bg-red-500 h-4 rounded-full" style="width: {{ round($expenses / $maxVal * 100, 1) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue & Expense Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Revenue Breakdown -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-green-200 bg-green-50">
                <h3 class="text-sm font-semibold text-green-800">Revenue Breakdown</h3>
            </div>
            <div class="divide-y divide-gray-100 text-sm">
                <div class="flex justify-between px-5 py-3 hover:bg-gray-50">
                    <span class="text-gray-700">Interest Income</span>
                    <span class="font-semibold text-gray-900">TZS {{ number_format($data['interest_income'] ?? 0, 0) }}</span>
                </div>
                <div class="flex justify-between px-5 py-3 hover:bg-gray-50">
                    <span class="text-gray-700">Management / Processing Fees</span>
                    <span class="font-semibold text-gray-900">TZS {{ number_format($data['fees_income'] ?? 0, 0) }}</span>
                </div>
                <div class="flex justify-between px-5 py-3 hover:bg-gray-50">
                    <span class="text-gray-700">Penalty Income</span>
                    <span class="font-semibold text-gray-900">TZS {{ number_format($data['penalty_income'] ?? 0, 0) }}</span>
                </div>
                <div class="flex justify-between px-5 py-3 bg-green-50 font-semibold">
                    <span class="text-green-800">Total Revenue</span>
                    <span class="text-green-700">TZS {{ number_format($revenue, 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Expenses Breakdown -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-red-200 bg-red-50">
                <h3 class="text-sm font-semibold text-red-800">Expenses Breakdown</h3>
            </div>
            <div class="divide-y divide-gray-100 text-sm">
                {{-- Salary / Wages --}}
                @if(($data['salary_expenses'] ?? 0) > 0)
                <div class="flex justify-between px-5 py-3 hover:bg-gray-50">
                    <span class="text-gray-700">Salaries &amp; Wages</span>
                    <span class="font-semibold text-gray-900">TZS {{ number_format($data['salary_expenses'], 0) }}</span>
                </div>
                @endif

                {{-- Expenses by Category --}}
                @forelse($expCat as $catName => $catAmount)
                <div class="flex justify-between px-5 py-3 hover:bg-gray-50">
                    <span class="text-gray-700">{{ $catName }}</span>
                    <span class="font-semibold text-gray-900">TZS {{ number_format($catAmount, 0) }}</span>
                </div>
                @empty
                <div class="px-5 py-3 text-gray-400 text-xs italic">No categorised expenses recorded for this period.</div>
                @endforelse

                {{-- Loan Loss Provision --}}
                @if(($data['provisions'] ?? 0) > 0)
                <div class="flex justify-between px-5 py-3 hover:bg-gray-50">
                    <span class="text-gray-700">Loan Loss Provision (5%)</span>
                    <span class="font-semibold text-gray-900">TZS {{ number_format($data['provisions'], 0) }}</span>
                </div>
                @endif

                <div class="flex justify-between px-5 py-3 bg-red-50 font-semibold">
                    <span class="text-red-800">Total Expenses</span>
                    <span class="text-red-700">TZS {{ number_format($expenses, 0) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Net Profit Summary -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6 text-center">
        <p class="text-sm font-medium text-gray-500 mb-2">Net Profit / Loss for Period</p>
        <p class="text-5xl font-bold {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
            TZS {{ number_format($netProfit, 0) }}
        </p>
        <p class="mt-3 text-sm text-gray-500">Profit Margin: <strong>{{ $margin }}%</strong></p>
        <div class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium {{ $netProfit >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
            @if($netProfit >= 0)
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
            Profitable
            @else
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            Loss
            @endif
        </div>
    </div>

    <p class="text-xs text-gray-400 no-print">All amounts in TZS. Report generated {{ now()->format('M d, Y H:i') }}.</p>
</div>
@endsection