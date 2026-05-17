@extends('layouts.admin')

@section('content')
<div class="space-y-6">
  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">{{ $tenant->name }}</h1>
      <p class="text-sm text-gray-600">Manage subscription, payments, and billing</p>
    </div>
    <a href="{{ route('admin.subscriptions.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
      ← Back to All Subscriptions
    </a>
  </div>

  <!-- Quick Stats -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white rounded-lg border p-4">
      <p class="text-sm text-gray-600">Total Paid</p>
      <p class="text-2xl font-semibold text-green-600">TZS {{ number_format($stats['total_paid']) }}</p>
    </div>
    <div class="bg-white rounded-lg border p-4">
      <p class="text-sm text-gray-600">Pending Amount</p>
      <p class="text-2xl font-semibold text-orange-600">TZS {{ number_format($stats['total_pending']) }}</p>
    </div>
    <div class="bg-white rounded-lg border p-4">
      <p class="text-sm text-gray-600">Overdue Invoices</p>
      <p class="text-2xl font-semibold text-red-600">{{ $stats['overdue_invoices'] }}</p>
    </div>
  </div>

  <!-- Current Subscription -->
  <div class="bg-white rounded-lg border">
    <div class="p-4 border-b flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Current Subscription</h2>
      <div class="flex items-center space-x-2">
        @php $activeSub = $tenant->subscriptions->first(); @endphp
        @if($activeSub && $activeSub->status === 'active' && !$tenant->is_on_trial)
          <button onclick="document.getElementById('changePlanModal').classList.remove('hidden')"
                  class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
            ↑↓ Upgrade / Downgrade Plan
          </button>
        @endif
        <button onclick="document.getElementById('updatePlanModal').classList.remove('hidden')"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
          Update Plan
        </button>
      </div>
    </div>
    <div class="p-6">
      @php
        $subscription = $tenant->subscriptions->first();
      @endphp
      
      @if($subscription)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <p class="text-sm text-gray-600">Plan</p>
            <p class="text-lg font-semibold text-gray-900">{{ $subscription->plan->name ?? $tenant->plan_slug }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Status</p>
            @if($subscription->status === 'active')
              <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full bg-green-100 text-green-800">Active</span>
            @elseif($subscription->status === 'trial')
              <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full bg-blue-100 text-blue-800">Trial</span>
            @elseif($subscription->status === 'suspended')
              <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full bg-red-100 text-red-800">Suspended</span>
            @else
              <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($subscription->status) }}</span>
            @endif
          </div>
          <div>
            <p class="text-sm text-gray-600">Billing Period</p>
            <p class="text-lg text-gray-900">{{ ucfirst($subscription->billing_period ?? 'monthly') }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Renews At</p>
            <p class="text-lg text-gray-900">{{ $subscription->current_period_end ? \Carbon\Carbon::parse($subscription->current_period_end)->format('M d, Y') : '-' }}</p>
          </div>
        </div>
      @else
        <p class="text-gray-500">No active subscription</p>
      @endif

      <!-- Action Buttons -->
      <div class="mt-6 flex items-center space-x-3">
        @if($tenant->status === 'suspended')
          <form method="POST" action="{{ route('admin.subscriptions.reactivate', $tenant) }}" class="inline">
            @csrf
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
              Reactivate Subscription
            </button>
          </form>
        @else
          <button onclick="document.getElementById('suspendModal').classList.remove('hidden')" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
            Suspend Subscription
          </button>
        @endif
        <button onclick="document.getElementById('reducePlanModal').classList.remove('hidden')" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700" style="display: inline-block !important; visibility: visible !important; opacity: 1 !important;">
          Reduce Plan
        </button>
      </div>
    </div>
  </div>

  <!-- Manual Payment Recording -->
  <div class="bg-white rounded-lg border">
    <div class="p-4 border-b">
      <h2 class="text-lg font-semibold text-gray-900">Record Manual Payment</h2>
    </div>
    <div class="p-6">
      <form method="POST" action="{{ route('admin.subscriptions.record-payment', $tenant) }}" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Amount (TZS)</label>
            <input type="number" name="amount" step="0.01" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Payment Method</label>
            <select name="payment_method" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
              <option value="cash">Cash</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="mobile_money">Mobile Money</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Reference Number</label>
            <input type="text" name="reference" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Payment Date</label>
            <input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Link to Invoice (Optional)</label>
            <select name="invoice_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
              <option value="">No invoice</option>
              @foreach($tenant->invoices()->whereIn('status', ['pending', 'overdue'])->get() as $invoice)
                <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} - TZS {{ number_format($invoice->amount) }}</option>
              @endforeach
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
          </div>
        </div>
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
          Record Payment
        </button>
      </form>
    </div>
  </div>

  <!-- Invoices -->
  <div class="bg-white rounded-lg border">
    <div class="p-4 border-b">
      <h2 class="text-lg font-semibold text-gray-900">Invoices</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @forelse($tenant->invoices()->latest()->get() as $invoice)
            <tr>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $invoice->number }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $invoice->created_at->format('M d, Y') }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">TZS {{ number_format($invoice->amount) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : '-' }}
                @if($invoice->due_date && $invoice->due_date->isPast() && $invoice->status !== 'paid')
                  <span class="text-xs text-red-600">(Overdue)</span>
                @endif
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                @if($invoice->status === 'paid')
                  <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Paid</span>
                @elseif($invoice->status === 'pending')
                  <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                @elseif($invoice->status === 'overdue')
                  <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Overdue</span>
                @else
                  <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($invoice->status) }}</span>
                @endif
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                TZS {{ number_format($invoice->remaining_balance) }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-6 py-8 text-center text-gray-500">No invoices found</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Payments -->
  <div class="bg-white rounded-lg border">
    <div class="p-4 border-b">
      <h2 class="text-lg font-semibold text-gray-900">Recent Payments</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @forelse($tenant->payments()->latest()->take(10)->get() as $payment)
            <tr>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $payment->paid_at ? $payment->paid_at->format('M d, Y') : '-' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">TZS {{ number_format($payment->amount) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $payment->reference ?? '-' }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                @if($payment->status === 'success')
                  <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Success</span>
                @elseif($payment->status === 'pending')
                  <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                @else
                  <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">{{ ucfirst($payment->status) }}</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-6 py-8 text-center text-gray-500">No payments found</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Update Plan Modal -->
<div id="updatePlanModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Update Subscription Plan</h3>
      <button onclick="document.getElementById('updatePlanModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    @php $curSub = $tenant->subscriptions->first(); @endphp
    <form method="POST" action="{{ route('admin.subscriptions.update-plan', $tenant) }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm font-medium text-gray-700">Plan</label>
        <select name="plan_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          @foreach(\App\Models\Plan::where('is_active', true)->orderBy('price')->get() as $p)
            <option value="{{ $p->id }}" {{ ($curSub?->plan_id ?? $tenant->plan_id) == $p->id ? 'selected' : '' }}>
              {{ $p->name }} - TZS {{ number_format($p->price) }}/{{ $p->period }}
            </option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">New Period End Date</label>
        <input type="date" name="new_period_end"
               value="{{ $curSub?->current_period_end ? \Carbon\Carbon::parse($curSub->current_period_end)->format('Y-m-d') : now()->addMonth()->format('Y-m-d') }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <input type="hidden" name="keep_period" value="0">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
      </div>
      <div class="flex justify-end space-x-3">
        <button type="button" onclick="document.getElementById('updatePlanModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update Plan</button>
      </div>
    </form>
  </div>
</div>

<!-- Extend Plan Modal -->
<div id="extendPlanModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Extend Plan Period</h3>
      <button onclick="document.getElementById('extendPlanModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <form method="POST" action="{{ route('admin.subscriptions.extend-trial', $tenant) }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm font-medium text-gray-700">Number of Days</label>
        <input type="number" name="days" min="1" max="365" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Reason</label>
        <textarea name="reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
      </div>
      <div class="flex justify-end space-x-3">
        <button type="button" onclick="document.getElementById('extendPlanModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Extend Plan</button>
      </div>
    </form>
  </div>
</div>

<!-- Reduce Plan Modal -->
<div id="reducePlanModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Reduce Plan Period</h3>
      <button onclick="document.getElementById('reducePlanModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <form method="POST" action="{{ route('admin.subscriptions.reduce-plan', $tenant) }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm font-medium text-gray-700">Number of Days to Reduce</label>
        <input type="number" name="days" min="1" max="365" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Reason</label>
        <textarea name="reason" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"></textarea>
      </div>
      <div class="bg-orange-50 border border-orange-200 rounded-lg p-3">
        <p class="text-sm text-orange-800">⚠️ This will reduce the subscription period by the specified number of days.</p>
      </div>
      <div class="flex justify-end space-x-3">
        <button type="button" onclick="document.getElementById('reducePlanModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">Reduce Plan</button>
      </div>
    </form>
  </div>
</div>

<!-- Upgrade / Downgrade Plan Modal (active non-trial subscriptions only) -->
<div id="changePlanModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">↑↓ Upgrade / Downgrade Plan</h3>
      <button onclick="document.getElementById('changePlanModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    @php
      $currentSub = $tenant->subscriptions->first();
      $currentPlanId = $currentSub?->plan_id ?? $tenant->plan_id;
      $allPlans = \App\Models\Plan::where('is_active', true)->orderBy('price')->get();
    @endphp

    <form method="POST" action="{{ route('admin.subscriptions.update-plan', $tenant) }}" class="space-y-4">
      @csrf

      <!-- Current Plan info -->
      <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700">
        <span class="font-medium">Current Plan:</span>
        {{ $currentSub?->plan?->name ?? $tenant->plan_slug ?? 'None' }}
        &nbsp;|&nbsp;
        <span class="font-medium">Renews:</span>
        {{ $currentSub?->current_period_end ? \Carbon\Carbon::parse($currentSub->current_period_end)->format('M d, Y') : '-' }}
      </div>

      <!-- New Plan -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Select New Plan</label>
        <div class="space-y-2">
          @foreach($allPlans as $plan)
            <label class="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-indigo-50 {{ $plan->id == $currentPlanId ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200' }}">
              <div class="flex items-center space-x-3">
                <input type="radio" name="plan_id" value="{{ $plan->id }}"
                       {{ $plan->id == $currentPlanId ? 'checked' : '' }}
                       class="text-indigo-600 focus:ring-indigo-500">
                <div>
                  <div class="text-sm font-medium text-gray-900">
                    {{ $plan->name }}
                    @if($plan->id == $currentPlanId)
                      <span class="ml-1 text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">Current</span>
                    @endif
                  </div>
                  <div class="text-xs text-gray-500">
                    TZS {{ number_format($plan->price) }}/{{ $plan->period }}
                    @if($plan->staff_limit) &nbsp;· {{ $plan->staff_limit }} staff @endif
                    @if($plan->branch_limit) &nbsp;· {{ $plan->branch_limit }} branches @endif
                  </div>
                </div>
              </div>
              @if($plan->price > ($allPlans->firstWhere('id', $currentPlanId)?->price ?? 0))
                <span class="text-xs font-semibold text-green-700 bg-green-100 px-2 py-0.5 rounded-full">Upgrade</span>
              @elseif($plan->price < ($allPlans->firstWhere('id', $currentPlanId)?->price ?? 0))
                <span class="text-xs font-semibold text-orange-700 bg-orange-100 px-2 py-0.5 rounded-full">Downgrade</span>
              @endif
            </label>
          @endforeach
        </div>
      </div>

      <!-- Keep or override period end -->
      <div>
        <label class="flex items-center space-x-2 text-sm text-gray-700 cursor-pointer">
          <input type="checkbox" name="keep_period" value="1" checked id="keepPeriodCheck"
                 onchange="document.getElementById('newPeriodEndRow').style.display = this.checked ? 'none' : 'block'"
                 class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
          <span>Keep existing renewal date ({{ $currentSub?->current_period_end ? \Carbon\Carbon::parse($currentSub->current_period_end)->format('M d, Y') : 'N/A' }})</span>
        </label>
      </div>
      <div id="newPeriodEndRow" style="display:none">
        <label class="block text-sm font-medium text-gray-700 mb-1">New Period End Date</label>
        <input type="date" name="new_period_end"
               value="{{ $currentSub?->current_period_end ? \Carbon\Carbon::parse($currentSub->current_period_end)->format('Y-m-d') : now()->addMonth()->format('Y-m-d') }}"
               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
      </div>

      <!-- Notes -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
        <textarea name="notes" rows="2" placeholder="Reason for plan change..."
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
      </div>

      <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
        <p class="text-xs text-blue-800">ℹ️ Changing the plan does not generate a new invoice. Use "Record Manual Payment" below if payment was collected.</p>
      </div>

      <div class="flex justify-end space-x-3">
        <button type="button" onclick="document.getElementById('changePlanModal').classList.add('hidden')"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
          Apply Plan Change
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Suspend Modal -->
<div id="suspendModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Suspend Subscription</h3>
      <button onclick="document.getElementById('suspendModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <form method="POST" action="{{ route('admin.subscriptions.suspend', $tenant) }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm font-medium text-gray-700">Reason for Suspension</label>
        <textarea name="reason" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
      </div>
      <div class="flex justify-end space-x-3">
        <button type="button" onclick="document.getElementById('suspendModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Suspend</button>
      </div>
    </form>
  </div>
</div>
@endsection
