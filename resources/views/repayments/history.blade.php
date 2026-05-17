@extends('layouts.app')

@section('title', 'Repayment History')
@section('page-title', 'Repayment History')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Repayment History</h1>
            <p class="mt-1 text-sm text-gray-500">View and filter all repayments grouped by period</p>
        </div>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Export CSV
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <form method="GET" action="{{ route('repayments.history') }}" class="flex flex-col lg:flex-row gap-4 items-end">

            {{-- Period Tabs --}}
            <div class="flex-1">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                    Filter Period
                </label>
                <div class="flex rounded-lg overflow-hidden border border-gray-300">
                    @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $key => $label)
                    <a href="{{ route('repayments.history', array_merge(request()->except('period','page'), ['period' => $key])) }}"
                       class="flex-1 text-center px-4 py-2 text-sm font-medium transition
                           {{ $period === $key
                               ? 'bg-amber-500 text-white'
                               : 'bg-white text-gray-600 hover:bg-amber-50 hover:text-amber-700' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- From Date --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                    From Date
                </label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-amber-500 focus:border-amber-500">
            </div>

            {{-- To Date --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                    To Date
                </label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-amber-500 focus:border-amber-500">
            </div>

            <input type="hidden" name="period" value="{{ $period }}">

            <div class="flex gap-2">
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                    </svg>
                    Apply Filter
                </button>
                <a href="{{ route('repayments.history', ['period' => $period]) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center gap-4">
            <div class="p-3 rounded-full bg-amber-100">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Total Collected</p>
                <p class="text-xl font-bold text-gray-900">TZS {{ number_format($grandTotal, 2) }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center gap-4">
            <div class="p-3 rounded-full bg-blue-100">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">No. of Repayments</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($totalCount) }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center gap-4">
            <div class="p-3 rounded-full bg-green-100">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Period</p>
                <p class="text-xl font-bold text-gray-900">{{ $dateFrom }} &rarr; {{ $dateTo }}</p>
            </div>
        </div>
    </div>

    {{-- Grouped Repayment Tables --}}
    @if($summary->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 py-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-gray-500 text-sm font-medium">No repayments found for the selected period.</p>
        </div>
    @else
        @foreach($summary as $groupKey => $group)
            @php
                if ($period === 'weekly') {
                    $label = 'Week of ' . \Carbon\Carbon::parse($groupKey)->format('d M Y')
                           . ' – ' . \Carbon\Carbon::parse($groupKey)->endOfWeek()->format('d M Y');
                } elseif ($period === 'monthly') {
                    $label = \Carbon\Carbon::createFromFormat('Y-m', $groupKey)->format('F Y');
                } else {
                    $label = \Carbon\Carbon::parse($groupKey)->format('l, d F Y');
                }
            @endphp

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                {{-- Group Header --}}
                <div class="flex items-center justify-between px-5 py-3 bg-amber-50 border-b border-amber-100">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-500 text-white text-xs font-bold">
                            {{ $group['count'] }}
                        </span>
                        <span class="text-sm font-semibold text-gray-800">{{ $label }}</span>
                    </div>
                    <div class="text-sm font-bold text-amber-700">
                        TZS {{ number_format($group['total'], 2) }}
                    </div>
                </div>

                {{-- Repayments Table --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Receipt No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Borrower</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Loan No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Payment Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Paid By</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($group['items'] as $repayment)
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-4 py-3 font-mono text-xs text-gray-600 whitespace-nowrap">
                                    {{ $repayment->receipt_number }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($repayment->loan && $repayment->loan->client)
                                        <a href="{{ route('clients.show', $repayment->loan->client_id) }}"
                                           class="font-medium text-indigo-600 hover:text-indigo-800">
                                            {{ $repayment->loan->client->full_name ?? ($repayment->loan->client->first_name . ' ' . $repayment->loan->client->last_name) }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($repayment->loan)
                                        <a href="{{ route('loans.show', $repayment->loan_id) }}"
                                           class="font-mono text-xs text-blue-600 hover:text-blue-800">
                                            {{ $repayment->loan->loan_number }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($repayment->payment_date)->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-semibold text-green-700">
                                        TZS {{ number_format($repayment->amount, 2) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @php
                                        $methodColors = [
                                            'cash'          => 'bg-green-100 text-green-700',
                                            'mobile_money'  => 'bg-blue-100 text-blue-700',
                                            'bank_transfer' => 'bg-purple-100 text-purple-700',
                                            'cheque'        => 'bg-orange-100 text-orange-700',
                                        ];
                                        $method = $repayment->payment_method ?? 'cash';
                                        $color  = $methodColors[$method] ?? 'bg-gray-100 text-gray-700';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                                        {{ ucfirst(str_replace('_', ' ', $method)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap text-xs">
                                    {{ optional($repayment->user)->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <a href="{{ route('repayments.show', $repayment->id) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-amber-700 bg-amber-100 hover:bg-amber-200 rounded-full transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t border-gray-200">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-xs font-semibold text-gray-500 text-right uppercase tracking-wider">
                                    Total:
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-green-700">
                                    TZS {{ number_format($group['total'], 2) }}
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endforeach
    @endif

</div>
@endsection
