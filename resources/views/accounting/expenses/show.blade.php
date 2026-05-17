@extends('layouts.user')

@section('title', 'Expense - ' . $expense->expense_number)

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('accounting.expenses.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Expenses</a>
        </div>

        @if(session('success'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4">
            <p class="text-green-700">{{ session('success') }}</p>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
            <p class="text-red-700">{{ session('error') }}</p>
        </div>
        @endif

        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $expense->expense_number }}</h1>
                    <p class="text-sm text-gray-500">{{ $expense->category->name ?? 'Uncategorized' }}</p>
                </div>
                <span class="px-3 py-1 text-sm font-medium rounded-full
                    @if($expense->status === 'paid') bg-green-100 text-green-800
                    @elseif($expense->status === 'approved') bg-blue-100 text-blue-800
                    @elseif($expense->status === 'pending_approval') bg-yellow-100 text-yellow-800
                    @elseif($expense->status === 'rejected') bg-red-100 text-red-800
                    @else bg-gray-100 text-gray-800
                    @endif">
                    {{ ucwords(str_replace('_', ' ', $expense->status)) }}
                </span>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Date</p>
                        <p class="font-medium">{{ $expense->expense_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Amount</p>
                        <p class="font-bold text-xl text-gray-900">{{ number_format($expense->amount, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Payee</p>
                        <p class="font-medium">{{ $expense->payee ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Expense Account</p>
                        <p class="font-medium">{{ $expense->account->account_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Payment Account</p>
                        <p class="font-medium">{{ $expense->paymentAccount->account_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Payment Method</p>
                        <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $expense->payment_method ?? '-')) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Payment Reference</p>
                        <p class="font-medium">{{ $expense->payment_reference ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Receipt Number</p>
                        <p class="font-medium">{{ $expense->receipt_number ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Branch</p>
                        <p class="font-medium">{{ $expense->branch->name ?? '-' }}</p>
                    </div>
                </div>

                <div class="mb-6">
                    <p class="text-xs text-gray-500 uppercase">Description</p>
                    <p class="text-gray-900">{{ $expense->description }}</p>
                </div>

                @if($expense->attachment)
                <div class="mb-6">
                    <p class="text-xs text-gray-500 uppercase mb-2">Attachment</p>
                    <a href="{{ Storage::url($expense->attachment) }}" target="_blank" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                        View Attachment
                    </a>
                </div>
                @endif

                <div class="border-t pt-4">
                    <p class="text-xs text-gray-500">Created by {{ $expense->createdBy->name ?? 'Unknown' }} on {{ $expense->created_at->format('M d, Y H:i') }}</p>
                    @if($expense->approved_by)
                    <p class="text-xs text-gray-500">{{ $expense->status === 'rejected' ? 'Rejected' : 'Approved' }} by {{ $expense->approvedBy->name ?? 'Unknown' }} on {{ $expense->approved_at->format('M d, Y H:i') }}</p>
                    @endif
                </div>
            </div>

            @if($expense->status === 'pending_approval')
            <div class="px-6 py-4 bg-gray-50 border-t flex space-x-3">
                <form action="{{ route('accounting.expenses.approve', $expense) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Approve & Post</button>
                </form>
                <form action="{{ route('accounting.expenses.reject', $expense) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700" onclick="return confirm('Are you sure you want to reject this expense?')">Reject</button>
                </form>
            </div>
            @endif

            @if(in_array($expense->status, ['draft', 'rejected']))
            <div class="px-6 py-4 bg-gray-50 border-t">
                <a href="{{ route('accounting.expenses.edit', $expense) }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Edit Expense</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
