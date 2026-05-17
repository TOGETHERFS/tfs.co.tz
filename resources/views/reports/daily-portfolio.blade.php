@extends('layouts.app')

@section('title', __('messages.daily_portfolio_report'))
@section('page-title', __('messages.daily_portfolio_report'))

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.daily_portfolio_report') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.daily_portfolio_desc') }}</p>
        </div>
        <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" onclick="window.print(); return false;"
           class="no-print inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            {{ __('messages.print') }}
        </a>
    </div>

    {{-- Date Filter --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 no-print">
        <form method="GET" action="{{ route('reports.daily-portfolio') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.date') }}</label>
                <input type="date" name="date" value="{{ $date }}"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold rounded-lg transition">
                {{ __('messages.apply_filter') }}
            </button>
            <a href="{{ route('reports.daily-portfolio') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                {{ __('messages.reset_filter') }}
            </a>
        </form>
    </div>

    {{-- Report Date Banner --}}
    <div class="bg-cyan-600 text-white rounded-xl px-6 py-4 flex items-center gap-3">
        <svg class="w-6 h-6 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <div>
            <p class="text-xs opacity-75 uppercase tracking-wider font-semibold">{{ __('messages.report_date') }}</p>
            <p class="text-lg font-bold">{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</p>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Total Active Loans --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.total_active_loans') }}</p>
                <div class="p-2 rounded-lg bg-blue-100">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($totalActive) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ __('messages.active_loans_desc') }}</p>
        </div>

        {{-- Total Outstanding Balance --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.total_outstanding_balance') }}</p>
                <div class="p-2 rounded-lg bg-indigo-100">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">TZS {{ number_format($totalOutstanding, 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ __('messages.outstanding_balance_desc') }}</p>
        </div>

        {{-- Disbursement Today --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.disbursement_today') }}</p>
                <div class="p-2 rounded-lg bg-green-100">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-green-700">TZS {{ number_format($disbursementAmount, 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $disbursementCount }} {{ __('messages.loans_disbursed') }}</p>
        </div>

        {{-- Collection Today --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.collection_today') }}</p>
                <div class="p-2 rounded-lg bg-amber-100">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-amber-700">TZS {{ number_format($collectionToday, 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $collectionCount }} {{ __('messages.repayments') }}</p>
        </div>
    </div>

    {{-- PAR Summary + Overdue --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                {{ __('messages.par_overview') }}
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-700">{{ __('messages.overdue_loans') }}</span>
                    <span class="font-bold text-red-700">{{ number_format($overdueCount) }} {{ __('messages.loans') }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-700">{{ __('messages.overdue_amount') }}</span>
                    <span class="font-bold text-orange-700">TZS {{ number_format($overdueAmount, 0) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 {{ $parPercent > 10 ? 'bg-red-50' : ($parPercent > 5 ? 'bg-yellow-50' : 'bg-green-50') }} rounded-lg">
                    <span class="text-sm font-medium text-gray-700">{{ __('messages.par_ratio') }}</span>
                    <span class="font-bold text-lg {{ $parPercent > 10 ? 'text-red-700' : ($parPercent > 5 ? 'text-yellow-700' : 'text-green-700') }}">
                        {{ $parPercent }}%
                    </span>
                </div>
            </div>
        </div>

        {{-- 7-Day Trend Table --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                {{ __('messages.seven_day_trend') }}
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm divide-y divide-gray-100">
                    <thead>
                        <tr class="text-xs text-gray-500 uppercase tracking-wider">
                            <th class="pb-2 text-left font-semibold">{{ __('messages.date') }}</th>
                            <th class="pb-2 text-right font-semibold">{{ __('messages.disbursed') }}</th>
                            <th class="pb-2 text-right font-semibold">{{ __('messages.collected') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($trend as $row)
                        <tr class="{{ $row['date'] === $date ? 'bg-cyan-50 font-semibold' : '' }}">
                            <td class="py-2 text-gray-700">{{ \Carbon\Carbon::parse($row['date'])->format('D, d M') }}</td>
                            <td class="py-2 text-right text-green-700">{{ number_format($row['disbursed'], 0) }}</td>
                            <td class="py-2 text-right text-amber-700">{{ number_format($row['collections'], 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
