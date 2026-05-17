@extends('layouts.admin')

@section('title', 'Edit Plan')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="bg-white shadow">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="py-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Plan</h1>
                    <p class="mt-1 text-sm text-gray-600">Update plan details and pricing</p>
                </div>
                <div>
                    <a href="{{ route('admin.plans.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Back to Plans</a>
                </div>
            </div>
        </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" value="{{ old('name', $plan->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Code</label>
                            <input type="text" value="{{ $plan->code }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100" disabled>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Period</label>
                            <select name="period" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="monthly" {{ old('period', $plan->period) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            </select>
                            @error('period')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Base Price (TZS)</label>
                            <input type="number" name="price" value="{{ old('price', $plan->price) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" min="0" required>
                            <p class="mt-1 text-xs text-gray-500">Base price for 1 staff member per month</p>
                            @error('price')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Price Per Additional Staff (TZS)</label>
                            <input type="number" name="price_per_staff" value="{{ old('price_per_staff', $plan->price_per_staff) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" min="0">
                            <p class="mt-1 text-xs text-gray-500">Cost for each additional staff member per month</p>
                            @error('price_per_staff')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Price Per Additional Branch (TZS)</label>
                            <input type="number" name="price_per_branch" value="{{ old('price_per_branch', $plan->price_per_branch) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" min="0">
                            <p class="mt-1 text-xs text-gray-500">Cost for each additional branch per month</p>
                            @error('price_per_branch')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Currency</label>
                            <input type="text" name="currency" value="{{ old('currency', $plan->currency) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            @error('currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Branch Limit</label>
                            <input type="number" name="branch_limit" value="{{ old('branch_limit', $plan->branch_limit) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" min="0">
                            @error('branch_limit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Staff Limit</label>
                            <input type="number" name="staff_limit" value="{{ old('staff_limit', $plan->staff_limit) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" min="0">
                            @error('staff_limit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- SMS Pricing Section -->
                        <div class="md:col-span-2 border-t pt-4 mt-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">SMS Pricing</h3>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">SMS Price Per Unit (TZS)</label>
                            <input type="number" name="sms_price_per_unit" value="{{ old('sms_price_per_unit', $plan->sms_price_per_unit ?? 30) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" min="1" step="0.01" required>
                            <p class="mt-1 text-xs text-gray-500">Cost per SMS for tenants on this plan</p>
                            @error('sms_price_per_unit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">SMS Volume Limit</label>
                            <input type="number" name="sms_volume_limit" value="{{ old('sms_volume_limit', $plan->sms_volume_limit ?? 1000) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" min="1" required>
                            <p class="mt-1 text-xs text-gray-500">Maximum SMS at this price tier (e.g., 1000, 3000, 10000)</p>
                            @error('sms_volume_limit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                            @error('is_active')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection