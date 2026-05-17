@extends('layouts.app')

@section('title', 'Sender IDs')
@section('page-title', 'Sender IDs')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Sender IDs</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your SMS sender identities</p>
        </div>
        <a href="{{ route('messages.request-sender-id') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Request New Sender ID
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-md bg-green-50 border border-green-200">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-md bg-red-50 border border-red-200">
            <p class="text-sm text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Status Workflow Guide -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Sender ID Approval Process</h3>
        <div class="flex items-center justify-between">
            <div class="flex items-center flex-1">
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                        <span class="text-yellow-600 font-bold">1</span>
                    </div>
                    <span class="mt-2 text-xs font-medium text-yellow-700">Pending</span>
                    <span class="text-xs text-gray-500">Under Review</span>
                </div>
                <div class="flex-1 h-1 bg-gray-200 mx-2"></div>
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <span class="text-blue-600 font-bold">2</span>
                    </div>
                    <span class="mt-2 text-xs font-medium text-blue-700">Approved</span>
                    <span class="text-xs text-gray-500">Admin Approved</span>
                </div>
                <div class="flex-1 h-1 bg-gray-200 mx-2"></div>
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                        <span class="text-green-600 font-bold">3</span>
                    </div>
                    <span class="mt-2 text-xs font-medium text-green-700">Live</span>
                    <span class="text-xs text-gray-500">Ready to Use</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Sender IDs -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Active Sender IDs</h3>
        </div>
        <div class="p-6">
            @if($activeSenderIds->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($activeSenderIds as $senderId)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border {{ $senderId->is_default ? 'border-blue-300 bg-blue-50' : 'border-gray-200' }}">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $senderId->sender_id }}</p>
                                @if($senderId->is_default)
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-blue-100 text-blue-800">Default</span>
                                @endif
                            </div>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Live
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">No active Sender IDs</p>
                    <p class="text-sm text-gray-400">Request a Sender ID to start sending SMS</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Sender ID Requests -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Request History</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sender ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($requests as $request)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $request->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $request->sender_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $request->company_name ?? $request->business_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $request->status_badge_class ?? ($request->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($request->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')) }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $request->admin_notes ?? $request->rejection_reason ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @if(in_array($request->status, ['pending', 'rejected']))
                                    <a href="{{ route('messages.edit-sender-id', $request->id) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                No sender ID requests yet
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
