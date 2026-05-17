@extends('layouts.admin')

@section('title', 'SMS Campaigns')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">SMS Campaigns</h1>
            <p class="mt-1 text-sm text-gray-500">Manage all SMS campaigns across tenants</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Total Campaigns</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_campaigns'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Active Campaigns</p>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['active_campaigns'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Total Sent</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($stats['total_sent'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Total Cost</p>
            <p class="text-2xl font-bold text-gray-900">TZS {{ number_format($stats['total_cost'] ?? 0) }}</p>
        </div>
    </div>

    <!-- Campaigns Table -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">All Campaigns</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tenant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recipients</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($campaigns as $campaign)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $campaign->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $campaign->tenant->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($campaign->total_recipients) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($campaign->sent_count) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                {{ $campaign->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $campaign->status === 'running' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $campaign->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $campaign->status === 'paused' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $campaign->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}">
                                {{ ucfirst($campaign->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $campaign->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('admin.sms-campaigns.show', $campaign) }}" class="text-blue-600 hover:text-blue-900">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            No SMS campaigns found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($campaigns->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $campaigns->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
