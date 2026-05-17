@extends('layouts.app')

@section('title', 'Tenant Dashboard')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600">Welcome back! Here's an overview of your organization.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Total Clients</h3>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['total_clients'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Active Loans</h3>
            <p class="text-2xl font-bold text-green-600">{{ number_format($stats['active_loans'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Total Disbursed</h3>
            <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['total_disbursed'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Total Repaid</h3>
            <p class="text-2xl font-bold text-orange-600">{{ number_format($stats['total_repaid'] ?? 0, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Loans -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Recent Loans</h3>
            </div>
            <div class="p-6">
                @if($recentLoans && $recentLoans->count() > 0)
                <div class="space-y-4">
                    @foreach($recentLoans as $loan)
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-medium text-gray-900">{{ $loan->client->name ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-500">{{ $loan->loan_number ?? 'N/A' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium">{{ number_format($loan->principal, 2) }}</p>
                            <span class="text-xs px-2 py-1 rounded-full {{ $loan->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($loan->status) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500 text-center py-4">No recent loans</p>
                @endif
            </div>
        </div>

        <!-- Recent Repayments -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Recent Repayments</h3>
            </div>
            <div class="p-6">
                @if($recentRepayments && $recentRepayments->count() > 0)
                <div class="space-y-4">
                    @foreach($recentRepayments as $repayment)
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-medium text-gray-900">{{ $repayment->loan->client->name ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-500">{{ $repayment->payment_date ? $repayment->payment_date->format('M d, Y') : 'N/A' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-green-600">+{{ number_format($repayment->amount, 2) }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500 text-center py-4">No recent repayments</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Subscription Info -->
    @if($subscription)
    <div class="mt-6 bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Subscription Status</h3>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600">Current Plan: <span class="font-semibold">{{ $subscription->plan->name ?? 'N/A' }}</span></p>
                <p class="text-sm text-gray-500">Expires: {{ $subscription->current_period_end ? $subscription->current_period_end->format('M d, Y') : 'N/A' }}</p>
            </div>
            <a href="{{ route('billing.subscription') }}" class="text-blue-600 hover:underline">Manage Subscription</a>
        </div>
    </div>
    @endif
</div>
@endsection
