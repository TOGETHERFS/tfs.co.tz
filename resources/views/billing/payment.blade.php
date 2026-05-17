@extends('layouts.app')

@section('title', 'Payment Details')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('billing.payments') }}" class="text-blue-600 hover:underline">&larr; Back to Payments</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Payment Details</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Reference</h3>
                <p class="text-lg font-semibold">{{ $payment->reference ?? 'N/A' }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Status</h3>
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                    {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' : 
                       ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                    {{ ucfirst($payment->status) }}
                </span>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Amount</h3>
                <p class="text-lg font-semibold">{{ number_format($payment->amount, 2) }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Payment Method</h3>
                <p class="text-lg">{{ ucfirst($payment->payment_method ?? 'N/A') }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Date</h3>
                <p class="text-lg">{{ $payment->created_at->format('F d, Y H:i') }}</p>
            </div>
            @if($payment->invoice)
            <div>
                <h3 class="text-sm font-medium text-gray-500">Invoice</h3>
                <a href="{{ route('billing.invoices.show', $payment->invoice) }}" class="text-blue-600 hover:underline">
                    {{ $payment->invoice->invoice_number ?? 'View Invoice' }}
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
