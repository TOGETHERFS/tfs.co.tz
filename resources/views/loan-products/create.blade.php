@extends('layouts.app')

@section('title', __('messages.create_loan_product'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('loan-products.index') }}" class="text-blue-600 hover:underline">&larr; {{ __('messages.back_to_loan_products') }}</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ __('messages.create_loan_product') }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
        <form method="POST" action="{{ route('loan-products.store') }}">
            @csrf
            
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700">{{ __('messages.product_name') }}</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="min_amount" class="block text-sm font-medium text-gray-700">{{ __('messages.min_amount') }}</label>
                    <input type="number" name="min_amount" id="min_amount" value="{{ old('min_amount', 0) }}" min="0" step="0.01"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('min_amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="max_amount" class="block text-sm font-medium text-gray-700">{{ __('messages.max_amount') }}</label>
                    <input type="number" name="max_amount" id="max_amount" value="{{ old('max_amount') }}" min="0" step="0.01"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('max_amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="interest_rate" class="block text-sm font-medium text-gray-700">{{ __('messages.interest_rate') }} (%)</label>
                    <input type="number" name="interest_rate" id="interest_rate" value="{{ old('interest_rate', 3.5) }}" min="0" max="100" step="0.01" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('interest_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="interest_type" class="block text-sm font-medium text-gray-700">{{ __('messages.interest_type') }}</label>
                    <select name="interest_type" id="interest_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="flat" {{ old('interest_type') === 'flat' ? 'selected' : '' }}>{{ __('messages.flat') }}</option>
                        <option value="reducing" {{ old('interest_type') === 'reducing' ? 'selected' : '' }}>{{ __('messages.reducing_balance') }}</option>
                    </select>
                    @error('interest_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <label for="repayment_type" class="block text-sm font-medium text-gray-700">Repayment Structure</label>
                <select name="repayment_type" id="repayment_type"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        onchange="toggleRepaymentTypeNote()">
                    <option value="amortized" {{ old('repayment_type', 'amortized') === 'amortized' ? 'selected' : '' }}>Standard (Principal + Interest each installment)</option>
                    <option value="interest_only" {{ old('repayment_type') === 'interest_only' ? 'selected' : '' }}>Special / Agriculture (Interest only each installment; principal on last)</option>
                </select>
                <p id="repayment_type_note" class="mt-1 text-xs text-orange-600 hidden">
                    &#9888; Interest-Only: borrower pays <strong>interest only</strong> each period. The full principal is repaid in a lump sum on the <strong>last installment</strong>.
                </p>
                @error('repayment_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="min_term" class="block text-sm font-medium text-gray-700">{{ __('messages.min_term_months') }}</label>
                    <input type="number" name="min_term" id="min_term" value="{{ old('min_term', 1) }}" min="1"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('min_term')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="max_term" class="block text-sm font-medium text-gray-700">{{ __('messages.max_term_months') }}</label>
                    <input type="number" name="max_term" id="max_term" value="{{ old('max_term', 12) }}" min="1"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('max_term')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Management Fee --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="processing_fee_type" class="block text-sm font-medium text-gray-700">{{ __('messages.management_fee_type') }}</label>
                    <select name="processing_fee_type" id="processing_fee_type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            onchange="updateMgmtFeeLabel()">
                        <option value="percentage">{{ __('messages.percentage') }} (%)</option>
                        <option value="fixed">{{ __('messages.fixed_amount') }} (TZS)</option>
                    </select>
                </div>
                <div>
                    <label for="processing_fee" id="mgmt_fee_label" class="block text-sm font-medium text-gray-700">
                        {{ __('messages.management_fee') }} (<span id="mgmt_fee_unit">%</span>)
                    </label>
                    <input type="number" name="processing_fee" id="processing_fee" value="{{ old('processing_fee', 0) }}" min="0" step="0.01"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('processing_fee')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Late Payment Penalty --}}
            <div class="mb-6 border border-orange-200 rounded-lg p-4 bg-orange-50">
                <h3 class="text-sm font-semibold text-orange-800 mb-3">{{ __('messages.late_payment_penalty') }}</h3>
                <p class="text-xs text-orange-600 mb-3">{{ __('messages.penalty_section_desc') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="penalty_type" class="block text-sm font-medium text-gray-700">{{ __('messages.penalty_type') }}</label>
                        <select name="penalty_type" id="penalty_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                onchange="togglePenaltyFields()">
                            <option value="none">{{ __('messages.penalty_none') }}</option>
                            <option value="percentage">{{ __('messages.penalty_percentage') }}</option>
                            <option value="fixed">{{ __('messages.penalty_fixed') }}</option>
                        </select>
                    </div>
                    <div id="penalty_value_wrap">
                        <label for="penalty_value" class="block text-sm font-medium text-gray-700">{{ __('messages.penalty_value') }}</label>
                        <input type="number" name="penalty_value" id="penalty_value" value="{{ old('penalty_value', 0) }}" min="0" step="0.01"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div id="penalty_freq_wrap">
                        <label for="penalty_frequency" class="block text-sm font-medium text-gray-700">{{ __('messages.penalty_frequency') }}</label>
                        <select name="penalty_frequency" id="penalty_frequency"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="1">{{ __('messages.penalty_freq_daily') }}</option>
                            <option value="7">{{ __('messages.penalty_freq_weekly') }}</option>
                            <option value="14">{{ __('messages.penalty_freq_biweekly') }}</option>
                            <option value="30" selected>{{ __('messages.penalty_freq_monthly') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700">{{ __('messages.description') }}</label>
                <textarea name="description" id="description" rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    {{ __('messages.create_product') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateMgmtFeeLabel() {
        const type = document.getElementById('processing_fee_type').value;
        document.getElementById('mgmt_fee_unit').textContent = type === 'percentage' ? '%' : 'TZS';
        const input = document.getElementById('processing_fee');
        if (type === 'percentage') {
            input.setAttribute('max', '100');
            input.setAttribute('step', '0.01');
        } else {
            input.removeAttribute('max');
            input.setAttribute('step', '1');
        }
    }

    function togglePenaltyFields() {
        const type = document.getElementById('penalty_type').value;
        const show = type !== 'none';
        document.getElementById('penalty_value_wrap').style.opacity = show ? '1' : '0.4';
        document.getElementById('penalty_freq_wrap').style.opacity = show ? '1' : '0.4';
        document.getElementById('penalty_value').disabled = !show;
        document.getElementById('penalty_frequency').disabled = !show;
    }

    function toggleRepaymentTypeNote() {
        const val = document.getElementById('repayment_type').value;
        const note = document.getElementById('repayment_type_note');
        note.classList.toggle('hidden', val !== 'interest_only');
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateMgmtFeeLabel();
        togglePenaltyFields();
        toggleRepaymentTypeNote();
    });
</script>
@endpush
