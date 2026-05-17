@extends('layouts.admin')

@section('title', 'Campaign Details')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $smsCampaign->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">Campaign ID: {{ $smsCampaign->id }}</p>
        </div>
        <a href="{{ route('admin.sms-campaigns.index') }}" class="text-gray-600 hover:text-gray-900">Back to Campaigns</a>
    </div>

    <!-- Campaign Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Total Recipients</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($smsCampaign->total_recipients) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Sent</p>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($smsCampaign->sent_count) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Delivered</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($smsCampaign->delivered_count) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Failed</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($smsCampaign->failed_count) }}</p>
        </div>
    </div>

    <!-- Campaign Details -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Campaign Details</h3>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm text-gray-500">Status</dt>
                <dd class="mt-1">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                        {{ $smsCampaign->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $smsCampaign->status === 'running' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $smsCampaign->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ ucfirst($smsCampaign->status) }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Tenant</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $smsCampaign->tenant->name ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Created At</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $smsCampaign->created_at->format('M d, Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Actual Cost</dt>
                <dd class="mt-1 text-sm text-gray-900">TZS {{ number_format($smsCampaign->actual_cost ?? 0) }}</dd>
            </div>
        </dl>
    </div>

    <!-- Message Preview -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Message</h3>
        <div class="bg-gray-50 rounded p-4">
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $smsCampaign->message }}</p>
        </div>
    </div>
</div>
@endsection
