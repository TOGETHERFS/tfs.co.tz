@extends('layouts.app')

@section('title', 'Tenant Settings')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h1 class="text-2xl font-semibold text-gray-900">Tenant Settings</h1>
                <p class="text-sm text-gray-600 mt-1">Manage your organization's settings and configuration</p>
            </div>

            <div class="p-6">
                @if (session('success'))
                    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('tenant.settings.update') }}" method="POST" class="space-y-6">
                    @csrf

                    {{-- Organization Details --}}
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">Organization Details</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Organization Name
                                </label>
                                <input type="text"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $tenant->name) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('name') border-red-300 @enderror"
                                       required>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Contact Email
                                </label>
                                <input type="email"
                                       id="contact_email"
                                       name="contact_email"
                                       value="{{ old('contact_email', $tenant->contact_email) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('contact_email') border-red-300 @enderror"
                                       required>
                                @error('contact_email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Phone Number
                                </label>
                                <input type="text"
                                       id="phone"
                                       name="phone"
                                       value="{{ old('phone', $tenant->phone) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('phone') border-red-300 @enderror">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            @if($tenant->plan)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Current Plan
                                </label>
                                <div class="px-3 py-2 bg-gray-50 border border-gray-300 rounded-md">
                                    <span class="text-sm font-medium text-gray-900">{{ $tenant->plan->name }}</span>
                                    <span class="text-sm text-gray-600 ml-2">TZS {{ number_format($tenant->plan->price) }}/month</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- SMS Configuration --}}
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 mb-1 pb-2 border-b border-gray-100">
                            SMS Configuration
                        </h2>
                        <p class="text-xs text-gray-500 mb-4">
                            Set your SMS Sender ID used for loan disbursement and repayment notifications sent to your customers.
                            The Sender ID must be registered and approved on the SMS gateway (e.g. Beem Africa) before use.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="sender_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    SMS Sender ID
                                </label>
                                <input type="text"
                                       id="sender_id"
                                       name="sender_id"
                                       value="{{ old('sender_id', optional($defaultSenderId)->sender_id) }}"
                                       maxlength="20"
                                       placeholder="e.g. MYFINANCE"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase @error('sender_id') border-red-300 @enderror">
                                @error('sender_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Max 20 characters, letters and numbers only. Will be saved in uppercase.</p>
                            </div>

                            @if($senderIds->count() > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Registered Sender IDs
                                </label>
                                <div class="space-y-1">
                                    @foreach($senderIds as $sid)
                                    <div class="flex items-center justify-between px-3 py-2 bg-gray-50 border border-gray-200 rounded-md">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-mono font-medium text-gray-900">{{ $sid->sender_id }}</span>
                                            @if($sid->is_default)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">Default</span>
                                            @endif
                                        </div>
                                        <span class="text-xs {{ $sid->provider_status === 'approved' ? 'text-green-600' : 'text-yellow-600' }}">
                                            {{ ucfirst($sid->provider_status ?? 'pending') }}
                                        </span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- SMS balance info --}}
                        @php
                            $smsBalance = \App\Models\Sms\SmsBalance::getOrCreateForTenant($tenant->id);
                        @endphp
                        <div class="mt-4 flex items-center gap-3 px-4 py-3 bg-blue-50 border border-blue-200 rounded-md">
                            <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z"/>
                            </svg>
                            <div>
                                <p class="text-sm text-blue-800">
                                    <span class="font-semibold">SMS Credits Available:</span>
                                    {{ number_format($smsBalance->balance ?? 0) }} credits
                                </p>
                                <p class="text-xs text-blue-600">Used {{ number_format($smsBalance->total_used ?? 0) }} credits total</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-200">
                        <button type="submit"
                                class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection