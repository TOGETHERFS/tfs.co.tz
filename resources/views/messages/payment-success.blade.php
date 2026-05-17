@extends('layouts.app')

@section('title', 'Payment Successful')
@section('page-title', 'Payment Successful')

@section('content')
<div class="max-w-lg mx-auto text-center">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Payment Successful!</h2>
        <p class="text-gray-600 mb-6">Your SMS credits have been added to your account.</p>
        
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="text-left">
                    <p class="text-gray-500">Package</p>
                    <p class="font-medium text-gray-900">{{ $package->name }}</p>
                </div>
                <div class="text-right">
                    <p class="text-gray-500">SMS Credits</p>
                    <p class="font-medium text-green-600">+{{ number_format($purchaseRequest->sms_count) }}</p>
                </div>
                <div class="text-left">
                    <p class="text-gray-500">Amount Paid</p>
                    <p class="font-medium text-gray-900">{{ number_format($purchaseRequest->amount) }} {{ $purchaseRequest->currency }}</p>
                </div>
                <div class="text-right">
                    <p class="text-gray-500">Reference</p>
                    <p class="font-medium text-gray-900 text-xs">{{ $purchaseRequest->selcom_transaction_id ?? $purchaseRequest->selcom_order_id }}</p>
                </div>
            </div>
        </div>
        
        <div class="space-y-3">
            <a href="{{ route('messages.compose') }}" class="block w-full px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                Send SMS Now
            </a>
            <a href="{{ route('messages.packages') }}" class="block w-full px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                View Balance & Packages
            </a>
        </div>
    </div>
</div>
@endsection
