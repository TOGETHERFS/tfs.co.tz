@extends('layouts.user')

@section('title', __('messages.add_repayment'))

@section('content')
<div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.add_repayment') }}</h1>
            <p class="text-sm text-gray-600">{{ __('messages.record_repayment_desc') }}</p>
        </div>
        <a href="{{ route('repayments.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
            {{ __('messages.back_to_repayments') }}
        </a>
    </div>

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($loan)
        <!-- Loan Information Card -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('messages.loan_info') }}</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">{{ __('messages.borrower') }}</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $loan->client->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ __('messages.loan_id') }}</p>
                        <p class="text-lg font-semibold text-gray-900">#{{ $loan->id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ __('messages.principal_amount') }}</p>
                        <p class="text-lg font-semibold text-gray-900">TZS {{ number_format($loan->principal_amount, 0) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ __('messages.outstanding_balance') }}</p>
                        <p class="text-lg font-semibold text-red-600">TZS {{ number_format($loan->outstanding_balance, 0) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Repayment Schedule -->
        @if($schedules && count($schedules) > 0)
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('messages.repayment_schedule') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.due_date') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.amount') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.loan_status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($schedules as $schedule)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $schedule->due_date }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">TZS {{ number_format($schedule->total_amount, 0) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($schedule->status == 'paid') bg-green-100 text-green-800
                                        @elseif($schedule->status == 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($schedule->status == 'overdue') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($schedule->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Payment Form -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('messages.payment_information') }}</h2>
            </div>
            <div class="p-6">
                <form action="{{ route('repayments.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700">{{ __('messages.payment_amount_label') }}</label>
                            <input type="number" step="0.01" id="amount" name="amount" value="{{ old('amount') }}" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('amount') border-red-500 @enderror"
                                   placeholder="Enter amount" required>
                            @error('amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="payment_date" class="block text-sm font-medium text-gray-700">{{ __('messages.payment_date_label') }}</label>
                            <input type="date" id="payment_date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('payment_date') border-red-500 @enderror"
                                   required>
                            @error('payment_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="payment_method" class="block text-sm font-medium text-gray-700">{{ __('messages.payment_method_label') }}</label>
                            <select id="payment_method" name="payment_method" 
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('payment_method') border-red-500 @enderror"
                                    required>
                                <option value="">{{ __('messages.select_payment_method') }}</option>
                                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>{{ __('messages.cash') }}</option>
                                <option value="mobile_money" {{ old('payment_method') == 'mobile_money' ? 'selected' : '' }}>{{ __('messages.mobile_money') }}</option>
                                <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>{{ __('messages.bank_transfer') }}</option>
                                <option value="cheque" {{ old('payment_method') == 'cheque' ? 'selected' : '' }}>{{ __('messages.cheque') }}</option>
                            </select>
                            @error('payment_method')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="reference_number" class="block text-sm font-medium text-gray-700">{{ __('messages.reference_number') }}</label>
                            <input type="text" id="reference_number" name="reference_number" value="{{ old('reference_number') }}" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Transaction reference (optional)">
                            @error('reference_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6">
                        <label for="notes" class="block text-sm font-medium text-gray-700">{{ __('messages.notes') }}</label>
                        <textarea id="notes" name="notes" rows="3" 
                                  class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Additional notes about this payment (optional)">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6 flex items-center justify-end space-x-3">
                        <a href="{{ route('repayments.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            {{ __('messages.cancel') }}
                        </a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ __('messages.add_repayment') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <!-- Select Loan Form -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('messages.select_loan_label') }}</h2>
            </div>
            <div class="p-6">
                @if(isset($loans) && $loans->count() > 0)
                    <p class="text-sm text-gray-600 mb-4">{{ __('messages.record_repayment_desc') }}</p>
                    <form method="GET" action="{{ route('repayments.create') }}">
                        <div class="mb-4">
                            <label for="loan_id" class="block text-sm font-medium text-gray-700">{{ __('messages.select_loan_label') }}</label>
                            <select name="loan_id" id="loan_id" 
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    required onchange="this.form.submit()">
                                <option value="">{{ __('messages.select_a_loan') }}</option>
                                @foreach($loans as $loanItem)
                                    <option value="{{ $loanItem->id }}">
                                        #{{ $loanItem->id }} - {{ $loanItem->client->first_name ?? '' }} {{ $loanItem->client->last_name ?? '' }} 
                                        (TZS {{ number_format($loanItem->principal_amount, 0) }} | Balance: TZS {{ number_format($loanItem->outstanding_balance ?? 0, 0) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
                            {{ __('messages.continue') }}
                        </button>
                    </form>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('messages.no_loans_available') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('messages.create_loan_first') }}</p>
                        <div class="mt-6 flex justify-center space-x-3">
                            <a href="{{ route('loans.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
                                {{ __('messages.new_loan') }}
                            </a>
                            <a href="{{ route('loans.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-50">
                                {{ __('messages.view_all_loans') }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection