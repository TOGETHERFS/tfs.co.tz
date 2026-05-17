@extends('layouts.app')
@section('title', 'Add Account')
@section('page-title', 'Add Account')

@section('content')
@php
$types = [
    'asset'     => ['label' => 'Assets',          'desc' => 'Cash, bank, loans receivable, equipment', 'icon' => '🏦', 'color' => 'blue'],
    'liability' => ['label' => 'Liabilities',      'desc' => 'Loans payable, borrowings, payables',     'icon' => '📋', 'color' => 'red'],
    'equity'    => ['label' => 'Equity / Capital', 'desc' => 'Share capital, retained earnings',         'icon' => '💰', 'color' => 'green'],
    'income'    => ['label' => 'Revenue / Income', 'desc' => 'Interest income, fees, penalties',         'icon' => '📈', 'color' => 'purple'],
    'expense'   => ['label' => 'Expenses',         'desc' => 'Salaries, rent, utilities, other costs',  'icon' => '📉', 'color' => 'orange'],
];
$colorMap = [
    'blue'   => ['ring' => 'ring-blue-500 bg-blue-50 border-blue-400',   'btn' => 'bg-blue-600 hover:bg-blue-700',   'text' => 'text-blue-700'],
    'red'    => ['ring' => 'ring-red-500 bg-red-50 border-red-400',       'btn' => 'bg-red-600 hover:bg-red-700',     'text' => 'text-red-700'],
    'green'  => ['ring' => 'ring-green-500 bg-green-50 border-green-400', 'btn' => 'bg-green-600 hover:bg-green-700', 'text' => 'text-green-700'],
    'purple' => ['ring' => 'ring-purple-500 bg-purple-50 border-purple-400','btn'=> 'bg-purple-600 hover:bg-purple-700','text'=> 'text-purple-700'],
    'orange' => ['ring' => 'ring-orange-500 bg-orange-50 border-orange-400','btn'=> 'bg-orange-600 hover:bg-orange-700','text'=> 'text-orange-700'],
];
$selected = old('account_type', request('type', ''));
@endphp

<div class="max-w-2xl mx-auto space-y-6">

    {{-- Back link --}}
    <div>
        <a href="{{ route('accounting.chart-of-accounts.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Chart of Accounts
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Add New Account</h1>
        <p class="text-sm text-gray-500 mt-1">Select the account type, enter a name and opening balance — done.</p>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $error)<li>• {{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('accounting.chart-of-accounts.store') }}" method="POST" id="addAccountForm">
        @csrf

        {{-- Step 1: Choose Type --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Step 1 — What type of account?</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($types as $key => $info)
                @php $c = $colorMap[$info['color']]; @endphp
                <label class="relative cursor-pointer">
                    <input type="radio" name="account_type" value="{{ $key }}"
                           {{ $selected === $key ? 'checked' : '' }}
                           class="sr-only peer" required>
                    <div class="border-2 rounded-xl p-4 transition-all peer-checked:ring-2 peer-checked:{{ $c['ring'] }} border-gray-200 hover:border-gray-300">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">{{ $info['icon'] }}</span>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $info['label'] }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $info['desc'] }}</p>
                            </div>
                        </div>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Step 2: Account Details --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Step 2 — Account details</h2>
            <div class="space-y-5">
                <div>
                    <label for="account_name" class="block text-sm font-semibold text-gray-700 mb-1">
                        Account Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="account_name" id="account_name"
                           value="{{ old('account_name') }}"
                           placeholder="e.g. Cash on Hand, Bank NMB, Rent Expense"
                           required autocomplete="off"
                           class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-cyan-500 focus:border-cyan-500">
                </div>

                <div>
                    <label for="opening_balance" class="block text-sm font-semibold text-gray-700 mb-1">
                        Opening Balance (TZS)
                        <span class="text-xs font-normal text-gray-400 ml-1">— leave 0 if starting fresh</span>
                    </label>
                    <input type="number" name="opening_balance" id="opening_balance"
                           value="{{ old('opening_balance', 0) }}" min="0" step="0.01"
                           class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-cyan-500 focus:border-cyan-500">
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">
                        Description <span class="text-xs font-normal text-gray-400">(optional)</span>
                    </label>
                    <input type="text" name="description" id="description"
                           value="{{ old('description') }}"
                           placeholder="Brief note about this account"
                           class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-cyan-500 focus:border-cyan-500">
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('accounting.chart-of-accounts.index') }}"
               class="px-5 py-2.5 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold rounded-lg shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Account
            </button>
        </div>

    </form>
</div>
@endsection
