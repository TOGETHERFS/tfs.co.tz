@extends('layouts.user')

@section('title', 'Edit Account')

@section('content')
<div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('accounting.chart-of-accounts.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                ← {{ __('messages.chart_of_accounts') }}
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">
                Edit: {{ $chartOfAccount->account_code }} — {{ $chartOfAccount->account_name }}
            </h1>
            @if($chartOfAccount->is_system)
            <div class="mt-2 inline-flex items-center gap-1.5 text-xs text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-lg">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                System Account — only Name, Description and Balances can be changed
            </div>
            @endif
        </div>

        @if($errors->any())
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
            <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6">
            <form action="{{ route('accounting.chart-of-accounts.update', $chartOfAccount) }}" method="POST">
                @csrf
                @method('PUT')

                @if($chartOfAccount->is_system)
                {{-- System accounts: restricted fields only --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Account Code</label>
                        <input type="text" value="{{ $chartOfAccount->account_code }}" disabled
                            class="mt-1 block w-full rounded-md border-gray-200 bg-gray-50 text-gray-500 shadow-sm cursor-not-allowed">
                    </div>

                    <div class="md:col-span-2">
                        <label for="account_name" class="block text-sm font-medium text-gray-700">Account Name *</label>
                        <input type="text" name="account_name" id="account_name"
                            value="{{ old('account_name', $chartOfAccount->account_name) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('account_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $chartOfAccount->description) }}</textarea>
                    </div>

                    <div>
                        <label for="opening_balance" class="block text-sm font-medium text-gray-700">Opening Balance (TZS)</label>
                        <input type="number" step="0.01" name="opening_balance" id="opening_balance"
                            value="{{ old('opening_balance', $chartOfAccount->opening_balance) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Initial balance when the account was first set up</p>
                        @error('opening_balance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="current_balance" class="block text-sm font-medium text-gray-700">Current Balance (TZS)</label>
                        <input type="number" step="0.01" name="current_balance" id="current_balance"
                            value="{{ old('current_balance', $chartOfAccount->current_balance) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Adjust this to correct the displayed balance</p>
                        @error('current_balance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                @else
                {{-- Non-system accounts: full form --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="account_code" class="block text-sm font-medium text-gray-700">{{ __('messages.account_code') }} *</label>
                        <input type="text" name="account_code" id="account_code"
                            value="{{ old('account_code', $chartOfAccount->account_code) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('account_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="account_name" class="block text-sm font-medium text-gray-700">{{ __('messages.account_name') }} *</label>
                        <input type="text" name="account_name" id="account_name"
                            value="{{ old('account_name', $chartOfAccount->account_name) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('account_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">{{ __('messages.expense_category') }} *</label>
                        <select name="category_id" id="category_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $chartOfAccount->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->code }} - {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="account_type" class="block text-sm font-medium text-gray-700">{{ __('messages.account_type') }} *</label>
                        <select name="account_type" id="account_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($accountTypes as $key => $label)
                            <option value="{{ $key }}" {{ old('account_type', $chartOfAccount->account_type) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="normal_balance" class="block text-sm font-medium text-gray-700">{{ __('messages.normal_balance') }} *</label>
                        <select name="normal_balance" id="normal_balance" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="debit"   {{ old('normal_balance', $chartOfAccount->normal_balance) === 'debit'   ? 'selected' : '' }}>{{ __('messages.debit') }}</option>
                            <option value="credit"  {{ old('normal_balance', $chartOfAccount->normal_balance) === 'credit'  ? 'selected' : '' }}>{{ __('messages.credit') }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent Account</label>
                        <select name="parent_id" id="parent_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">— (Top Level)</option>
                            @foreach($parentAccounts as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id', $chartOfAccount->parent_id) == $parent->id ? 'selected' : '' }}>
                                {{ $parent->account_code }} - {{ $parent->account_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="opening_balance" class="block text-sm font-medium text-gray-700">Opening Balance (TZS)</label>
                        <input type="number" step="0.01" name="opening_balance" id="opening_balance"
                            value="{{ old('opening_balance', $chartOfAccount->opening_balance) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('opening_balance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="current_balance" class="block text-sm font-medium text-gray-700">Current Balance (TZS)</label>
                        <input type="number" step="0.01" name="current_balance" id="current_balance"
                            value="{{ old('current_balance', $chartOfAccount->current_balance) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Adjust to correct displayed balance</p>
                        @error('current_balance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">{{ __('messages.description') }}</label>
                        <textarea name="description" id="description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $chartOfAccount->description) }}</textarea>
                    </div>

                    <div class="md:col-span-2 flex flex-wrap gap-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $chartOfAccount->is_active) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">{{ __('messages.active') }}</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="allow_manual_entry" value="1" {{ old('allow_manual_entry', $chartOfAccount->allow_manual_entry) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">{{ __('messages.allow_manual_entry') }}</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_cash_account" value="1" {{ old('is_cash_account', $chartOfAccount->is_cash_account) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">{{ __('messages.is_cash_account') }}</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_bank_account" value="1" {{ old('is_bank_account', $chartOfAccount->is_bank_account) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">{{ __('messages.is_bank_account') }}</span>
                        </label>
                    </div>
                </div>
                @endif

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('accounting.chart-of-accounts.index') }}"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        {{ __('messages.cancel') }}
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        {{ __('messages.save_changes') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
