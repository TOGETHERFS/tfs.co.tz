@extends('layouts.admin')

@section('title', $package ? 'Edit Package' : 'Create Package')
@section('page-title', $package ? 'Edit Package' : 'Create Package')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">{{ $package ? 'Edit SMS Package' : 'Create SMS Package' }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $package ? 'Update package details' : 'Define a new SMS package for tenants' }}</p>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-md bg-red-50 border border-red-200">
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <form action="{{ $package ? route('admin.sms.update-package', $package) : route('admin.sms.store-package') }}" method="POST" class="p-6 space-y-6">
            @csrf
            @if($package)
                @method('PUT')
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Package Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $package->name ?? '') }}" required
                       placeholder="e.g., Starter Pack, Business Bundle"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" placeholder="Brief description of the package..."
                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('description', $package->description ?? '') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SMS Count <span class="text-red-500">*</span></label>
                    <input type="number" name="sms_count" value="{{ old('sms_count', $package->sms_count ?? '') }}" required min="1"
                           placeholder="e.g., 500"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (TZS) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" value="{{ old('price', $package->price ?? '') }}" required min="0" step="0.01"
                           placeholder="e.g., 25000"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $package->sort_order ?? 0) }}" min="0"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Lower numbers appear first</p>
                </div>
                <div class="flex items-center pt-6">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', $package->is_active ?? true) ? 'checked' : '' }}
                           class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500">
                    <label for="is_active" class="ml-2 text-sm text-gray-700">Active (visible to tenants)</label>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('admin.sms.packages') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    {{ $package ? 'Update Package' : 'Create Package' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
