@extends('layouts.app')
@section('title', 'Books of Accounts')
@section('page-title', 'Books of Accounts')

@section('content')
@php
$colorMap = [
    'blue'   => ['bg' => 'bg-blue-600',   'light' => 'bg-blue-50',   'border' => 'border-blue-200', 'text' => 'text-blue-700',   'badge' => 'bg-blue-100 text-blue-800'],
    'red'    => ['bg' => 'bg-red-600',    'light' => 'bg-red-50',    'border' => 'border-red-200',  'text' => 'text-red-700',    'badge' => 'bg-red-100 text-red-800'],
    'green'  => ['bg' => 'bg-green-600',  'light' => 'bg-green-50',  'border' => 'border-green-200','text' => 'text-green-700',  'badge' => 'bg-green-100 text-green-800'],
    'purple' => ['bg' => 'bg-purple-600', 'light' => 'bg-purple-50', 'border' => 'border-purple-200','text'=> 'text-purple-700', 'badge' => 'bg-purple-100 text-purple-800'],
    'orange' => ['bg' => 'bg-orange-600', 'light' => 'bg-orange-50', 'border' => 'border-orange-200','text'=> 'text-orange-700', 'badge' => 'bg-orange-100 text-orange-800'],
];
$icons = [
    'blue'   => '🏦',
    'red'    => '📋',
    'green'  => '💰',
    'purple' => '📈',
    'orange' => '📉',
];
@endphp

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Books of Accounts</h1>
            <p class="mt-1 text-sm text-gray-500">Click any account to view its transactions and ledger</p>
        </div>
        <a href="{{ route('accounting.chart-of-accounts.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Account
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-800 font-medium">
        {{ session('success') }}
    </div>
    @endif

    {{-- Account Groups --}}
    @foreach($grouped as $type => $group)
    @php $c = $colorMap[$group['color']]; @endphp

    @if($group['accounts']->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border {{ $c['border'] }} overflow-hidden">

        {{-- Group Header --}}
        <div class="{{ $c['bg'] }} px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-xl">{{ $icons[$group['color']] }}</span>
                <h2 class="text-white font-bold text-base uppercase tracking-wider">{{ $group['label'] }}</h2>
            </div>
            <span class="bg-white bg-opacity-20 text-white text-xs font-semibold px-2.5 py-1 rounded-full">
                {{ $group['accounts']->count() }} account{{ $group['accounts']->count() != 1 ? 's' : '' }}
            </span>
        </div>

        {{-- Accounts Table --}}
        <table class="min-w-full text-sm divide-y divide-gray-100">
            <thead class="{{ $c['light'] }}">
                <tr>
                    <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Code</th>
                    <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Account Name</th>
                    <th class="px-6 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Current Balance</th>
                    <th class="px-6 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($group['accounts'] as $account)
                <tr class="hover:{{ $c['light'] }} transition-colors group">
                    <td class="px-6 py-3 whitespace-nowrap">
                        <span class="font-mono text-xs {{ $c['badge'] }} px-2 py-0.5 rounded">{{ $account->account_code }}</span>
                    </td>
                    <td class="px-6 py-3">
                        <p class="font-medium text-gray-900">{{ $account->account_name }}</p>
                        @if($account->description)
                        <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($account->description, 60) }}</p>
                        @endif
                        @if($account->is_system)
                        <span class="text-xs text-amber-600 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded mt-0.5 inline-block">System</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-right">
                        <span class="font-bold text-base {{ $account->current_balance < 0 ? 'text-red-600' : $c['text'] }}">
                            {{ number_format($account->current_balance, 0) }}
                        </span>
                        <span class="text-xs text-gray-400 ml-1">TZS</span>
                    </td>
                    <td class="px-6 py-3 text-center">
                        <a href="{{ route('accounting.reports.account-statement', $account) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 {{ $c['bg'] }} hover:opacity-90 text-white text-xs font-semibold rounded-lg transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            View Ledger
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="{{ $c['light'] }} border-t-2 {{ $c['border'] }}">
                <tr>
                    <td colspan="2" class="px-6 py-2.5 text-xs font-bold {{ $c['text'] }} uppercase">Total {{ $group['label'] }}</td>
                    <td class="px-6 py-2.5 text-right font-bold {{ $c['text'] }}">
                        {{ number_format($group['accounts']->sum('current_balance'), 0) }} TZS
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @else
    <div class="bg-white rounded-xl border {{ $c['border'] }} overflow-hidden">
        <div class="{{ $c['bg'] }} px-6 py-3 flex items-center gap-3">
            <span class="text-xl">{{ $icons[$group['color']] }}</span>
            <h2 class="text-white font-bold text-base uppercase tracking-wider">{{ $group['label'] }}</h2>
        </div>
        <div class="px-6 py-8 text-center">
            <p class="text-gray-400 text-sm mb-3">No {{ strtolower($group['label']) }} accounts yet.</p>
            <a href="{{ route('accounting.chart-of-accounts.create', ['type' => $type]) }}"
               class="text-sm {{ $c['text'] }} font-semibold hover:underline">
                + Add {{ $group['label'] }} Account
            </a>
        </div>
    </div>
    @endif

    @endforeach

</div>
@endsection
