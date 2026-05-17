@extends('layouts.app')

@section('title', $loanProduct->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('loan-products.index') }}" class="text-blue-600 hover:underline">&larr; {{ __('messages.back_to_loan_products') }}</a>
        <div class="flex justify-between items-center mt-2">
            <h1 class="text-2xl font-bold text-gray-900">{{ $loanProduct->name }}</h1>
            <a href="{{ route('loan-products.edit', $loanProduct) }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                {{ __('messages.edit_product') }}
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">{{ __('messages.total_loans') }}</h3>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['total_loans'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">{{ __('messages.active_loans') }}</h3>
            <p class="text-2xl font-bold text-green-600">{{ number_format($stats['active_loans'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">{{ __('messages.total_disbursed') }}</h3>
            <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['total_disbursed'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">{{ __('messages.outstanding') }}</h3>
            <p class="text-2xl font-bold text-orange-600">{{ number_format($stats['outstanding'] ?? 0, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Product Details -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('messages.product_details') }}</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">{{ __('messages.interest_rate') }}</span>
                    <span class="font-medium">{{ $loanProduct->interest_rate }}%</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">{{ __('messages.interest_type') }}</span>
                    <span class="font-medium capitalize">{{ $loanProduct->interest_type ?? 'Flat' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">{{ __('messages.amount_range') }}</span>
                    <span class="font-medium">{{ number_format($loanProduct->min_amount ?? 0) }} - {{ number_format($loanProduct->max_amount ?? 0) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">{{ __('messages.term_range') }}</span>
                    <span class="font-medium">{{ $loanProduct->min_term ?? 1 }} - {{ $loanProduct->max_term ?? 12 }} months</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">{{ __('messages.processing_fee') }}</span>
                    <span class="font-medium">{{ $loanProduct->processing_fee ?? 0 }}%</span>
                </div>
            </div>
            @if($loanProduct->description)
            <div class="mt-4 pt-4 border-t">
                <h4 class="text-sm font-medium text-gray-700 mb-2">{{ __('messages.description') }}</h4>
                <p class="text-gray-600">{{ $loanProduct->description }}</p>
            </div>
            @endif
        </div>

        <!-- Recent Loans -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('messages.recent_loans') }}</h3>
            @if($recentLoans && $recentLoans->count() > 0)
            <div class="space-y-3">
                @foreach($recentLoans as $loan)
                <div class="flex justify-between items-center py-2 border-b last:border-0">
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
            <p class="text-gray-500 text-center py-4">{{ __('messages.no_loans_yet') }}</p>
            @endif
        </div>
    </div>
</div>
@endsection
