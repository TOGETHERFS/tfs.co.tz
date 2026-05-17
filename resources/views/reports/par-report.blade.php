@extends('layouts.app')

@section('title', __('messages.par_report'))
@section('page-title', __('messages.par_report'))

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.par_report') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.par_report_desc') }}</p>
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
        <form method="GET" action="{{ route('reports.par-report') }}" class="flex flex-wrap items-end gap-4">
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

    {{-- Total Portfolio Banner --}}
    <div class="bg-gradient-to-r from-cyan-600 to-teal-600 text-white rounded-xl px-6 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <p class="text-xs opacity-75 uppercase tracking-wider font-semibold">{{ __('messages.total_portfolio') }}</p>
            <p class="text-3xl font-bold">TZS {{ number_format($totalPortfolio, 0) }}</p>
        </div>
        <div class="text-right">
            <p class="text-xs opacity-75 uppercase tracking-wider font-semibold">{{ __('messages.as_of_date') }}</p>
            <p class="text-lg font-semibold">{{ \Carbon\Carbon::parse($asOf)->format('d F Y') }}</p>
        </div>
    </div>

    {{-- PAR Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            'par_1'  => ['label' => __('messages.par_1_day'),  'color' => 'yellow'],
            'par_7'  => ['label' => __('messages.par_7_days'), 'color' => 'orange'],
            'par_30' => ['label' => __('messages.par_30_days'),'color' => 'red'],
            'par_90' => ['label' => __('messages.par_90_days'),'color' => 'rose'],
        ] as $key => $cfg)
        @php $p = $par[$key] ?? ['count' => 0, 'amount' => 0, 'percent' => 0, 'days' => 0]; @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 bg-{{ $cfg['color'] }}-50 border-b border-{{ $cfg['color'] }}-100 flex items-center justify-between">
                <span class="text-sm font-bold text-{{ $cfg['color'] }}-800">{{ $cfg['label'] }}</span>
                <span class="text-xs font-medium text-{{ $cfg['color'] }}-600 bg-{{ $cfg['color'] }}-100 px-2 py-0.5 rounded-full">
                    > {{ $p['days'] }} {{ __('messages.days') }}
                </span>
            </div>
            <div class="p-5">
                <p class="text-2xl font-bold text-gray-900">{{ $p['percent'] }}%</p>
                <p class="text-xs text-gray-500 mt-1">{{ __('messages.of_portfolio') }}</p>
                <div class="mt-3 pt-3 border-t border-gray-100 space-y-1">
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500">{{ __('messages.loans_count') }}</span>
                        <span class="font-semibold text-gray-800">{{ number_format($p['count']) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500">{{ __('messages.amount') }}</span>
                        <span class="font-semibold text-{{ $cfg['color'] }}-700">TZS {{ number_format($p['amount'], 0) }}</span>
                    </div>
                </div>
                {{-- Progress bar --}}
                <div class="mt-3 bg-gray-100 rounded-full h-2">
                    <div class="bg-{{ $cfg['color'] }}-500 h-2 rounded-full transition-all"
                         style="width: {{ min(100, $p['percent']) }}%"></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- PAR Industry Benchmarks Info --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex gap-3">
        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-blue-700">
            {{ __('messages.par_benchmark_note') }}
            <strong>PAR30 &lt; 5%</strong> {{ __('messages.par_benchmark_good') }}.
            <strong>PAR30 &gt; 10%</strong> {{ __('messages.par_benchmark_critical') }}.
        </p>
    </div>

    {{-- PAR by Product --}}
    @if($parByProduct->count())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">{{ __('messages.par_by_product') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.loan_product') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.loans_count') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.outstanding_balance') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($parByProduct as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ optional($row->product)->name ?? __('messages.unknown') }}</td>
                        <td class="px-5 py-3 text-right text-gray-700">{{ number_format($row->count) }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-red-700">TZS {{ number_format($row->amount, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
