@extends('layouts.app')

@section('title', 'SMS Top Up')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">SMS Top Up</h1>
        <p class="text-gray-600">Purchase SMS credits for your organization</p>
    </div>

    <!-- Current Balance -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Current Balance</h3>
                <p class="text-3xl font-bold text-blue-600">{{ number_format($wallet->balance ?? 0) }} credits</p>
            </div>
            <a href="{{ route('sms.topup.history') }}" class="text-blue-600 hover:underline">View History</a>
        </div>
    </div>

    <!-- Top Up Packages -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        @foreach($packages ?? [] as $package)
        <div class="bg-white rounded-lg shadow p-6 border-2 hover:border-blue-500 transition-colors">
            <h3 class="text-lg font-semibold text-gray-900">{{ $package['name'] ?? 'Package' }}</h3>
            <p class="text-3xl font-bold text-blue-600 my-4">{{ number_format($package['credits'] ?? 0) }} <span class="text-sm font-normal text-gray-500">credits</span></p>
            <p class="text-gray-600 mb-4">TZS {{ number_format($package['price'] ?? 0) }}</p>
            <form method="POST" action="{{ route('sms.topup.create') }}">
                @csrf
                <input type="hidden" name="package" value="{{ $package['id'] ?? '' }}">
                <input type="hidden" name="amount" value="{{ $package['price'] ?? 0 }}">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Purchase
                </button>
            </form>
        </div>
        @endforeach
    </div>

    <!-- Recent Transactions -->
    @if($walletStats ?? false)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Wallet Statistics</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-gray-500">Total Purchased</p>
                <p class="text-xl font-semibold">{{ number_format($walletStats['total_purchased'] ?? 0) }} credits</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Used</p>
                <p class="text-xl font-semibold">{{ number_format($walletStats['total_used'] ?? 0) }} credits</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">This Month</p>
                <p class="text-xl font-semibold">{{ number_format($walletStats['this_month'] ?? 0) }} credits</p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
