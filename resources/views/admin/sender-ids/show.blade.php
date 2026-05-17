@extends('layouts.admin')

@section('title', 'Sender ID Details')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Sender ID Details</h1>
                    <p class="mt-1 text-sm text-gray-500">View and manage sender ID application</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.sender-ids.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to List
                    </a>
                </div>
            </div>
        </div>
        @if(isset($senderId))
        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Sender ID Information Card -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Sender ID Information</h3>
                    </div>
                    <div class="px-6 py-4">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Sender ID</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <span class="inline-flex items-center px-3 py-1 rounded-md text-sm font-mono bg-blue-100 text-blue-800">
                                        {{ $senderId->sender_id }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    @if($senderId->status == 'approved' || $senderId->status == 'live')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ ucfirst($senderId->status) }}
                                        </span>
                                    @elseif($senderId->status == 'pending')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                            </svg>
                                            Pending
                                        </span>
                                    @elseif($senderId->status == 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            </svg>
                                            Rejected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ ucfirst($senderId->status ?? 'Unknown') }}
                                        </span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Tenant</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $senderId->tenant->name ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Company Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $senderId->company_name ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Requested Date</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $senderId->created_at ? $senderId->created_at->format('d M Y H:i') : 'N/A' }}</dd>
                            </div>
                            @if($senderId->approved_at)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Approved Date</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($senderId->approved_at)->format('d M Y H:i') }}</dd>
                            </div>
                            @endif
                            @if($senderId->rejected_at)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Rejected Date</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($senderId->rejected_at)->format('d M Y H:i') }}</dd>
                            </div>
                            @endif
                            @if($senderId->purpose)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Purpose</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $senderId->purpose }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                @if($senderId->rejection_reason)
                <!-- Rejection Reason -->
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Rejection Reason</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>{{ $senderId->rejection_reason }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column - Stats & Actions -->
            <div class="space-y-6">
                <!-- Usage Statistics Card -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Usage Statistics</h3>
                    </div>
                    <div class="px-6 py-4">
                        @if(isset($stats))
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Total Messages Sent</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($stats['total_sms_sent'] ?? 0) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Total Campaigns</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($stats['total_campaigns'] ?? 0) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Last Used</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $stats['last_used'] ?? 'Never' }}</dd>
                            </div>
                        </dl>
                        @else
                        <p class="text-sm text-gray-500">No usage statistics available</p>
                        @endif
                    </div>
                </div>

                <!-- Actions Card -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Actions</h3>
                    </div>
                    <div class="px-6 py-4 space-y-3">
                        @if($senderId->status == 'pending')
                        <form action="{{ route('admin.sender-ids.approve', $senderId->id) }}" method="POST">
                            @csrf
                            <button type="submit" onclick="return confirm('Are you sure you want to approve this Sender ID?')" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Approve Sender ID
                            </button>
                        </form>
                        <form action="{{ route('admin.sender-ids.reject', $senderId->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to reject this Sender ID?')">
                            @csrf
                            <input type="hidden" name="rejection_reason" value="Application rejected by admin">
                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Reject Sender ID
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @else
        <!-- Not Found State -->
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Sender ID Not Found</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>The requested Sender ID could not be found.</p>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('admin.sender-ids.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700">
                            Back to Sender IDs
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection