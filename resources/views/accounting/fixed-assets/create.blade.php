@extends('layouts.user')

@section('title', 'Register Fixed Asset')

@section('content')
<div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('accounting.fixed-assets.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Fixed Assets</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">Register Fixed Asset</h1>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form action="{{ route('accounting.fixed-assets.store') }}" method="POST">
                @csrf

                @if($categories->isEmpty())
                <div class="md:col-span-2 bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">No Asset Categories Found</h3>
                            <p class="mt-1 text-sm text-yellow-700">You need to create asset categories before registering fixed assets.</p>
                            <div class="mt-3">
                                <a href="{{ route('accounting.fixed-assets.categories') }}" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-yellow-800 bg-yellow-100 hover:bg-yellow-200">
                                    Create Asset Categories
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Category *</label>
                        <select name="category_id" id="category_id" required {{ $categories->isEmpty() ? 'disabled' : '' }}
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 {{ $categories->isEmpty() ? 'bg-gray-100' : '' }}">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        @if($categories->isEmpty())
                        <p class="mt-1 text-sm text-gray-500">Please create asset categories first</p>
                        @endif
                    </div>

                    <div>
                        <label for="asset_name" class="block text-sm font-medium text-gray-700">Asset Name *</label>
                        <input type="text" name="asset_name" id="asset_name" value="{{ old('asset_name') }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('asset_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="serial_number" class="block text-sm font-medium text-gray-700">Serial Number</label>
                        <input type="text" name="serial_number" id="serial_number" value="{{ old('serial_number') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                        <input type="text" name="location" id="location" value="{{ old('location') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="purchase_date" class="block text-sm font-medium text-gray-700">Purchase Date *</label>
                        <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('purchase_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="purchase_price" class="block text-sm font-medium text-gray-700">Purchase Price *</label>
                        <input type="number" step="0.01" min="0.01" name="purchase_price" id="purchase_price" value="{{ old('purchase_price') }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('purchase_price')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="salvage_value" class="block text-sm font-medium text-gray-700">Salvage Value</label>
                        <input type="number" step="0.01" min="0" name="salvage_value" id="salvage_value" value="{{ old('salvage_value', 0) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="useful_life_years" class="block text-sm font-medium text-gray-700">Useful Life (Years) *</label>
                        <input type="number" step="0.5" min="0.5" name="useful_life_years" id="useful_life_years" value="{{ old('useful_life_years', 5) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('useful_life_years')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="depreciation_method" class="block text-sm font-medium text-gray-700">Depreciation Method *</label>
                        <select name="depreciation_method" id="depreciation_method" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($depreciationMethods as $key => $label)
                            <option value="{{ $key }}" {{ old('depreciation_method', 'straight_line') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="branch_id" class="block text-sm font-medium text-gray-700">Branch</label>
                        <select name="branch_id" id="branch_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('accounting.fixed-assets.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" {{ $categories->isEmpty() ? 'disabled' : '' }} class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 {{ $categories->isEmpty() ? 'opacity-50 cursor-not-allowed' : '' }}">Register Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
