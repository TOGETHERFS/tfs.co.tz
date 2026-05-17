@extends('layouts.app')

@section('title', 'Complete Payment')
@section('page-title', 'Complete Payment')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Complete Your SMS Purchase</h2>
            <p class="text-sm text-gray-500">Order #{{ $purchaseRequest->selcom_order_id }}</p>
        </div>
        
        <div class="p-6 space-y-6">
            <!-- Package Details -->
            <div class="bg-blue-50 rounded-lg p-4">
                <h3 class="font-medium text-blue-900">{{ $package->name }}</h3>
                @if($package->description)
                    <p class="text-sm text-blue-700 mt-1">{{ $package->description }}</p>
                @endif
                <div class="mt-3 flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-blue-900">{{ number_format($package->sms_count) }}</p>
                        <p class="text-sm text-blue-600">SMS Credits</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-blue-900">{{ number_format($package->price) }} {{ $package->currency ?? 'TZS' }}</p>
                        <p class="text-xs text-blue-600">{{ number_format($package->price_per_sms, 2) }} per SMS</p>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <form action="{{ route('messages.purchase') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="package_id" value="{{ $package->id }}">
                
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number (for M-Pesa/Tigo Pesa)</label>
                    <input type="tel" name="phone_number" id="phone_number" 
                           value="{{ old('phone_number', $tenant->phone ?? '') }}"
                           placeholder="e.g., 0712345678"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Enter your mobile money number for payment</p>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email (for receipt)</label>
                    <input type="email" name="email" id="email" 
                           value="{{ old('email', $tenant->contact_email ?? '') }}"
                           placeholder="your@email.com"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-md p-4">
                        <ul class="text-sm text-red-700 list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="pt-4 border-t border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-gray-600">Total Amount:</span>
                        <span class="text-xl font-bold text-gray-900">{{ number_format($package->price) }} {{ $package->currency ?? 'TZS' }}</span>
                    </div>
                    
                    <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                        <span class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Proceed to Payment
                        </span>
                    </button>
                </div>
            </form>

            <div class="text-center">
                <a href="{{ route('messages.packages') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    ← Back to packages
                </a>
            </div>
        </div>
    </div>

    <!-- Payment Methods Info -->
    <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="font-medium text-gray-900 mb-4">Accepted Payment Methods</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="font-medium text-gray-700">M-Pesa</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="font-medium text-gray-700">Tigo Pesa</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="font-medium text-gray-700">Airtel Money</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="font-medium text-gray-700">Halopesa</p>
            </div>
        </div>
        <p class="mt-4 text-xs text-gray-500 text-center">
            Payments are processed securely through Selcom Payment Gateway
        </p>
    </div>
</div>
@endsection
