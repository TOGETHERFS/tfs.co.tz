@extends('layouts.app')

@section('title', 'Request Sender ID')
@section('page-title', 'Request Sender ID')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Request Sender ID</h1>
            <p class="mt-1 text-sm text-gray-500">Apply for a custom SMS Sender ID with Beem</p>
        </div>
        <div>
            <a href="{{ route('messages.index') }}" class="px-4 py-2 border rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">Back to Messages</a>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">How to Request</h3>
        </div>
        <div class="p-6 space-y-4">
            <p class="text-sm text-gray-700">Sender IDs are managed by Beem Africa. To request a new Sender ID, follow Beem’s guidelines and submit your application in their portal.</p>
            <div>
                <a href="https://developers.beem.africa/docs/sender-id" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 rounded-md text-sm text-white bg-primary-600 hover:bg-primary-700">View Beem Sender ID Guide</a>
            </div>
            <p class="text-xs text-gray-500">Once approved, set your Sender ID in the `.env` as `BEEM_SENDER_ID` or use a custom value when sending messages.</p>
        </div>
    </div>
</div>
@endsection