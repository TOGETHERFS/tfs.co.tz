@extends('layouts.admin')

@section('title', 'Edit SMS Provider')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="bg-white shadow">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="py-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit SMS Provider</h1>
                    <p class="mt-1 text-sm text-gray-600">Update credentials and preferences</p>
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
                <form method="POST" action="{{ route('admin.sms-providers.update', $smsProvider) }}">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Provider</label>
                            <input type="text" value="{{ $smsProvider->name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100" disabled>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Display Name</label>
                            <input type="text" name="display_name" value="{{ old('display_name', $smsProvider->display_name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            @error('display_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cost per SMS</label>
                            <input type="number" step="0.0001" name="cost_per_sms" value="{{ old('cost_per_sms', $smsProvider->cost_per_sms) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            @error('cost_per_sms')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priority</label>
                            <input type="number" name="priority" value="{{ old('priority', $smsProvider->priority) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" min="1" required>
                            @error('priority')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="md:col-span-2 flex items-center space-x-6">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" {{ old('is_active', $smsProvider->is_active) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_primary" value="1" class="rounded border-gray-300" {{ old('is_primary', $smsProvider->is_primary) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Primary Provider</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-900">Provider Configuration</h3>
                        <p class="text-sm text-gray-600">Enter credentials required by the selected provider.</p>

                        @php($cfg = old('config', $smsProvider->config ?? []))

                        @if($smsProvider->name === 'beem_africa')
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-700 font-medium">Beem Africa</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">API Key</label>
                                <input type="text" name="config[api_key]" value="{{ $cfg['api_key'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                @error('config.api_key')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Secret Key</label>
                                <input type="password" name="config[secret_key]" value="{{ $cfg['secret_key'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                @error('config.secret_key')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sender ID (optional)</label>
                                <input type="text" name="config[sender_id]" value="{{ $cfg['sender_id'] ?? 'PHIDLMS' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Base URL (optional)</label>
                                <input type="url" name="config[base_url]" value="{{ $cfg['base_url'] ?? 'https://apisms.beem.africa/v1' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                        </div>
                        @elseif($smsProvider->name === 'route_africa')
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-700 font-medium">Route Africa</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Username</label>
                                <input type="text" name="config[username]" value="{{ $cfg['username'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Password</label>
                                <input type="password" name="config[password]" value="{{ $cfg['password'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Account ID (optional)</label>
                                <input type="text" name="config[account_id]" value="{{ $cfg['account_id'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">API Key (optional)</label>
                                <input type="text" name="config[api_key]" value="{{ $cfg['api_key'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Base URL (optional)</label>
                                <input type="url" name="config[base_url]" value="{{ $cfg['base_url'] ?? 'https://api.esmsafrica.io/api' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">Update Provider</button>
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