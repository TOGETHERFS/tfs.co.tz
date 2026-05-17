@extends('layouts.admin')

@section('title', 'SMS Diagnostics')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">SMS System Diagnostics</h1>
            <p class="mt-1 text-sm text-gray-500">Check SMS configuration and troubleshoot issues</p>
        </div>
    </div>

    <!-- Beem Africa Configuration -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Beem Africa Configuration</h2>
        
        @php
            $beemSettings = \App\Models\Sms\SmsProviderSetting::where('provider', 'beem_africa')->first();
        @endphp

        <div class="space-y-3">
            <div class="flex items-center justify-between py-2 border-b">
                <span class="text-sm font-medium text-gray-700">Provider Status</span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $beemSettings && $beemSettings->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $beemSettings && $beemSettings->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <div class="flex items-center justify-between py-2 border-b">
                <span class="text-sm font-medium text-gray-700">API Key Configured</span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $beemSettings && $beemSettings->api_key ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $beemSettings && $beemSettings->api_key ? 'Yes' : 'No' }}
                </span>
            </div>

            <div class="flex items-center justify-between py-2 border-b">
                <span class="text-sm font-medium text-gray-700">Secret Key Configured</span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $beemSettings && $beemSettings->secret_key ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $beemSettings && $beemSettings->secret_key ? 'Yes' : 'No' }}
                </span>
            </div>

            @if($beemSettings)
            <div class="flex items-center justify-between py-2 border-b">
                <span class="text-sm font-medium text-gray-700">Provider Balance</span>
                <span class="text-sm font-semibold text-gray-900">{{ number_format($beemSettings->provider_balance ?? 0) }} SMS</span>
            </div>
            @endif
        </div>

        @if(!$beemSettings || !$beemSettings->api_key || !$beemSettings->secret_key)
        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
            <p class="text-sm text-red-800">
                <strong>Action Required:</strong> Beem Africa API credentials are not configured. 
                <a href="{{ route('admin.sms-providers.index') }}" class="underline font-semibold">Configure now</a>
            </p>
        </div>
        @endif
    </div>

    <!-- Sender IDs -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Sender IDs</h2>
        
        @php
            $senderIds = \App\Models\Sms\SmsSenderId::where('is_active', true)->get();
        @endphp

        @if($senderIds->count() > 0)
        <div class="space-y-2">
            @foreach($senderIds as $sid)
            <div class="flex items-center justify-between py-2 border-b">
                <span class="text-sm font-medium text-gray-700">{{ $sid->sender_id }}</span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Active</span>
            </div>
            @endforeach
        </div>
        @else
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-md">
            <p class="text-sm text-yellow-800">
                <strong>Warning:</strong> No active sender IDs found. SMS sending may fail without a valid sender ID.
            </p>
        </div>
        @endif
    </div>

    <!-- Recent Failed Messages -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Failed Messages</h2>
        
        @php
            $failedMessages = \App\Models\Sms\SmsMessage::where('status', 'failed')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        @endphp

        @if($failedMessages->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Recipient</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($failedMessages as $msg)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $msg->created_at->format('M d, H:i') }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $msg->recipient }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ Str::limit($msg->message, 50) }}</td>
                        <td class="px-4 py-2 text-sm text-red-600">{{ $msg->error_message ?? 'Unknown error' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-sm text-gray-500">No failed messages found.</p>
        @endif
    </div>

    <!-- Test Connection -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Test Connection</h2>
        
        <form action="{{ route('admin.sms-diagnostics.test') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Test Phone Number</label>
                <input type="text" name="phone" placeholder="255712345678" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-xs text-gray-500">Enter a phone number to send a test SMS</p>
            </div>
            
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Send Test SMS
            </button>
        </form>

        @if(session('test_result'))
        <div class="mt-4 p-4 {{ session('test_result')['success'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }} border rounded-md">
            <p class="text-sm {{ session('test_result')['success'] ? 'text-green-800' : 'text-red-800' }}">
                {{ session('test_result')['message'] }}
            </p>
        </div>
        @endif
    </div>

    <!-- Troubleshooting Guide -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Troubleshooting Guide</h2>
        
        <div class="space-y-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-900">1. Check Beem Africa Credentials</h3>
                <p class="text-sm text-gray-600 mt-1">Ensure API Key and Secret Key are correctly configured in SMS Providers settings.</p>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-900">2. Verify Sender ID</h3>
                <p class="text-sm text-gray-600 mt-1">At least one sender ID must be active. Request approval from Beem Africa if needed.</p>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-900">3. Check Beem Africa Account Balance</h3>
                <p class="text-sm text-gray-600 mt-1">Ensure your Beem Africa account has sufficient SMS credits.</p>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-900">4. Review SMS Logs</h3>
                <p class="text-sm text-gray-600 mt-1">Check <code class="bg-gray-100 px-2 py-1 rounded">storage/logs/sms.log</code> for detailed error messages.</p>
            </div>
        </div>
    </div>
</div>
@endsection
