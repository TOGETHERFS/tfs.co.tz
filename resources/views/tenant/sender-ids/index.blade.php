@extends('layouts.app')

@section('title', 'Sender IDs')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Sender IDs</h1>
            <p class="text-gray-600">Manage your SMS sender IDs</p>
        </div>
        <a href="{{ route('sender-ids.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            Apply for Sender ID
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Total Applications</h3>
            <p class="text-2xl font-bold text-blue-600">{{ $stats['total_applications'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Pending</h3>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending_applications'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Active</h3>
            <p class="text-2xl font-bold text-green-600">{{ $stats['active_sender_ids'] ?? 0 }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sender ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($senderIds as $senderId)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $senderId->sender_id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ $senderId->type ?? 'Standard' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $senderId->status === 'approved' ? 'bg-green-100 text-green-800' : 
                               ($senderId->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ ucfirst($senderId->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $senderId->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                        <a href="{{ route('sender-ids.show', $senderId) }}" class="text-blue-600 hover:text-blue-900">View</a>
                        @if(in_array($senderId->status, ['pending', 'rejected']))
                        <a href="{{ route('sender-ids.edit', $senderId) }}" class="text-green-600 hover:text-green-900">Edit</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No sender IDs found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($senderIds->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $senderIds->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
