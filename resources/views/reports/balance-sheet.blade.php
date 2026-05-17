@extends('layouts.app')

@section('title', 'Balance Sheet')

@push('styles')
<style>
@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    body { background: white !important; }
    .shadow { box-shadow: none !important; }
    @page { margin: 1cm; size: A4; }
}
.print-only { display: none; }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Print Header -->
    <div class="print-only mb-4 text-center">
        <h1 class="text-2xl font-bold">{{ auth()->user()->tenant->name ?? 'Microfinance' }}</h1>
        <p class="text-lg">Balance Sheet</p>
        <p class="text-sm text-gray-500">As of: {{ ($asOfDate instanceof \Carbon\Carbon ? $asOfDate : \Carbon\Carbon::parse($asOfDate))->format('F j, Y') }}</p>
        <p class="text-xs text-gray-400">Generated: {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <div class="flex justify-between items-start mb-6 no-print">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Balance Sheet</h1>
            <p class="text-gray-600">Financial position as of {{ $asOfDate instanceof \Carbon\Carbon ? $asOfDate->format('F j, Y') : $asOfDate }}</p>
        </div>
        <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Print Report
        </button>
    </div>

    <!-- Date Filter -->
    <div class="bg-white rounded-lg shadow p-4 mb-6 no-print">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700">As of Date</label>
                <input type="date" name="as_of_date" value="{{ $asOfDate instanceof \Carbon\Carbon ? $asOfDate->format('Y-m-d') : $asOfDate }}" 
                       class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Generate
            </button>
            <a href="{{ route('reports.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50">Back</a>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Assets -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">Assets</h3>
            
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Current Assets</h4>
                <div class="space-y-2 ml-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Cash & Bank</span>
                        <span>{{ number_format($data['cash_bank'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Loan Portfolio (Outstanding)</span>
                        <span>{{ number_format($data['loan_portfolio'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Less: Loan Loss Provision</span>
                        <span class="text-red-600">({{ number_format($data['loan_provision'] ?? 0, 2) }})</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Accrued Interest Receivable</span>
                        <span>{{ number_format($data['accrued_interest'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Fixed Assets</h4>
                <div class="space-y-2 ml-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Property & Equipment</span>
                        <span>{{ number_format($data['fixed_assets'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="border-t pt-3 flex justify-between font-bold">
                <span>Total Assets</span>
                <span class="text-blue-600">{{ number_format($data['total_assets'] ?? 0, 2) }}</span>
            </div>
        </div>

        <!-- Liabilities & Equity -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">Liabilities & Equity</h3>
            
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Liabilities</h4>
                <div class="space-y-2 ml-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Accounts Payable</span>
                        <span>{{ number_format($data['accounts_payable'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Borrowings</span>
                        <span>{{ number_format($data['borrowings'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Other Liabilities</span>
                        <span>{{ number_format($data['other_liabilities'] ?? 0, 2) }}</span>
                    </div>
                </div>
                <div class="flex justify-between font-medium mt-2 ml-4">
                    <span>Total Liabilities</span>
                    <span>{{ number_format($data['total_liabilities'] ?? 0, 2) }}</span>
                </div>
            </div>

            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Equity</h4>
                <div class="space-y-2 ml-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Share Capital</span>
                        <span>{{ number_format($data['share_capital'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Retained Earnings</span>
                        <span>{{ number_format($data['retained_earnings'] ?? 0, 2) }}</span>
                    </div>
                </div>
                <div class="flex justify-between font-medium mt-2 ml-4">
                    <span>Total Equity</span>
                    <span>{{ number_format($data['total_equity'] ?? 0, 2) }}</span>
                </div>
            </div>

            <div class="border-t pt-3 flex justify-between font-bold">
                <span>Total Liabilities & Equity</span>
                <span class="text-blue-600">{{ number_format($data['total_liabilities_equity'] ?? 0, 2) }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
