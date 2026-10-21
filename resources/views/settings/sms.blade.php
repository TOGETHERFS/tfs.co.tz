@extends('layouts.app')

@section('title', 'SMS Settings')
@section('page-title', 'SMS Settings')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-semibold text-gray-900">SMS Settings</h1>
        <p class="mt-1 text-sm text-gray-500">Configure Beem Africa API credentials for sending SMS notifications to borrowers.</p>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-md bg-green-50 border border-green-200 flex items-start space-x-3">
            <svg class="h-5 w-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-md bg-red-50 border border-red-200">
            <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Current Status -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-5">
        <div class="flex items-center space-x-3">
            @if($providerSetting && $providerSetting->api_key)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Beem Africa Connected
                </span>
                <span class="text-sm text-gray-500">Sender ID: <strong class="text-gray-800">{{ $providerSetting->default_sender_id ?? 'TFS' }}</strong></span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    Not Configured
                </span>
                <span class="text-sm text-gray-500">Enter your Beem Africa API credentials below.</span>
            @endif
        </div>
    </div>

    <!-- Settings Form -->
    <form action="{{ route('settings.sms.update') }}" method="POST" class="bg-white shadow-sm rounded-lg border border-gray-200">
        @csrf
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Beem Africa API Credentials</h3>
            <p class="text-sm text-gray-500 mt-1">Get your credentials from <a href="https://apisms.beem.africa" target="_blank" class="text-blue-600 hover:underline">apisms.beem.africa</a></p>
        </div>

        <div class="p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">API Key <span class="text-red-500">*</span></label>
                <input type="text"
                       name="api_key"
                       value="{{ old('api_key', $providerSetting?->api_key ? str_repeat('*', 8) . substr($providerSetting->api_key, -4) : '') }}"
                       placeholder="Enter your Beem Africa API Key"
                       autocomplete="off"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('api_key') border-red-500 @enderror">
                @if($providerSetting?->api_key)
                    <p class="mt-1 text-xs text-gray-500">Leave unchanged to keep existing key, or type a new one to replace it.</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Secret Key <span class="text-red-500">*</span></label>
                <input type="password"
                       name="secret_key"
                       value="{{ old('secret_key', $providerSetting?->secret_key ? '••••••••' : '') }}"
                       placeholder="Enter your Beem Africa Secret Key"
                       autocomplete="new-password"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('secret_key') border-red-500 @enderror">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Default Sender ID <span class="text-red-500">*</span></label>
                <input type="text"
                       name="default_sender_id"
                       value="{{ old('default_sender_id', $providerSetting?->default_sender_id ?? 'TFS') }}"
                       maxlength="11"
                       placeholder="e.g. TFS"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('default_sender_id') border-red-500 @enderror">
                <p class="mt-1 text-xs text-gray-500">Max 11 characters. This name will appear as sender on all borrower SMS messages.</p>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg flex items-center justify-between">
            <div class="text-xs text-gray-500">
                <strong>Auto-SMS enabled for:</strong> Loan disbursement &bull; Payment received &bull; Daily overdue reminders
            </div>
            <button type="submit"
                    class="inline-flex items-center px-5 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-semibold text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save Settings
            </button>
        </div>
    </form>

    <!-- Info box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 text-sm text-blue-800 space-y-2">
        <p class="font-semibold">How automatic SMS notifications work:</p>
        <ul class="list-disc list-inside space-y-1 text-blue-700">
            <li><strong>Loan Disbursed:</strong> SMS sent to borrower immediately when loan is disbursed.</li>
            <li><strong>Payment Received:</strong> SMS sent to borrower when repayment is recorded.</li>
            <li><strong>Payment Due (daily):</strong> Reminder SMS sent every day at 8:00 AM until the overdue amount is paid.</li>
        </ul>
        <p class="text-xs text-blue-600 pt-1">SMS are sent using your Beem Africa credits. Ensure your account has sufficient balance at <a href="https://apisms.beem.africa" target="_blank" class="underline">apisms.beem.africa</a>.</p>
    </div>

</div>
@endsection
