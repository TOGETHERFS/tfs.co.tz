@extends('layouts.app')
@section('title', 'Account Statement')
@section('page-title', 'Account Statement')

@section('content')
@php
$typeColors = [
    'asset'     => ['bg' => 'bg-blue-600',   'badge' => 'bg-blue-100 text-blue-800',   'text' => 'text-blue-700'],
    'liability' => ['bg' => 'bg-red-600',    'badge' => 'bg-red-100 text-red-800',     'text' => 'text-red-700'],
    'equity'    => ['bg' => 'bg-green-600',  'badge' => 'bg-green-100 text-green-800', 'text' => 'text-green-700'],
    'income'    => ['bg' => 'bg-purple-600', 'badge' => 'bg-purple-100 text-purple-800','text'=> 'text-purple-700'],
    'expense'   => ['bg' => 'bg-orange-600', 'badge' => 'bg-orange-100 text-orange-800','text'=> 'text-orange-700'],
];
$c = $typeColors[$account->account_type] ?? ['bg' => 'bg-gray-600', 'badge' => 'bg-gray-100 text-gray-800', 'text' => 'text-gray-700'];
@endphp

<style>
@media print {
    .no-print { display: none !important; }
    body { font-size: 12px; }
}
</style>

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
        <div>
            <a href="{{ route('accounting.reports.books-of-accounts') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Books of Accounts
            </a>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $account->account_name }}</h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="font-mono text-xs {{ $c['badge'] }} px-2 py-0.5 rounded">{{ $account->account_code }}</span>
                <span class="text-xs {{ $c['badge'] }} px-2 py-0.5 rounded capitalize">{{ ucfirst($account->account_type) }}</span>
            </div>
        </div>
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print
        </button>
    </div>

    {{-- Date Filter --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 no-print">
        <form method="GET" action="{{ route('accounting.reports.account-statement', $account) }}"
              class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">From Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">To Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-4 py-2 {{ $c['bg'] }} hover:opacity-90 text-white text-sm font-semibold rounded-lg transition">
                View Transactions
            </button>
        </form>
    </div>

    {{-- Account Info Banner --}}
    <div class="{{ $c['bg'] }} text-white rounded-xl px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 print:rounded-none">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider opacity-75">Account Statement</p>
            <p class="text-lg font-bold mt-0.5">{{ $account->account_name }}</p>
            <p class="text-sm opacity-75">
                {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            </p>
        </div>
        <div class="text-right">
            <p class="text-xs opacity-75 uppercase font-semibold">Opening Balance</p>
            <p class="text-2xl font-bold">{{ number_format($report['opening_balance'] ?? 0, 0) }} <span class="text-sm font-normal opacity-75">TZS</span></p>
        </div>
    </div>

    {{-- Transactions Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-800 text-white text-xs uppercase tracking-wider">
                    <th class="px-5 py-3 text-left font-bold border border-gray-700">Date</th>
                    <th class="px-5 py-3 text-left font-bold border border-gray-700">Ref #</th>
                    <th class="px-5 py-3 text-left font-bold border border-gray-700">Description</th>
                    <th class="px-5 py-3 text-right font-bold border border-gray-700">Debit (TZS)</th>
                    <th class="px-5 py-3 text-right font-bold border border-gray-700">Credit (TZS)</th>
                    <th class="px-5 py-3 text-right font-bold border border-gray-700">Balance (TZS)</th>
                </tr>
            </thead>
            <tbody>
                {{-- Opening Balance Row --}}
                <tr class="bg-blue-50 font-semibold">
                    <td class="px-5 py-3 border border-gray-200 text-gray-500 text-xs">—</td>
                    <td class="px-5 py-3 border border-gray-200 text-gray-500 text-xs">—</td>
                    <td class="px-5 py-3 border border-gray-200 text-gray-700 font-bold">OPENING BALANCE</td>
                    <td class="px-5 py-3 border border-gray-200"></td>
                    <td class="px-5 py-3 border border-gray-200"></td>
                    <td class="px-5 py-3 border border-gray-200 text-right font-bold text-blue-800">
                        {{ number_format($report['opening_balance'] ?? 0, 0) }}
                    </td>
                </tr>

                @forelse($report['entries'] ?? [] as $entry)
                <tr class="hover:bg-gray-50 border-b border-gray-100">
                    <td class="px-5 py-3 border border-gray-200 whitespace-nowrap text-gray-700">
                        {{ \Carbon\Carbon::parse($entry['date'])->format('d M Y') }}
                    </td>
                    <td class="px-5 py-3 border border-gray-200 whitespace-nowrap">
                        <span class="text-xs font-mono text-blue-600">{{ $entry['entry_number'] ?? '—' }}</span>
                    </td>
                    <td class="px-5 py-3 border border-gray-200 text-gray-700">
                        {{ Str::limit($entry['description'] ?? '—', 60) }}
                    </td>
                    <td class="px-5 py-3 border border-gray-200 text-right font-semibold {{ ($entry['debit'] ?? 0) > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                        {{ ($entry['debit'] ?? 0) > 0 ? number_format($entry['debit'], 0) : '—' }}
                    </td>
                    <td class="px-5 py-3 border border-gray-200 text-right font-semibold {{ ($entry['credit'] ?? 0) > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                        {{ ($entry['credit'] ?? 0) > 0 ? number_format($entry['credit'], 0) : '—' }}
                    </td>
                    <td class="px-5 py-3 border border-gray-200 text-right font-bold {{ ($entry['balance'] ?? 0) < 0 ? 'text-red-600' : 'text-gray-800' }}">
                        {{ number_format($entry['balance'] ?? 0, 0) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        <svg class="mx-auto w-10 h-10 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm font-medium">No transactions found for this period.</p>
                        <p class="text-xs mt-1">Try changing the date range above.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                {{-- Closing Balance Row --}}
                <tr class="bg-gray-800 text-white font-bold border-t-4 border-gray-600">
                    <td colspan="3" class="px-5 py-4 border border-gray-600 uppercase tracking-wide text-sm">CLOSING BALANCE</td>
                    <td class="px-5 py-4 border border-gray-600 text-right text-green-300">
                        {{ number_format($report['total_debits'] ?? 0, 0) }}
                    </td>
                    <td class="px-5 py-4 border border-gray-600 text-right text-red-300">
                        {{ number_format($report['total_credits'] ?? 0, 0) }}
                    </td>
                    <td class="px-5 py-4 border border-gray-600 text-right text-yellow-300 text-lg">
                        {{ number_format($report['closing_balance'] ?? 0, 0) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 no-print">
        <div class="bg-white border border-gray-200 rounded-xl p-5 text-center shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Debits</p>
            <p class="text-xl font-bold text-gray-800">{{ number_format($report['total_debits'] ?? 0, 0) }} <span class="text-sm font-normal text-gray-400">TZS</span></p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5 text-center shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Credits</p>
            <p class="text-xl font-bold text-gray-800">{{ number_format($report['total_credits'] ?? 0, 0) }} <span class="text-sm font-normal text-gray-400">TZS</span></p>
        </div>
        <div class="{{ $c['bg'] }} rounded-xl p-5 text-center shadow-sm text-white">
            <p class="text-xs font-semibold uppercase tracking-wider opacity-75 mb-1">Closing Balance</p>
            <p class="text-xl font-bold">{{ number_format($report['closing_balance'] ?? 0, 0) }} <span class="text-sm font-normal opacity-75">TZS</span></p>
        </div>
    </div>

</div>
@endsection
