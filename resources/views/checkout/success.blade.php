@extends('layouts.app')

@section('title', 'Payment Successful - PHIDLMS')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center">
        <!-- Success Icon -->
        <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100 mb-8">
            <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <!-- Success Message -->
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Successful!</h1>
        <p class="text-xl text-gray-600 mb-8">
            Thank you for subscribing to PHIDLMS. Your account has been upgraded successfully.
        </p>

        <!-- Plan Details -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8 text-left">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Subscription Details</h2>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Plan:</span>
                    <span class="font-medium">{{ $plan['name'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Amount Paid:</span>
                    <span class="font-medium">TZS {{ number_format($plan['price']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Billing Period:</span>
                    <span class="font-medium">Monthly</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Staff Limit:</span>
                    <span class="font-medium">{{ $plan['max_staff'] }} staff</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Next Billing Date:</span>
                    <span class="font-medium">{{ $nextBilling->format('M j, Y') }}</span>
                </div>
                @if(isset($transactionId))
                <div class="flex justify-between">
                    <span class="text-gray-600">Transaction ID:</span>
                    <span class="font-medium font-mono text-sm">{{ $transactionId }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- What's Next -->
        <div class="bg-blue-50 rounded-lg p-6 mb-8 text-left">
            <h3 class="text-lg font-semibold text-blue-900 mb-4">What's Next?</h3>
            <ul class="space-y-2 text-blue-800">
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Your account has been automatically upgraded to the {{ $plan['name'] }} plan
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    You can now add up to {{ $plan['max_staff'] }} staff member{{ $plan['max_staff'] > 1 ? 's' : '' }} (+TZS {{ number_format($plan['price_per_staff'] ?? 0) }}/month for extra staff)
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    A confirmation email has been sent to your registered email address
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Your subscription will automatically renew on {{ $nextBilling->format('M j, Y') }}
                </li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('dashboard') }}" 
               class="bg-blue-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-blue-700 transition-colors">
                Go to Dashboard
            </a>
            <a href="{{ route('branches.index') }}" 
               class="bg-gray-100 text-gray-900 px-8 py-3 rounded-lg text-lg font-semibold hover:bg-gray-200 transition-colors">
                Manage Branches
            </a>
        </div>

        <!-- Support -->
        <div class="mt-12 text-center">
            <p class="text-gray-600 mb-4">Need help getting started?</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#" class="text-blue-600 hover:underline">View Documentation</a>
                <a href="#" class="text-blue-600 hover:underline">Contact Support</a>
                <a href="#" class="text-blue-600 hover:underline">Watch Tutorial Videos</a>
            </div>
        </div>
    </div>
</div>
@endsection