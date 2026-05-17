@extends('layouts.app')

@section('title', 'SMS Packages')
@section('page-title', 'SMS Packages')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">SMS Packages</h1>
            <p class="mt-1 text-sm text-gray-500">Purchase SMS credits for your messaging needs</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-500">Current Balance</p>
            <p class="text-3xl font-bold text-blue-600">{{ number_format($balance->balance ?? 0) }} SMS</p>
        </div>
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

    <!-- Available Packages -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($packages as $package)
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $package->name }}</h3>
                    @if($package->description)
                        <p class="mt-1 text-sm text-gray-500">{{ $package->description }}</p>
                    @endif
                    
                    <div class="mt-4">
                        <p class="text-4xl font-bold text-gray-900">{{ number_format($package->sms_count) }}</p>
                        <p class="text-sm text-gray-500">SMS Credits</p>
                    </div>
                    
                    <div class="mt-4">
                        <p class="text-2xl font-semibold text-blue-600">{{ $package->formatted_price }}</p>
                        <p class="text-xs text-gray-500">{{ number_format($package->price_per_sms, 2) }} {{ $package->currency }} per SMS</p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <form action="{{ route('messages.purchase') }}" method="POST">
                        @csrf
                        <input type="hidden" name="package_id" value="{{ $package->id }}">
                        <button type="submit" class="w-full px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Buy Now
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12 bg-white rounded-lg border border-gray-200">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500">No SMS packages available at the moment</p>
            </div>
        @endforelse
    </div>

    <!-- Transaction History -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Transaction History</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transactions as $txn)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $txn->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $txn->type_badge_class }}">
                                    {{ $txn->type_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $txn->description }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ $txn->amount > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $txn->amount > 0 ? '+' : '' }}{{ number_format($txn->amount) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                {{ number_format($txn->balance_after) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                No transactions yet
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
