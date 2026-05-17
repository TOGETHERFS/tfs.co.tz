@extends('layouts.app')

@section('title', __('messages.branch_performance'))
@section('page-title', __('messages.branch_performance'))

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.branch_performance') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.branch_performance_desc') }}</p>
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
        <form method="GET" action="{{ route('reports.branch-performance') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.from_date') }}</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.to_date') }}</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold rounded-lg transition">
                {{ __('messages.apply_filter') }}
            </button>
            <a href="{{ route('reports.branch-performance') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                {{ __('messages.reset_filter') }}
            </a>
        </form>
    </div>

    {{-- Summary Totals --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-center">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.active_borrowers') }}</p>
            <p class="text-2xl font-bold text-indigo-700">{{ number_format($totals['active_borrowers']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-center">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.total_active_loans') }}</p>
            <p class="text-2xl font-bold text-blue-700">{{ number_format($totals['active_loans']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-center">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.total_outstanding_balance') }}</p>
            <p class="text-lg font-bold text-gray-800">TZS {{ number_format($totals['outstanding'], 0) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-center">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.loans_disbursed_period') }}</p>
            <p class="text-lg font-bold text-green-700">TZS {{ number_format($totals['disbursed_amount'], 0) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-center">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.total_repayment_period') }}</p>
            <p class="text-lg font-bold text-amber-700">TZS {{ number_format($totals['collection_total'], 0) }}</p>
        </div>
    </div>

    {{-- Branch Detail Table --}}
    @if($branchData->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 py-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-gray-500 text-sm font-medium">{{ __('messages.no_branches_found') }}</p>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800">{{ __('messages.branch_breakdown') }}</h3>
                <span class="text-xs text-gray-500">{{ $dateFrom }} → {{ $dateTo }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.branch') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.region') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.officers') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.active_borrowers') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.active_loans') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.outstanding') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.disbursed_period') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.collected_period') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($branchData as $row)
                        <tr class="hover:bg-cyan-50/30 transition-colors">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-gray-900">{{ $row['branch']->name }}</div>
                                <div class="text-xs text-gray-400">{{ $row['branch']->code }}</div>
                            </td>
                            <td class="px-5 py-4 text-gray-600 text-xs">{{ $row['branch']->region ?? '—' }}</td>
                            <td class="px-5 py-4 text-right font-medium text-gray-700">{{ $row['officer_count'] }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-indigo-700">{{ number_format($row['active_borrowers']) }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-blue-700">{{ number_format($row['active_loans']) }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-gray-800">{{ number_format($row['outstanding'], 0) }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-green-700">
                                {{ number_format($row['disbursed_amount'], 0) }}
                                <div class="text-xs font-normal text-gray-400">{{ $row['disbursed_count'] }} {{ __('messages.loans') }}</div>
                            </td>
                            <td class="px-5 py-4 text-right font-semibold text-amber-700">
                                {{ number_format($row['collection_total'], 0) }}
                                <div class="text-xs font-normal text-gray-400">{{ $row['collection_count'] }} {{ __('messages.repayments') }}</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200 font-bold">
                        <tr>
                            <td colspan="3" class="px-5 py-3 text-sm text-gray-600 uppercase">{{ __('messages.totals') }}</td>
                            <td class="px-5 py-3 text-right text-indigo-700">{{ number_format($totals['active_borrowers']) }}</td>
                            <td class="px-5 py-3 text-right text-blue-700">{{ number_format($totals['active_loans']) }}</td>
                            <td class="px-5 py-3 text-right text-gray-800">{{ number_format($totals['outstanding'], 0) }}</td>
                            <td class="px-5 py-3 text-right text-green-700">{{ number_format($totals['disbursed_amount'], 0) }}</td>
                            <td class="px-5 py-3 text-right text-amber-700">{{ number_format($totals['collection_total'], 0) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
