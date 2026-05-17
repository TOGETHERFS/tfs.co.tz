@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Compose Message</h1>
            <p class="text-sm text-gray-600">Send SMS to your tenants</p>
        </div>
        <div class="text-sm text-gray-500">
            SMS Balance: <span class="font-semibold text-gray-900">{{ number_format($smsBalance ?? 0) }} SMS</span>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">
            {{ session('warning') }}
            @if(session('errors'))
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach(session('errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <!-- Send SMS Form -->
    <form action="{{ route('admin.sms.send.post') }}" method="POST" x-data="{
        sendTo: 'single',
        message: '',
        selectedTenants: [],
        maxLength: 800,
        get messageLength() { return this.message.length; },
        get smsCount() { return Math.ceil(this.messageLength / 160) || 0; }
    }">
        @csrf

        <!-- Recipients Section -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Recipients</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <!-- Send To Options -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Send To</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="send_to" value="single" x-model="sendTo" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">Single Number</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="send_to" value="selected" x-model="sendTo" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">Selected Tenants</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="send_to" value="all" x-model="sendTo" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">All Tenants</span>
                        </label>
                    </div>
                </div>

                <!-- Phone Number Input (for single) -->
                <div x-show="sendTo === 'single'" x-transition>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <input type="text" 
                           id="phone_number" 
                           name="phone_number" 
                           placeholder="e.g., 0712345678 or 255712345678"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Enter phone number with or without country code</p>
                </div>

                <!-- Tenant Selection (for selected) -->
                <div x-show="sendTo === 'selected'" x-transition>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Tenants</label>
                    <div class="border border-gray-300 rounded-md max-h-60 overflow-y-auto">
                        @forelse($tenants as $tenant)
                            <label class="flex items-center px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 cursor-pointer">
                                <input type="checkbox" 
                                       name="tenant_ids[]" 
                                       value="{{ $tenant->id }}"
                                       x-model="selectedTenants"
                                       class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                <div class="ml-3 flex-1">
                                    <div class="text-sm font-medium text-gray-900">{{ $tenant->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $tenant->phone ?? 'No phone' }} • {{ $tenant->contact_email }}</div>
                                </div>
                            </label>
                        @empty
                            <div class="px-4 py-3 text-sm text-gray-500">No active tenants found</div>
                        @endforelse
                    </div>
                    <p class="mt-2 text-xs text-gray-500" x-show="selectedTenants.length > 0">
                        <span x-text="selectedTenants.length"></span> tenant(s) selected
                    </p>
                </div>

                <!-- All Tenants Info -->
                <div x-show="sendTo === 'all'" x-transition>
                    <div class="bg-blue-50 border border-blue-200 rounded-md px-4 py-3">
                        <p class="text-sm text-blue-800">
                            <strong>{{ $tenants->count() }}</strong> active tenant(s) will receive this message.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Section -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mt-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Message</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <!-- Sender ID -->
                <div>
                    <label for="sender_id" class="block text-sm font-medium text-gray-700 mb-2">Sender ID</label>
                    <input type="text" 
                           id="sender_id" 
                           name="sender_id" 
                           value="PHIDTECH"
                           maxlength="11"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm"
                           required>
                    <p class="mt-1 text-xs text-gray-500">Maximum 11 characters</p>
                </div>

                <!-- Message Textarea -->
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                    <textarea id="message" 
                              name="message" 
                              rows="6"
                              x-model="message"
                              :maxlength="maxLength"
                              placeholder="Type your message here..."
                              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm"
                              required></textarea>
                    <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                        <span>Characters: <span x-text="messageLength"></span>/<span x-text="maxLength"></span></span>
                        <span>SMS count: <span x-text="smsCount"></span></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end space-x-3 mt-6">
            <a href="{{ route('admin.dashboard') }}" 
               class="px-6 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">
                Cancel
            </a>
            <button type="submit" 
                    class="inline-flex items-center px-6 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors shadow-md">
                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                Send Message
            </button>
        </div>
    </form>
</div>
@endsection
