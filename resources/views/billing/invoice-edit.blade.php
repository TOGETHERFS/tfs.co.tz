@extends('layouts.app')

@section('title', 'Edit Invoice')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">Edit Invoice {{ $invoice->number }}</h1>
        <a href="{{ route('billing.invoices.show', $invoice) }}" class="text-sm px-3 py-2 bg-gray-200 text-gray-800 rounded">Back to Invoice</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="p-4 bg-white shadow rounded">
            <div class="text-gray-700 font-medium mb-2">Current Details</div>
            <div class="text-sm text-gray-500">Plan: {{ optional($invoice->plan)->name }}</div>
            <div class="text-sm text-gray-500">Amount: TSH {{ number_format($invoice->amount) }}</div>
            <div class="text-sm text-gray-500">Status: {{ ucfirst($invoice->status) }}</div>
            <div class="text-sm text-gray-500">Due: {{ optional($invoice->due_date)->format('Y-m-d') }}</div>
            <div class="text-sm text-gray-500">Remaining: TSH {{ number_format($invoice->remaining_balance) }}</div>
        </div>

        <div class="p-4 bg-white shadow rounded">
            <form action="{{ route('billing.invoices.update', $invoice) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount (TSH)</label>
                    <input type="number" name="amount" value="{{ old('amount', $invoice->amount) }}" min="0" class="border rounded px-3 py-2 w-full" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}" class="border rounded px-3 py-2 w-full" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="border rounded px-3 py-2 w-full" required>
                        <option value="pending" {{ old('status', $invoice->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="paid" {{ old('status', $invoice->status) === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="failed" {{ old('status', $invoice->status) === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>

                <button class="px-3 py-2 bg-blue-600 text-white rounded">Save Changes</button>
            </form>
        </div>
    </div>
</div>
@endsection