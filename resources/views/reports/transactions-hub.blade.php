@extends('layouts.app')
@section('title', 'Transaction Reports')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-5 flex items-center gap-3">
        <svg class="w-6 h-6 text-teal-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <h1 class="text-xl font-semibold text-gray-900">Transaction Reports</h1>
    </div>

    {{-- Report Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        @php
        $cards = [
            [
                'title' => 'Daily Transaction Report',
                'route' => 'reports.daily-transactions',
            ],
            [
                'title' => 'Expense Report',
                'route' => 'reports.expense-report',
            ],
            [
                'title' => 'Fees Income Report',
                'route' => 'reports.fee-income',
            ],
            [
                'title' => 'Reversed Transaction Report',
                'route' => null,
            ],
        ];
        @endphp

        @foreach($cards as $card)
        @php $href = $card['route'] ? route($card['route']) : '#'; @endphp
        <a href="{{ $href }}"
           class="group flex items-center justify-between bg-white border border-gray-200 rounded-xl px-5 py-4 shadow-sm hover:shadow-md hover:border-teal-400 transition-all duration-150 {{ $card['route'] ? '' : 'opacity-60 cursor-not-allowed' }}">
            <span class="text-sm font-medium text-gray-800 group-hover:text-teal-700 transition-colors">{{ $card['title'] }}</span>
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </a>
        @endforeach

    </div>
</div>
@endsection
