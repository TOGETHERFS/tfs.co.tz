@extends('layouts.app')

@section('title', 'Message History')
@section('page-title', 'Message History')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Message History</h1>
            <p class="mt-1 text-sm text-gray-500">View all sent SMS messages</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-500">SMS Balance</p>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($balance->balance ?? 0) }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Status</option>
                    <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>Queued</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                <select name="type" class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Types</option>
                    <option value="single" {{ request('type') === 'single' ? 'selected' : '' }}>Manual (Single)</option>
                    <option value="bulk" {{ request('type') === 'bulk' ? 'selected' : '' }}>Manual (Bulk)</option>
                    <option value="loan_disbursed" {{ request('type') === 'loan_disbursed' ? 'selected' : '' }}>Loan Disbursed</option>
                    <option value="repayment_confirmation" {{ request('type') === 'repayment_confirmation' ? 'selected' : '' }}>Repayment Confirmation</option>
                    <option value="subscription_payment" {{ request('type') === 'subscription_payment' ? 'selected' : '' }}>Subscription Payment</option>
                    <option value="sms_package_activated" {{ request('type') === 'sms_package_activated' ? 'selected' : '' }}>SMS Package Activated</option>
                    <option value="welcome" {{ request('type') === 'welcome' ? 'selected' : '' }}>Welcome (Registration)</option>
                    <option value="notification" {{ request('type') === 'notification' ? 'selected' : '' }}>Notification</option>
                    <option value="reminder" {{ request('type') === 'reminder' ? 'selected' : '' }}>Reminder</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Phone or message..."
                       class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                Filter
            </button>
            <a href="{{ route('messages.history') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">
                Clear
            </a>
        </form>
    </div>

    <!-- Messages Table -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SMS</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent By</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($messages as $msg)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $msg->created_at->format('M d, Y') }}<br>
                                <span class="text-gray-500">{{ $msg->created_at->format('H:i') }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <p class="text-gray-900">{{ $msg->recipient }}</p>
                                @if($msg->client)
                                    <p class="text-gray-500 text-xs">{{ $msg->client->first_name }} {{ $msg->client->last_name }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                                <p class="truncate" title="{{ $msg->message }}">{{ Str::limit($msg->message, 60) }}</p>
                                @if($msg->failure_reason)
                                    <p class="text-red-500 text-xs mt-1">{{ $msg->failure_reason }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $msg->sms_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ ucfirst($msg->message_type) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $msg->status_badge_class }}">
                                    {{ $msg->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $msg->user->name ?? 'System' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                                <p class="mt-2">No messages found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($messages->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $messages->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
