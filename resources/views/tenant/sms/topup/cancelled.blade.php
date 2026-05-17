@extends('layouts.app')

@section('title', 'Top Up Cancelled')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-lg mx-auto text-center">
        <div class="bg-white rounded-lg shadow p-8">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Top Up Cancelled</h1>
            <p class="text-gray-600 mb-6">Your SMS top up has been cancelled. No charges have been made.</p>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-500">Reference:</span>
                    <span class="font-medium">{{ $topup->reference ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Status:</span>
                    <span class="font-medium text-red-600">Cancelled</span>
                </div>
            </div>

            <a href="{{ route('sms.topup.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 inline-block">
                Try Again
            </a>
        </div>
    </div>
</div>
@endsection
