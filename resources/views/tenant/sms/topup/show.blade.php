@extends('layouts.app')

@section('title', 'Top Up Details')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('sms.topup.history') }}" class="text-blue-600 hover:underline">&larr; Back to History</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Top Up Details</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Reference</h3>
                <p class="text-lg font-semibold">{{ $topup->reference ?? 'N/A' }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Status</h3>
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                    {{ $topup->status === 'completed' ? 'bg-green-100 text-green-800' : 
                       ($topup->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                    {{ ucfirst($topup->status ?? 'pending') }}
                </span>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Amount</h3>
                <p class="text-lg font-semibold">TZS {{ number_format($topup->amount ?? 0) }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Credits</h3>
                <p class="text-lg font-semibold">{{ number_format($topup->credits ?? 0) }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Date</h3>
                <p class="text-lg">{{ $topup->created_at->format('F d, Y H:i') }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Payment Method</h3>
                <p class="text-lg capitalize">{{ $topup->payment_method ?? 'N/A' }}</p>
            </div>
        </div>

        @if($topup->status === 'pending')
        <div class="mt-6 flex space-x-3">
            <form method="POST" action="{{ route('sms.topup.check-status', $topup) }}">
                @csrf
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Check Payment Status
                </button>
            </form>
            <form method="POST" action="{{ route('sms.topup.cancel', $topup) }}">
                @csrf
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700" onclick="return confirm('Are you sure you want to cancel this top up?')">
                    Cancel
                </button>
            </form>
        </div>
        @endif

        @if($topup->status === 'completed')
        <div class="mt-6">
            <a href="{{ route('sms.topup.receipt', $topup) }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                Download Receipt
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
