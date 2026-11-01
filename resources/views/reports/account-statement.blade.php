@extends('layouts.app')
@section('title', 'Account Statement')
@push('styles')
<style>
@media print { .no-print { display:none !important; } .print-only { display:block !important; } body { background:white !important; } @page { margin:1.5cm; size:A4; } }
.print-only { display:none; }
input[type="date"] { background-color:#ffffff !important; color:#111827 !important; color-scheme:light; }
</style>
@endpush

@section('content')
<div class="space-y-6">

    <!-- Print Header -->
    @if($loan)
    <div class="print-only mb-6">
        <div class="text-center mb-4">
            <h1 class="text-2xl font-bold">{{ auth()->user()->tenant->name ?? 'Microfinance' }}</h1>
            <p class="text-xl font-semibold mt-1">ACCOUNT STATEMENT</p>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm border-t border-b border-gray-300 py-3 mt-3">
            <div>
                <p><strong>Account:</strong> {{ $loan->loan_number }}</p>
                <p><strong>Client:</strong> {{ optional($loan->client)->full_name }}</p>
            </div>
            <div class="text-right">
                <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
                <p><strong>Generated:</strong> {{ now()->format('d M Y H:i') }}</p>
            </div>
        </div>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reports</a>
                <span class="text-gray-400">/</span>
                <span class="text-sm font-medium text-gray-900">Account Statement</span>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Account Statement</h1>
            <p class="mt-1 text-sm text-gray-500">Loan-level repayment statement with running balance</p>
        </div>
        @if($loan)
        <button onclick="window.print()" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 no-print">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print Statement
        </button>
        @endif
    </div>

    <!-- Selector -->
    <form method="GET" class="bg-white rounded-lg border border-gray-200 p-5 no-print">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Loan Account</label>
                <select name="loan_id" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">— Select Loan —</option>
                    @foreach($loans as $l)
                    <option value="{{ $l->id }}" @selected($loanId == $l->id)>
                        {{ $l->loan_number }} — {{ optional($l->client)->full_name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Generate Statement</button>
            </div>
        </div>
    </form>

    @if($loan)
    <!-- Loan Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-xs text-gray-500 uppercase">Account</p>
                <p class="font-semibold mt-1">{{ $loan->loan_number }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Client</p>
                <p class="font-semibold mt-1">{{ optional($loan->client)->full_name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Opening Balance</p>
                <p class="font-semibold mt-1">TZS {{ number_format($openingBalance, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Closing Balance</p>
                <p class="font-bold text-lg mt-0.5 {{ $closingBalance > 0 ? 'text-orange-600' : 'text-green-600' }}">TZS {{ number_format($closingBalance, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Transactions -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-700">
                Transactions: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Reference</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Credit (Payment)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Running Balance</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Processed By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr class="bg-gray-50">
                        <td class="px-4 py-3 font-semibold" colspan="4">Opening Balance</td>
                        <td class="px-4 py-3 text-right font-bold">{{ number_format($openingBalance, 2) }}</td>
                        <td></td>
                    </tr>
                    @forelse($transactions as $t)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ optional($t->payment_date)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">{{ $t->receipt_number }}</td>
                        <td class="px-4 py-3">Repayment — {{ ucfirst(str_replace('_',' ',$t->payment_method)) }}</td>
                        <td class="px-4 py-3 text-right text-green-600 font-semibold">{{ number_format($t->amount, 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $t->running_balance > 0 ? 'text-orange-600' : 'text-green-600' }}">{{ number_format($t->running_balance, 2) }}</td>
                        <td class="px-4 py-3">{{ optional($t->user)->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No transactions in this period.</td></tr>
                    @endforelse
                    <tr class="bg-gray-50 font-bold border-t-2 border-gray-300">
                        <td colspan="3" class="px-4 py-3 text-right">Closing Balance</td>
                        <td class="px-4 py-3 text-right text-green-600">{{ number_format($transactions->sum('amount'), 2) }}</td>
                        <td class="px-4 py-3 text-right text-lg {{ $closingBalance > 0 ? 'text-orange-600' : 'text-green-600' }}">{{ number_format($closingBalance, 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white rounded-lg border border-gray-200 p-12 text-center text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <p class="text-sm">Select a loan account above to generate the statement.</p>
    </div>
    @endif

</div>
@endsection
