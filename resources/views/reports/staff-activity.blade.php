@extends('layouts.app')

@section('title', __('messages.staff_activity_report'))
@section('page-title', __('messages.staff_activity_report'))

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.staff_activity_report') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.staff_activity_desc') }}</p>
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
        <form method="GET" action="{{ route('reports.staff-activity') }}" class="flex flex-wrap items-end gap-4">
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
            <a href="{{ route('reports.staff-activity') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                {{ __('messages.reset_filter') }}
            </a>
        </form>
    </div>

    {{-- Summary Totals --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.staff_count') }}</p>
            <p class="text-2xl font-bold text-gray-800">{{ $staffData->count() }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.loans_created') }}</p>
            <p class="text-2xl font-bold text-blue-700">{{ number_format($totals['loans_created']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.loans_disbursed') }}</p>
            <p class="text-2xl font-bold text-green-700">{{ number_format($totals['loans_disbursed']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.disbursed_amount') }}</p>
            <p class="text-sm font-bold text-green-700">{{ number_format($totals['disbursed_amount'], 0) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.collections') }}</p>
            <p class="text-2xl font-bold text-amber-700">{{ number_format($totals['collections_count']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.amount_collected') }}</p>
            <p class="text-sm font-bold text-amber-700">{{ number_format($totals['collections_amount'], 0) }}</p>
        </div>
    </div>

    {{-- Staff Performance Table --}}
    @if($staffData->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 py-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-gray-500 text-sm font-medium">{{ __('messages.no_staff_found') }}</p>
        </div>
    @else

        {{-- Loan Officer Performance --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                <h3 class="text-base font-semibold text-gray-800">{{ __('messages.loan_officer_performance') }}</h3>
                <span class="ml-auto text-xs text-gray-500">{{ $dateFrom }} → {{ $dateTo }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.officer') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.role') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.branch') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.loans_created') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.loans_disbursed') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.disbursed_tzs') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.collections_per_officer') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.collected_tzs') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.active_portfolio') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.overdue_loans') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($staffData->sortByDesc('collections_amount') as $row)
                        @php $overduePct = $row['active_portfolio'] > 0 ? round(($row['overdue_loans'] / max(1, $row['loans_disbursed'] + 1)) * 100, 0) : 0; @endphp
                        <tr class="hover:bg-cyan-50/20 transition-colors">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-cyan-100 flex items-center justify-center text-xs font-bold text-cyan-700 flex-shrink-0">
                                        {{ strtoupper(substr($row['user']->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ $row['user']->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $row['user']->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                @php
                                    $roleColors = ['admin' => 'bg-purple-100 text-purple-700', 'manager' => 'bg-blue-100 text-blue-700', 'officer' => 'bg-green-100 text-green-700'];
                                    $rc = $roleColors[$row['user']->role ?? 'officer'] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $rc }}">
                                    {{ ucfirst($row['user']->role ?? '—') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-600">{{ optional($row['user']->branch)->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-blue-700">{{ number_format($row['loans_created']) }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-green-700">{{ number_format($row['loans_disbursed']) }}</td>
                            <td class="px-5 py-3 text-right text-green-800 text-xs">{{ number_format($row['disbursed_amount'], 0) }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-amber-700">{{ number_format($row['collections_count']) }}</td>
                            <td class="px-5 py-3 text-right text-amber-800 text-xs">{{ number_format($row['collections_amount'], 0) }}</td>
                            <td class="px-5 py-3 text-right text-gray-700 text-xs">{{ number_format($row['active_portfolio'], 0) }}</td>
                            <td class="px-5 py-3 text-right">
                                @if($row['overdue_loans'] > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                        {{ $row['overdue_loans'] }}
                                    </span>
                                @else
                                    <span class="text-green-600 font-semibold text-xs">0</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200 font-bold text-sm">
                        <tr>
                            <td colspan="3" class="px-5 py-3 text-gray-600 uppercase text-xs">{{ __('messages.totals') }}</td>
                            <td class="px-5 py-3 text-right text-blue-700">{{ number_format($totals['loans_created']) }}</td>
                            <td class="px-5 py-3 text-right text-green-700">{{ number_format($totals['loans_disbursed']) }}</td>
                            <td class="px-5 py-3 text-right text-green-800 text-xs">{{ number_format($totals['disbursed_amount'], 0) }}</td>
                            <td class="px-5 py-3 text-right text-amber-700">{{ number_format($totals['collections_count']) }}</td>
                            <td class="px-5 py-3 text-right text-amber-800 text-xs">{{ number_format($totals['collections_amount'], 0) }}</td>
                            <td class="px-5 py-3 text-right text-gray-700 text-xs">{{ number_format($totals['active_portfolio'], 0) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
