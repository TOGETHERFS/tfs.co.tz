@extends('layouts.user')

@section('title', __('messages.financial_reports'))

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('messages.financial_reports') }}</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Trial Balance -->
            <a href="{{ route('accounting.reports.trial-balance') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition group">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="ml-4 text-lg font-medium text-gray-900">{{ __('messages.trial_balance') }}</h3>
                </div>
                <p class="text-sm text-gray-500">{{ __('messages.trial_balance_desc') }}</p>
            </a>

            <!-- Balance Sheet -->
            <a href="{{ route('accounting.reports.balance-sheet') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition group">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 group-hover:bg-green-600 group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                        </svg>
                    </div>
                    <h3 class="ml-4 text-lg font-medium text-gray-900">{{ __('messages.balance_sheet') }}</h3>
                </div>
                <p class="text-sm text-gray-500">{{ __('messages.balance_sheet_desc') }}</p>
            </a>

            <!-- Income Statement -->
            <a href="{{ route('accounting.reports.income-statement') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition group">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="ml-4 text-lg font-medium text-gray-900">{{ __('messages.profit_loss') }}</h3>
                </div>
                <p class="text-sm text-gray-500">{{ __('messages.income_statement_desc') }}</p>
            </a>

            <!-- Cash Flow -->
            <a href="{{ route('accounting.reports.cash-flow') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition group">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600 group-hover:bg-orange-600 group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                        </svg>
                    </div>
                    <h3 class="ml-4 text-lg font-medium text-gray-900">{{ __('messages.cash_flow_statement') }}</h3>
                </div>
                <p class="text-sm text-gray-500">{{ __('messages.cash_flow_desc') }}</p>
            </a>

            <!-- General Ledger -->
            <a href="{{ route('accounting.reports.general-ledger') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition group">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <h3 class="ml-4 text-lg font-medium text-gray-900">General Ledger</h3>
                </div>
                <p class="text-sm text-gray-500">Detailed transaction history for any account with running balances.</p>
            </a>

            <!-- Audit Trail -->
            <a href="{{ route('accounting.audit-trail.index') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition group">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-full bg-gray-100 text-gray-600 group-hover:bg-gray-600 group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                    <h3 class="ml-4 text-lg font-medium text-gray-900">Audit Trail</h3>
                </div>
                <p class="text-sm text-gray-500">Complete history of all accounting actions for compliance and auditing.</p>
            </a>
        </div>
    </div>
</div>
@endsection
