@extends('layouts.app')

@section('title', 'Top Up Successful')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-lg mx-auto text-center">
        <div class="bg-white rounded-lg shadow p-8">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Top Up Successful!</h1>
            <p class="text-gray-600 mb-6">Your SMS credits have been added to your account.</p>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-500">Reference:</span>
                    <span class="font-medium">{{ $topup->reference ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-500">Amount:</span>
                    <span class="font-medium">TZS {{ number_format($topup->amount ?? 0) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Credits Added:</span>
                    <span class="font-medium text-green-600">+{{ number_format($topup->credits ?? 0) }}</span>
                </div>
            </div>

            <div class="flex flex-col space-y-3">
                <a href="{{ route('sms.topup.receipt', $topup) }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    Download Receipt
                </a>
                <a href="{{ route('sms.topup.index') }}" class="text-blue-600 hover:underline">
                    Back to SMS Top Up
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
