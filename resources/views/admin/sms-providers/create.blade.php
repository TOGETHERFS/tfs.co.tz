@extends('layouts.admin')

@section('title', 'Add SMS Provider')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="bg-white shadow">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="py-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Add SMS Provider</h1>
                    <p class="mt-1 text-sm text-gray-600">Configure Beem Africa or other SMS provider credentials</p>
                </div>
                <div>
                    <a href="{{ route('admin.sms-providers.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Back to Providers</a>
                </div>
            </div>
        </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white shadow rounded-lg max-w-3xl">
            <div class="px-4 py-5 sm:p-6">
                <form method="POST" action="{{ route('admin.sms-providers.store') }}" x-data="{ provider: '{{ old('name', 'beem_africa') }}' }">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Provider</label>
                            <select name="name" x-model="provider" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="beem_africa">Beem Africa</option>
                                <option value="route_africa">Route Africa</option>
                            </select>
                            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Display Name</label>
                            <input type="text" name="display_name" value="{{ old('display_name', 'Beem Africa') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            @error('display_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cost per SMS</label>
                            <input type="number" step="0.0001" name="cost_per_sms" value="{{ old('cost_per_sms', 0.0000) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            @error('cost_per_sms')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priority</label>
                            <input type="number" name="priority" value="{{ old('priority', 1) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" min="1" required>
                            @error('priority')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="md:col-span-2 flex items-center space-x-6">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" {{ old('is_active', true) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_primary" value="1" class="rounded border-gray-300" {{ old('is_primary', true) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Primary Provider</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-900">Provider Configuration</h3>
                        <p class="text-sm text-gray-600">Enter credentials required by the selected provider.</p>

                        <!-- Beem Africa Config -->
                        <div x-show="provider === 'beem_africa'" x-cloak class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-700 font-medium">Beem Africa</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">API Key</label>
                                <input type="text" name="config[api_key]" value="{{ old('config.api_key') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                @error('config.api_key')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Secret Key</label>
                                <input type="password" name="config[secret_key]" value="{{ old('config.secret_key') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                @error('config.secret_key')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sender ID (optional)</label>
                                <input type="text" name="config[sender_id]" value="{{ old('config.sender_id', 'PHIDLMS') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Base URL (optional)</label>
                                <input type="url" name="config[base_url]" value="{{ old('config.base_url', 'https://apisms.beem.africa/v1') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                        </div>

                        <!-- Route Africa Config -->
                        <div x-show="provider === 'route_africa'" x-cloak class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-700 font-medium">Route Africa</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Username</label>
                                <input type="text" name="config[username]" value="{{ old('config.username') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Password</label>
                                <input type="password" name="config[password]" value="{{ old('config.password') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Account ID (optional)</label>
                                <input type="text" name="config[account_id]" value="{{ old('config.account_id') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">API Key (optional)</label>
                                <input type="text" name="config[api_key]" value="{{ old('config.api_key') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Base URL (optional)</label>
                                <input type="url" name="config[base_url]" value="{{ old('config.base_url', 'https://api.esmsafrica.io/api') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">Save Provider</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-8 max-w-3xl text-xs text-gray-500">
            <p>Note: Only one provider can be marked as primary. Credentials are stored in the `config` JSON column of `sms_providers` and used by the messaging services.</p>
        </div>
    </div>
</div>
@endsection