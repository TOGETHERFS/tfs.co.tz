@extends('layouts.user')

@section('title', 'Journal Entry - ' . $journalEntry->entry_number)

@section('content')
<div class="py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('accounting.journal-entries.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                ← Back to Journal Entries
            </a>
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

        <!-- Header -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $journalEntry->entry_number }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ ucwords(str_replace('_', ' ', $journalEntry->entry_type)) }}</p>
                </div>
                <span class="px-3 py-1 text-sm font-medium rounded-full
                    @if($journalEntry->status === 'posted') bg-green-100 text-green-800
                    @elseif($journalEntry->status === 'approved') bg-blue-100 text-blue-800
                    @elseif($journalEntry->status === 'pending_approval') bg-yellow-100 text-yellow-800
                    @elseif($journalEntry->status === 'rejected') bg-red-100 text-red-800
                    @else bg-gray-100 text-gray-800
                    @endif">
                    {{ ucwords(str_replace('_', ' ', $journalEntry->status)) }}
                </span>
            </div>

            <div class="px-6 py-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Entry Date</p>
                        <p class="font-medium">{{ $journalEntry->entry_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Created By</p>
                        <p class="font-medium">{{ $journalEntry->createdBy->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Fiscal Year</p>
                        <p class="font-medium">{{ $journalEntry->fiscalYear->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Period</p>
                        <p class="font-medium">{{ $journalEntry->period->name ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="mb-4">
                    <p class="text-xs text-gray-500 uppercase">Description</p>
                    <p class="text-gray-900">{{ $journalEntry->description }}</p>
                </div>

                @if($journalEntry->rejection_reason)
                <div class="bg-red-50 border border-red-200 rounded-md p-3 mb-4">
                    <p class="text-xs text-red-600 uppercase font-medium">Rejection Reason</p>
                    <p class="text-red-800">{{ $journalEntry->rejection_reason }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Entry Lines -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Entry Lines</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($journalEntry->lines as $line)
                        <tr>
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-900">{{ $line->account->account_code }}</p>
                                <p class="text-sm text-gray-500">{{ $line->account->account_name }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $line->description ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium {{ $line->debit_amount > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                {{ $line->debit_amount > 0 ? number_format($line->debit_amount, 2) : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium {{ $line->credit_amount > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                {{ $line->credit_amount > 0 ? number_format($line->credit_amount, 2) : '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="2" class="px-6 py-3 text-right font-medium text-gray-900">Totals:</td>
                            <td class="px-6 py-3 text-right font-bold text-gray-900">{{ number_format($journalEntry->total_debit, 2) }}</td>
                            <td class="px-6 py-3 text-right font-bold text-gray-900">{{ number_format($journalEntry->total_credit, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>
            <div class="flex flex-wrap gap-3">
                @if($journalEntry->status === 'pending_approval')
                    <form action="{{ route('accounting.journal-entries.approve', $journalEntry) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Approve
                        </button>
                    </form>
                    
                    <button type="button" onclick="document.getElementById('reject-modal').classList.remove('hidden')"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Reject
                    </button>
                @endif

                @if($journalEntry->status === 'approved')
                    <form action="{{ route('accounting.journal-entries.post', $journalEntry) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Post to Ledger
                        </button>
                    </form>
                @endif

                @if($journalEntry->status === 'posted')
                    <form action="{{ route('accounting.journal-entries.reverse', $journalEntry) }}" method="POST" class="inline"
                        onsubmit="return confirm('Are you sure you want to reverse this entry?')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">
                            Reverse Entry
                        </button>
                    </form>
                @endif

                @if(in_array($journalEntry->status, ['draft', 'rejected']))
                    <a href="{{ route('accounting.journal-entries.edit', $journalEntry) }}"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Edit Entry
                    </a>
                @endif
            </div>
        </div>

        <!-- Rejection Modal -->
        <div id="reject-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Journal Entry</h3>
                <form action="{{ route('accounting.journal-entries.reject', $journalEntry) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="reason" class="block text-sm font-medium text-gray-700">Rejection Reason *</label>
                        <textarea name="reason" id="reason" rows="3" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('reject-modal').classList.add('hidden')"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
