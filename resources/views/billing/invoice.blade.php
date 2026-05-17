@php
    $user = auth()->user();
    $isAdminContext = auth()->check() && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
    $layout = $isAdminContext ? 'layouts.admin' : 'layouts.user';
@endphp
@extends($layout)

@section('title', 'Invoice Details')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">Invoice {{ $invoice->number }}</h1>
        @can('manage-billing')
            <a href="{{ route('billing.invoices.edit', $invoice) }}" class="text-sm px-3 py-2 bg-blue-600 text-white rounded">Edit Invoice</a>
        @endcan
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="p-4 bg-white shadow rounded">
            <div class="text-gray-700 font-medium mb-2">Summary</div>
            <div class="text-sm text-gray-500">Plan: {{ optional($invoice->plan)->name }}</div>
            <div class="text-sm text-gray-500">Amount: TSH {{ number_format($invoice->amount) }}</div>
            <div class="text-sm text-gray-500">Status: {{ ucfirst($invoice->status) }}</div>
            <div class="text-sm text-gray-500">Due: {{ optional($invoice->due_date)->format('Y-m-d') }}</div>
        </div>

        <div class="p-4 bg-white shadow rounded">
            <div class="text-gray-700 font-medium mb-2">Pay Invoice</div>
            @if(method_exists($invoice, 'isPaid') ? !$invoice->isPaid() : ($invoice->status !== 'paid'))
            <form action="{{ route('billing.invoices.pay', $invoice) }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="provider" value="selcom" />
                <button class="px-3 py-2 bg-indigo-600 text-white rounded">Pay via Selcom</button>
            </form>
            @else
                <div class="text-green-700 bg-green-50 p-3 rounded">Invoice already paid.</div>
            @endif
        </div>
    </div>

    <div class="mt-6">
        <h2 class="text-xl font-semibold mb-2">Payments</h2>
        <div class="bg-white shadow rounded">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Reference</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Provider</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Amount</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoice->payments as $payment)
                        <tr>
                            <td class="px-4 py-2">{{ $payment->reference }}</td>
                            <td class="px-4 py-2">{{ ucfirst($payment->provider) }}</td>
                            <td class="px-4 py-2">TSH {{ number_format($payment->amount) }}</td>
                            <td class="px-4 py-2">{{ ucfirst($payment->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-center text-gray-500">No payments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection