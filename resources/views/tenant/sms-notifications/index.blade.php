@extends('layouts.app')

@section('title', 'SMS Notifications')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">SMS Notification Settings</h1>
        <p class="text-gray-600">Configure automated SMS notifications for your organization</p>
    </div>

    @if($wallet)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">SMS Balance</h3>
                <p class="text-3xl font-bold text-blue-600">{{ number_format($wallet->balance ?? 0) }} credits</p>
            </div>
            <a href="{{ route('sms.topup.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Top Up
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Notification Preferences</h3>
        
        <form method="POST" action="{{ route('sms-notifications.update') }}">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                <div class="flex items-center justify-between py-3 border-b">
                    <div>
                        <h4 class="font-medium text-gray-900">Loan Disbursement</h4>
                        <p class="text-sm text-gray-500">Notify clients when loans are disbursed</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="loan_disbursement" value="1" class="sr-only peer" {{ ($wallet->loan_disbursement_sms ?? false) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between py-3 border-b">
                    <div>
                        <h4 class="font-medium text-gray-900">Payment Reminders</h4>
                        <p class="text-sm text-gray-500">Send reminders before payment due dates</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="payment_reminder" value="1" class="sr-only peer" {{ ($wallet->payment_reminder_sms ?? false) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between py-3 border-b">
                    <div>
                        <h4 class="font-medium text-gray-900">Payment Received</h4>
                        <p class="text-sm text-gray-500">Confirm when payments are received</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="payment_received" value="1" class="sr-only peer" {{ ($wallet->payment_received_sms ?? false) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between py-3">
                    <div>
                        <h4 class="font-medium text-gray-900">Overdue Alerts</h4>
                        <p class="text-sm text-gray-500">Alert clients about overdue payments</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="overdue_alert" value="1" class="sr-only peer" {{ ($wallet->overdue_alert_sms ?? false) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Save Preferences
                </button>
            </div>
        </form>
    </div>
    @else
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
        <p class="text-yellow-800">SMS wallet not found. Please contact support to set up your SMS wallet.</p>
    </div>
    @endif
</div>
@endsection
