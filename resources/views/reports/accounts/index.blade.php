@extends('layouts.app')

@section('title', 'Accounts Reports')
@section('page-title', 'Accounts Reports')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Accounts & Ledgers</h1>
            <p class="mt-1 text-sm text-gray-500">View books of accounts and ledger reports</p>
        </div>
        <div>
            <a href="{{ route('reports.index') }}" class="px-4 py-2 border rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">Back to Reports</a>
        </div>
    </div>

    @if(session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-md p-3">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Ledgers (User/Manager/Officer) -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Ledgers</h3>
            </div>
            <div class="p-6 space-y-3">
                <a href="{{ route('reports.accounts.general-ledger') }}" class="block px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-gray-900">General Ledger</h4>
                            <p class="text-sm text-gray-500">Summary of account balances</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </a>
                <a href="{{ route('reports.accounts.trial-balance') }}" class="block px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-gray-900">Trial Balance</h4>
                            <p class="text-sm text-gray-500">Debits and credits summary</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </a>
                <a href="{{ route('reports.accounts.cashbook') }}" class="block px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-gray-900">Cashbook</h4>
                            <p class="text-sm text-gray-500">Cash receipts and payments</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </a>
                <a href="{{ route('reports.accounts.bankbook') }}" class="block px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-gray-900">Bankbook</h4>
                            <p class="text-sm text-gray-500">Bank transactions register</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </a>
                <a href="{{ route('reports.accounts.client-ledger') }}" class="block px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-gray-900">Client Ledger</h4>
                            <p class="text-sm text-gray-500">Ledger by borrower</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </a>
            </div>
        </div>

        <!-- Books of Accounts (Admin) -->
        @if(auth()->user()->isAdmin())
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Books of Accounts</h3>
            </div>
            <div class="p-6 space-y-3">
                <a href="{{ route('reports.accounts.chart-of-accounts') }}" class="block px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-gray-900">Chart of Accounts</h4>
                            <p class="text-sm text-gray-500">Account structure and categories</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </a>
                <a href="{{ route('reports.accounts.journal-entries') }}" class="block px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-gray-900">Journal Entries</h4>
                            <p class="text-sm text-gray-500">Debits and credits log</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </a>
            </div>
        </div>
        @endif
    </div>

    <!-- Categories Overview: Load all categories together -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Categories Overview</h3>
            <p class="text-sm text-gray-500">Income, expenditure, and assets are displayed together here</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Income Categories -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-medium text-gray-900">Income Categories</h4>
                        <a href="{{ route('reports.accounts.income-categories') }}" class="text-sm text-indigo-600 hover:text-indigo-700">Manage</a>
                    </div>
                    <ul class="list-disc list-inside text-gray-700 space-y-1">
                        <li>Interest Income</li>
                        <li>Fee Income</li>
                        <li>Other Operating Income</li>
                    </ul>
                </div>

                <!-- Expenditure Categories -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-medium text-gray-900">Expenditure Categories</h4>
                        <a href="{{ route('reports.accounts.expenditure-categories') }}" class="text-sm text-indigo-600 hover:text-indigo-700">Manage</a>
                    </div>
                    <ul class="list-disc list-inside text-gray-700 space-y-1">
                        <li>Operating Expenses</li>
                        <li>Administrative Expenses</li>
                        <li>Financial Costs</li>
                    </ul>
                </div>

                <!-- Assets + Add Form -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-medium text-gray-900">Assets</h4>
                        <a href="{{ route('reports.accounts.assets') }}" class="text-sm text-indigo-600 hover:text-indigo-700">View All</a>
                    </div>
                    <ul class="list-disc list-inside text-gray-700 space-y-1 mb-4">
                        <li>Office Furniture - 5,000</li>
                        <li>Computers - 8,500</li>
                        <li>Branch Vehicle - 12,000</li>
                    </ul>
                    <h5 class="text-sm font-medium text-gray-900 mb-2">Add Asset</h5>
                    <form method="POST" action="{{ route('reports.accounts.assets.store') }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Asset Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="mt-1 w-full border rounded-md p-2" required>
                            @error('name')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <input type="text" name="category" value="{{ old('category') }}" class="mt-1 w-full border rounded-md p-2" required>
                            @error('category')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Amount</label>
                                <input type="number" name="amount" step="0.01" value="{{ old('amount') }}" class="mt-1 w-full border rounded-md p-2" required>
                                @error('amount')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Acquired On</label>
                                <input type="date" name="acquired_on" value="{{ old('acquired_on') }}" class="mt-1 w-full border rounded-md p-2" required>
                                @error('acquired_on')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description (optional)</label>
                            <textarea name="description" class="mt-1 w-full border rounded-md p-2" rows="2">{{ old('description') }}</textarea>
                            @error('description')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-3 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm">Save Asset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection