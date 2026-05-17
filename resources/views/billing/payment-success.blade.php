@extends('layouts.app')

@section('title', 'Payment Processing')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-white shadow rounded-lg p-8 text-center">
        <div class="mb-6">
            <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Received!</h1>
            <p class="text-gray-600">{{ $message }}</p>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment Details</h3>
            <div class="space-y-2 text-left">
                <div class="flex justify-between">
                    <span class="text-gray-600">Reference:</span>
                    <span class="font-mono text-sm">{{ $payment->reference }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Amount:</span>
                    <span class="font-semibold">TZS {{ number_format($payment->amount) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Status:</span>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                        {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ ucfirst($payment->status) }}
                    </span>
                </div>
            </div>
        </div>

        @if($payment->status === 'completed')
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-800 font-medium">🎉 Your subscription is now active!</p>
                <p class="text-green-700 text-sm mt-1">You can now access all features of your plan.</p>
            </div>
        @else
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-yellow-800 font-medium">⏳ Payment is being processed</p>
                <p class="text-yellow-700 text-sm mt-1">Your subscription will be activated automatically once payment is confirmed.</p>
            </div>
        @endif

        <div class="space-y-3">
            <a href="{{ route('user.dashboard') }}" 
               class="w-full inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Return to Dashboard
            </a>
            
            <a href="{{ route('billing.invoices') }}" 
               class="w-full inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                View Invoices
            </a>
        </div>
    </div>
</div>
@endsection