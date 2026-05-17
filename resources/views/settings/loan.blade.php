@extends('settings.layout')

@section('settings_content')
<div class="p-6 max-w-4xl">
  <h1 class="text-2xl font-semibold mb-4">Loan Settings</h1>
  <p class="text-gray-600 mb-6">Configure default loan parameters and processing rules.</p>

  <form method="POST" action="{{ route('settings.loan.update') }}" class="space-y-6">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium">Default Interest Rate (% per month)</label>
        <input type="number" step="0.01" min="0" max="100" name="default_interest_rate" value="{{ old('default_interest_rate', $settings['default_interest_rate']) }}" class="mt-1 border rounded w-full p-2" required />
      </div>
      <div>
        <label class="block text-sm font-medium">Default Loan Term (months)</label>
        <input type="number" min="1" max="120" name="default_loan_term" value="{{ old('default_loan_term', $settings['default_loan_term']) }}" class="mt-1 border rounded w-full p-2" required />
      </div>
      <div>
        <label class="block text-sm font-medium">Minimum Loan Amount</label>
        <input type="number" min="0" name="minimum_loan_amount" value="{{ old('minimum_loan_amount', $settings['minimum_loan_amount']) }}" class="mt-1 border rounded w-full p-2" required />
      </div>
      <div>
        <label class="block text-sm font-medium">Maximum Loan Amount</label>
        <input type="number" min="0" name="maximum_loan_amount" value="{{ old('maximum_loan_amount', $settings['maximum_loan_amount']) }}" class="mt-1 border rounded w-full p-2" required />
      </div>
      <div>
        <label class="block text-sm font-medium">Processing Fee Rate (%)</label>
        <input type="number" step="0.01" min="0" max="100" name="processing_fee_rate" value="{{ old('processing_fee_rate', $settings['processing_fee_rate']) }}" class="mt-1 border rounded w-full p-2" required />
      </div>
      <div>
        <label class="block text-sm font-medium">Late Payment Penalty (%)</label>
        <input type="number" step="0.01" min="0" max="100" name="late_payment_penalty" value="{{ old('late_payment_penalty', $settings['late_payment_penalty']) }}" class="mt-1 border rounded w-full p-2" required />
      </div>
      <div>
        <label class="block text-sm font-medium">Grace Period (days)</label>
        <input type="number" min="0" max="30" name="grace_period_days" value="{{ old('grace_period_days', $settings['grace_period_days']) }}" class="mt-1 border rounded w-full p-2" required />
      </div>
      <div>
        <label class="block text-sm font-medium">Auto-approve Limit</label>
        <input type="number" min="0" name="auto_approve_limit" value="{{ old('auto_approve_limit', $settings['auto_approve_limit']) }}" class="mt-1 border rounded w-full p-2" required />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="flex items-center space-x-2">
        <input type="hidden" name="require_guarantor" value="0" />
        <input type="checkbox" name="require_guarantor" value="1" class="rounded" {{ old('require_guarantor', $settings['require_guarantor']) ? 'checked' : '' }} />
        <label class="text-sm">Require Guarantor</label>
      </div>
      <div class="flex items-center space-x-2">
        <input type="hidden" name="allow_partial_payments" value="0" />
        <input type="checkbox" name="allow_partial_payments" value="1" class="rounded" {{ old('allow_partial_payments', $settings['allow_partial_payments']) ? 'checked' : '' }} />
        <label class="text-sm">Allow Partial Payments</label>
      </div>
    </div>

    <div>
      <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save Loan Settings</button>
    </div>
  </form>
</div>
@endsection