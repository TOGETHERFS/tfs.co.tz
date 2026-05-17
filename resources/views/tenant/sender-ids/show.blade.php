@extends('layouts.app')

@section('title', 'Sender ID Details')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('sender-ids.index') }}" class="text-blue-600 hover:underline">&larr; Back to Sender IDs</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Sender ID: {{ $senderId->sender_id }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Sender ID</h3>
                <p class="text-lg font-semibold">{{ $senderId->sender_id }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Status</h3>
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                    {{ $senderId->status === 'approved' ? 'bg-green-100 text-green-800' : 
                       ($senderId->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                    {{ ucfirst($senderId->status) }}
                </span>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Created</h3>
                <p class="text-lg">{{ $senderId->created_at->format('F d, Y H:i') }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Type</h3>
                <p class="text-lg capitalize">{{ $senderId->type ?? 'Standard' }}</p>
            </div>
        </div>

        @if($senderId->purpose)
        <div class="mt-6">
            <h3 class="text-sm font-medium text-gray-500">Purpose</h3>
            <p class="mt-1 text-gray-900">{{ $senderId->purpose }}</p>
        </div>
        @endif

        @if($senderId->rejection_reason)
        <div class="mt-6 p-4 bg-red-50 rounded-md">
            <h3 class="text-sm font-medium text-red-800">Rejection Reason</h3>
            <p class="mt-1 text-red-700">{{ $senderId->rejection_reason }}</p>
        </div>
        @endif

        @if($senderId->status === 'pending' || $senderId->status === 'rejected')
        <div class="mt-6 flex space-x-3">
            <a href="{{ route('sender-ids.edit', $senderId) }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Edit Application
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
