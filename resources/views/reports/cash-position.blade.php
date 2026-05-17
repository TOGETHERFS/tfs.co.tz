@extends('layouts.app')

@section('title', __('messages.cash_position_report'))
@section('page-title', __('messages.cash_position_report'))

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.cash_position_report') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.cash_position_desc') }}</p>
        </div>
        <button onclick="window.print()"
                class="no-print inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            {{ __('messages.print') }}
        </button>
    </div>

    {{-- Date Filter --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 no-print">
        <form method="GET" action="{{ route('reports.cash-position') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.as_of_date') }}</label>
                <input type="date" name="as_of_date" value="{{ $asOf }}"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold rounded-lg transition">
                {{ __('messages.apply_filter') }}
            </button>
        </form>
    </div>

    {{-- Cash Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

        {{-- Cash in Office --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-amber-500 px-5 py-3">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-white uppercase tracking-wider">{{ __('messages.cash_in_office') }}</p>
                </div>
            </div>
            <div class="p-5">
                <p class="text-3xl font-bold text-gray-900">TZS {{ number_format($cashInOffice, 0) }}</p>
                <p class="text-xs text-gray-500 mt-2">{{ __('messages.cash_in_office_desc') }}</p>
            </div>
        </div>

        {{-- Cash in Bank --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-blue-600 px-5 py-3">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-white uppercase tracking-wider">{{ __('messages.cash_in_bank') }}</p>
                </div>
            </div>
            <div class="p-5">
                <p class="text-3xl font-bold text-gray-900">TZS {{ number_format($cashInBank, 0) }}</p>
                <p class="text-xs text-gray-500 mt-2">{{ $bankAccounts->count() }} {{ __('messages.bank_accounts') }}</p>
            </div>
        </div>

        {{-- Total Liquidity --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-green-600 px-5 py-3">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-white uppercase tracking-wider">{{ __('messages.total_liquidity') }}</p>
                </div>
            </div>
            <div class="p-5">
                <p class="text-3xl font-bold text-green-700">TZS {{ number_format($totalLiquidity, 0) }}</p>
                <p class="text-xs text-gray-500 mt-2">{{ __('messages.total_liquidity_desc') }}</p>
            </div>
        </div>
    </div>

    {{-- Today's Movements --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center gap-4">
            <div class="p-3 rounded-full bg-green-100 flex-shrink-0">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.collections_today') }}</p>
                <p class="text-2xl font-bold text-green-700">TZS {{ number_format($collectionsToday, 0) }}</p>
                <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($asOf)->format('d F Y') }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center gap-4">
            <div class="p-3 rounded-full bg-red-100 flex-shrink-0">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.disbursements_today') }}</p>
                <p class="text-2xl font-bold text-red-700">TZS {{ number_format($disbursementsToday, 0) }}</p>
                <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($asOf)->format('d F Y') }}</p>
            </div>
        </div>
    </div>

    {{-- Bank Accounts Detail --}}
    @if($bankAccounts->count())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">{{ __('messages.bank_accounts_detail') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.account_name') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.bank_name') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.account_number') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.balance') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($bankAccounts as $account)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $account->account_holder ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $account->bank_name ?? '—' }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-gray-500">{{ $account->account_number ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-blue-700">TZS {{ number_format($account->current_balance ?? 0, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-sm font-bold text-gray-600 uppercase">{{ __('messages.total') }}</td>
                        <td class="px-5 py-3 text-right font-bold text-blue-700">TZS {{ number_format($cashInBank, 0) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    {{-- 14-Day Cash Flow Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">{{ __('messages.cash_flow_14_days') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.date') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.inflow_collections') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.outflow_disbursements') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.net') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($cashFlow as $row)
                    @php $net = $row['inflow'] - $row['outflow']; @endphp
                    <tr class="{{ $row['date'] === $asOf ? 'bg-cyan-50 font-semibold' : 'hover:bg-gray-50' }}">
                        <td class="px-5 py-3 text-gray-700">{{ \Carbon\Carbon::parse($row['date'])->format('D, d M Y') }}</td>
                        <td class="px-5 py-3 text-right text-green-700">{{ number_format($row['inflow'], 0) }}</td>
                        <td class="px-5 py-3 text-right text-red-700">{{ number_format($row['outflow'], 0) }}</td>
                        <td class="px-5 py-3 text-right font-semibold {{ $net >= 0 ? 'text-green-700' : 'text-red-700' }}">
                            {{ $net >= 0 ? '+' : '' }}{{ number_format($net, 0) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
