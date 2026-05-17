@extends('layouts.app')

@section('title', 'Purchase SMS')
@section('page-title', 'Purchase SMS')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Purchase SMS</h1>
            <p class="mt-1 text-sm text-gray-500">Buy SMS credits for your Beem account</p>
        </div>
        <div>
            <a href="{{ route('messages.index') }}" class="px-4 py-2 border rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">Back to Messages</a>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">How to Purchase</h3>
        </div>
        <div class="p-6 space-y-4">
            <p class="text-sm text-gray-700">Purchasing SMS credits is handled via Beem Africa. Use the link below to access your Beem portal and complete the purchase.</p>
            <div>
                <a href="https://portal.beem.africa/" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 rounded-md text-sm text-white bg-primary-600 hover:bg-primary-700">Open Beem Portal</a>
            </div>
            <p class="text-xs text-gray-500">Ensure your Beem API credentials are configured in `.env` for sending SMS from this system.</p>
        </div>
    </div>
</div>
@endsection