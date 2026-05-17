@extends('layouts.user')

@section('title', __('messages.chart_of_accounts'))

@section('content')
@php
$tabs = [
    ''          => __('messages.all_types'),
    'asset'     => __('messages.acc_assets'),
    'liability' => __('messages.acc_liabilities'),
    'equity'    => __('messages.acc_equity'),
    'income'    => __('messages.acc_revenue'),
    'expense'   => __('messages.acc_expenses'),
];
$typeColors = [
    'asset'     => 'bg-blue-100 text-blue-800',
    'liability' => 'bg-red-100 text-red-800',
    'equity'    => 'bg-green-100 text-green-800',
    'income'    => 'bg-purple-100 text-purple-800',
    'expense'   => 'bg-orange-100 text-orange-800',
];
@endphp

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.chart_of_accounts') }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ __('messages.accounting') }} — {{ __('messages.setup_default_prompt') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <form action="{{ route('accounting.chart-of-accounts.setup-defaults') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('{{ __('messages.setup_defaults') }}?')"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ __('messages.setup_defaults') }}
                    </button>
                </form>
                <a href="/accounting/chart-of-accounts/opening-balance"
                   class="inline-flex items-center px-3 py-2 border border-green-600 text-sm font-medium rounded-lg text-green-700 bg-green-50 hover:bg-green-100 shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Opening Balance
                </a>
                <a href="{{ route('accounting.chart-of-accounts.create') }}"
                   class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('messages.add_account') }}
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg">
            <p class="text-green-800 text-sm font-medium">{{ session('success') }}</p>
        </div>
        @endif
        @if(session('error'))
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
            <p class="text-red-800 text-sm font-medium">{{ session('error') }}</p>
        </div>
        @endif

        {{-- Category Tabs --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-4">
            <div class="flex overflow-x-auto border-b border-gray-200">
                @foreach($tabs as $tabKey => $tabLabel)
                <a href="{{ route('accounting.chart-of-accounts.index', array_filter(['type' => $tabKey, 'search' => $search])) }}"
                   class="flex-shrink-0 px-5 py-3 text-sm font-medium border-b-2 transition whitespace-nowrap
                          {{ $type === $tabKey
                              ? 'border-blue-600 text-blue-700 bg-blue-50/40'
                              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $tabLabel }}
                </a>
                @endforeach
            </div>

            {{-- Search bar --}}
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/50">
                <form action="{{ route('accounting.chart-of-accounts.index') }}" method="GET" class="flex gap-3 items-center">
                    @if($type)
                    <input type="hidden" name="type" value="{{ $type }}">
                    @endif
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="{{ __('messages.search') }}..."
                           class="flex-1 text-sm border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <button type="submit" class="px-4 py-2 bg-gray-700 text-white text-sm rounded-lg hover:bg-gray-800">
                        {{ __('messages.filter') }}
                    </button>
                    @if($search)
                    <a href="{{ route('accounting.chart-of-accounts.index', ['type' => $type]) }}"
                       class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">✕ {{ __('messages.clear') }}</a>
                    @endif
                </form>
            </div>

            {{-- Accounts Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.account_code') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.account_name') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.account_type') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.normal_balance') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.current_balance') }}</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.status') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($accounts as $account)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="font-mono text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded">{{ $account->account_code }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-gray-900">{{ $account->account_name }}</div>
                                @if($account->description)
                                <div class="text-xs text-gray-400 mt-0.5">{{ Str::limit($account->description, 55) }}</div>
                                @endif
                                @if($account->is_system)
                                <span class="inline-block mt-0.5 text-xs text-amber-600 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">
                                    {{ __('messages.is_system_account') }}
                                </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $typeColors[$account->account_type] ?? 'bg-gray-100 text-gray-700' }}">
                                    @php
                                    $typeLabels = [
                                        'asset'     => __('messages.acc_assets'),
                                        'liability' => __('messages.acc_liabilities'),
                                        'equity'    => __('messages.acc_equity'),
                                        'income'    => __('messages.acc_revenue'),
                                        'expense'   => __('messages.acc_expenses'),
                                    ];
                                    @endphp
                                    {{ $typeLabels[$account->account_type] ?? ucfirst($account->account_type) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ $account->normal_balance === 'debit' ? __('messages.debit') : __('messages.credit') }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-sm text-right font-semibold {{ $account->current_balance < 0 ? 'text-red-600' : 'text-gray-800' }}">
                                {{ number_format($account->current_balance, 2) }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-center">
                                @if($account->is_active)
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">{{ __('messages.active') }}</span>
                                @else
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">{{ __('messages.inactive') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('accounting.reports.account-statement', $account) }}"
                                       class="text-xs px-2 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg font-medium">
                                        {{ __('messages.account_ledger') }}
                                    </a>
                                    <a href="{{ route('accounting.chart-of-accounts.edit', $account) }}"
                                       class="text-xs px-2 py-1 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg font-medium">
                                        {{ __('messages.edit') }}
                                    </a>
                                    @if(!$account->is_system)
                                    <form action="{{ route('accounting.chart-of-accounts.destroy', $account) }}" method="POST" class="inline"
                                          onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs px-2 py-1 bg-red-50 text-red-700 hover:bg-red-100 rounded-lg font-medium">
                                            {{ __('messages.delete') }}
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400 mb-3">
                                    <svg class="mx-auto w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 text-sm mb-3">{{ __('messages.no_accounts_found') }}</p>
                                <form action="{{ route('accounting.chart-of-accounts.setup-defaults') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-sm text-blue-600 hover:underline font-medium">
                                        {{ __('messages.setup_default_prompt') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($accounts->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $accounts->withQueryString()->links() }}
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
