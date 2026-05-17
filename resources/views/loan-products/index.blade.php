@extends('layouts.app')

@section('title', __('messages.loan_products'))
@section('page-title', __('messages.loan_products'))

@section('content')
<div class="space-y-6" x-data="{showCreate:false}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.loan_products') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.manage_loan_products') }}</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button type="button" @click="showCreate = !showCreate" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                {{ __('messages.add_product') }}
            </button>
        </div>
    </div>

    <!-- Inline Create Form -->
    <div x-show="showCreate" x-cloak class="bg-white shadow-sm rounded-lg border border-gray-200">
        <form method="POST" action="{{ route('settings.loan-products.store') }}" class="p-6 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.product_name') }}</label>
                    <input name="name" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('messages.product_name_placeholder') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.interest_type') }}</label>
                    <select name="interest_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="flat">{{ __('messages.flat_rate') }}</option>
                        <option value="reducing">{{ __('messages.reducing_balance') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.interest_rate_per_month') }}</label>
                    <input name="interest_rate" type="number" step="0.01" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('messages.interest_rate_placeholder') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.processing_fee_type') }}</label>
                    <select name="processing_fee_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="percentage">{{ __('messages.percentage') }}</option>
                        <option value="fixed">{{ __('messages.fixed_amount') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.processing_fee') }}</label>
                    <input name="processing_fee" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('messages.processing_fee_placeholder') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.min_amount') }}</label>
                    <input name="min_amount" type="number" step="1" min="5000" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('messages.min_amount_placeholder') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.max_amount') }}</label>
                    <input name="max_amount" type="number" step="1" min="5000" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('messages.max_amount_placeholder') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.min_term') }}</label>
                    <input name="min_term" type="number" step="1" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('messages.min_term_placeholder') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.max_term') }}</label>
                    <input name="max_term" type="number" step="1" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('messages.max_term_placeholder') }}">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">{{ __('messages.description') }}</label>
                    <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('messages.description_placeholder') }}"></textarea>
                </div>
                <div class="flex items-center space-x-2">
                    <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="is_active" class="text-sm text-gray-700">{{ __('messages.active') }}</label>
                </div>
            </div>
            <div class="flex items-center justify-end space-x-2">
                <button type="button" @click="showCreate=false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">{{ __('messages.cancel') }}</button>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">{{ __('messages.save_product') }}</button>
            </div>
        </form>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <form method="GET" action="{{ route('settings.loan-products') }}" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">{{ __('messages.search') }}</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('messages.search_placeholder') }}">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">{{ __('messages.loan_status') }}</label>
                    <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">{{ __('messages.all') }}</option>
                        <option value="active" @selected(request('status')==='active')>{{ __('messages.active') }}</option>
                        <option value="inactive" @selected(request('status')==='inactive')>{{ __('messages.inactive') }}</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full md:w-auto inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">{{ __('messages.apply') }}</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="p-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.product_name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.rate_per_month') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.interest_type') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.amount_range') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.term_months') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.loans') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($products as $product)
                        <tr x-data="{ openEdit: false }">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                <div class="text-sm text-gray-500">{{ $product->is_active ? __('messages.active') : __('messages.inactive') }}</div>
                            </td>
                            <td class="px-4 py-3">{{ number_format($product->interest_rate, 2) }}</td>
                            <td class="px-4 py-3">{{ str_replace('_', ' ', $product->interest_type) }}</td>
                            <td class="px-4 py-3">
                                {{ number_format($product->min_amount, 0) }} - {{ number_format($product->max_amount, 0) }}
                            </td>
                            <td class="px-4 py-3">{{ $product->min_term }} - {{ $product->max_term }}</td>
                            <td class="px-4 py-3">{{ $product->loans_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <form method="POST" action="{{ route('settings.loan-products.toggle', $product) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-3 py-1 rounded-md text-xs {{ $product->is_active ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-green-600 hover:bg-green-700' }} text-white">
                                        {{ $product->is_active ? __('messages.deactivate') : __('messages.activate') }}
                                    </button>
                                </form>
                                <button type="button" @click="openEdit = true" class="px-3 py-1 rounded-md text-xs bg-indigo-600 text-white hover:bg-indigo-700">{{ __('messages.edit') }}</button>
                                <form method="POST" action="{{ route('settings.loan-products.delete', $product) }}" class="inline" onsubmit="return confirm('{{ __('messages.delete_confirmation') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1 rounded-md text-xs bg-red-600 text-white hover:bg-red-700">{{ __('messages.delete') }}</button>
                                </form>
                                <!-- Edit Modal -->
                                <div x-show="openEdit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                    <div class="absolute inset-0 bg-black opacity-50" @click="openEdit=false"></div>
                                    <div class="relative bg-white w-full max-w-2xl rounded-lg shadow-xl overflow-hidden">
                                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                                            <h3 class="text-lg font-semibold text-gray-900">{{ __('messages.edit_loan_product') }}</h3>
                                            <button type="button" @click="openEdit=false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                                        </div>
                                        <form method="POST" action="{{ route('settings.loan-products.update', $product) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="p-6 space-y-4 max-h-96 overflow-y-auto">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.product_name') }}</label>
                                                        <input name="name" type="text" value="{{ $product->name }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.interest_type') }}</label>
                                                        <select name="interest_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                            <option value="flat" @selected($product->interest_type==='flat')>{{ __('messages.flat_rate') }}</option>
                                                            <option value="reducing_balance" @selected($product->interest_type==='reducing_balance' || $product->interest_type==='reducing')>{{ __('messages.reducing_balance') }}</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.interest_rate_per_month') }}</label>
                                                        <input name="interest_rate" type="number" step="0.01" min="1" value="{{ $product->interest_rate }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.processing_fee_type') }}</label>
                                                        <select name="processing_fee_type" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                            <option value="percentage" @selected(($product->processing_fee_type ?? 'percentage') === 'percentage')>{{ __('messages.percentage') }}</option>
                                                            <option value="fixed" @selected(($product->processing_fee_type ?? 'percentage') === 'fixed')>{{ __('messages.fixed_amount') }}</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.processing_fee') }}</label>
                                                        <input name="processing_fee" type="number" step="0.01" min="0" value="{{ $product->processing_fee ?? 0 }}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.min_amount') }}</label>
                                                        <input name="min_amount" type="number" step="1" min="5000" value="{{ $product->min_amount }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.max_amount') }}</label>
                                                        <input name="max_amount" type="number" step="1" min="5000" value="{{ $product->max_amount }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.min_term') }}</label>
                                                        <input name="min_term" type="number" step="1" min="1" value="{{ $product->min_term }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.max_term') }}</label>
                                                        <input name="max_term" type="number" step="1" min="1" value="{{ $product->max_term }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                    </div>
                                                    <div class="md:col-span-2">
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.description') }}</label>
                                                        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ $product->description }}</textarea>
                                                    </div>
                                                    <div class="md:col-span-2">
                                                        <label class="flex items-center space-x-2 cursor-pointer">
                                                            <input name="is_active" type="checkbox" value="1" @checked($product->is_active) class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                            <span class="text-sm text-gray-700">{{ __('messages.active_available') }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-end space-x-3">
                                                <button type="button" @click="openEdit=false" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 font-medium">{{ __('messages.cancel') }}</button>
                                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">{{ __('messages.save_changes') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">{{ __('messages.no_loan_products') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>
@endsection