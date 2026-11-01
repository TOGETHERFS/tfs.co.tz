@extends('layouts.app')
@section('title', 'PAR Summary Report')
@push('styles')
<style>
@media print { .no-print { display:none !important; } .print-only { display:block !important; } body { background:white !important; } @page { margin:1cm; } }
.print-only { display:none; }
input[type="date"] { background-color:#ffffff !important; color:#111827 !important; color-scheme:light; }
</style>
@endpush

@section('content')
<div class="space-y-6">

    <div class="print-only mb-4 text-center">
        <h1 class="text-2xl font-bold">{{ auth()->user()->tenant->name ?? 'Microfinance' }}</h1>
        <p class="text-lg font-semibold">Portfolio at Risk (PAR) Summary</p>
        <p class="text-sm text-gray-500">As of: {{ \Carbon\Carbon::parse($asOf)->format('d M Y') }}</p>
        <p class="text-xs text-gray-400">Generated: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reports</a>
                <span class="text-gray-400">/</span>
                <span class="text-sm font-medium text-gray-900">PAR Summary</span>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Portfolio at Risk (PAR) Summary</h1>
            <p class="mt-1 text-sm text-gray-500">Outstanding balances of loans with overdue installments meeting each DPD threshold</p>
        </div>
        <button onclick="window.print()" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 no-print">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
    </div>

    <form method="GET" class="bg-white rounded-lg border border-gray-200 p-5 no-print">
        <div class="flex items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">As of Date</label>
                <input type="date" name="as_of_date" value="{{ $asOf }}" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Recalculate</button>
        </div>
    </form>

    <!-- Methodology Note -->
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
        <strong>PAR Methodology:</strong> PAR = Outstanding balance of loans with at least one installment overdue by ≥ N days ÷ Total outstanding portfolio balance. A loan's entire outstanding balance is counted once it meets the DPD threshold — not only the overdue installment amount.
    </div>

    <!-- Portfolio Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Active Loans</p>
            <p class="text-3xl font-bold text-gray-700 mt-1">{{ number_format($summary['total_active_loans']) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Total Outstanding Portfolio</p>
            <p class="text-3xl font-bold text-blue-600 mt-1">TZS {{ number_format($summary['total_outstanding'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-red-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Loans with Any Arrears</p>
            <p class="text-3xl font-bold text-red-600 mt-1">{{ number_format($summary['loans_in_arrears']) }}</p>
        </div>
    </div>

    <!-- PAR Brackets Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900">PAR Brackets (as of {{ \Carbon\Carbon::parse($asOf)->format('d M Y') }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bracket</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Loans at Risk</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">At-Risk Balance (TZS)</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">PAR %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($parData as $key => $par)
                    @php
                        $barWidth = min(100, $par['percent']);
                        $colorClass = $par['percent'] >= 20 ? 'bg-red-500' : ($par['percent'] >= 10 ? 'bg-orange-500' : ($par['percent'] >= 5 ? 'bg-yellow-500' : 'bg-green-500'));
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4">
                            <span class="text-lg font-bold text-gray-800">PAR {{ $par['days'] }}</span>
                        </td>
                        <td class="px-5 py-4 text-gray-600">
                            Loans with installments overdue ≥ {{ $par['days'] }} day{{ $par['days'] > 1 ? 's' : '' }}
                        </td>
                        <td class="px-5 py-4 text-center font-semibold text-gray-700">{{ number_format($par['loan_count']) }}</td>
                        <td class="px-5 py-4 text-right font-semibold text-red-600">{{ number_format($par['amount'], 2) }}</td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <div class="w-20 bg-gray-200 rounded-full h-2 no-print">
                                    <div class="{{ $colorClass }} h-2 rounded-full" style="width:{{ $barWidth }}%"></div>
                                </div>
                                <span class="font-bold text-base {{ $par['percent'] >= 20 ? 'text-red-600' : ($par['percent'] >= 10 ? 'text-orange-600' : ($par['percent'] >= 5 ? 'text-yellow-600' : 'text-green-600')) }}">
                                    {{ $par['percent'] }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 text-xs text-gray-500">
        <p><strong>Note:</strong> PAR 1 includes all loans with any overdue installment. PAR 30, 60, 90 are subsets where the oldest overdue installment exceeds 30, 60, or 90 days respectively. The denominator is the total outstanding balance of all active loans.</p>
    </div>

</div>
@endsection
