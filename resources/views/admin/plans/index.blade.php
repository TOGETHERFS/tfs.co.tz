@extends('layouts.admin')

@section('title', 'Plans')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="bg-white shadow">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="py-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Plans</h1>
                    <p class="mt-1 text-sm text-gray-600">Manage subscription plans and pricing</p>
                </div>
                <div>
                    <a href="{{ route('billing.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Billing Overview</a>
                </div>
            </div>
        </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 py-6">
        @if(session('status'))
            <div class="mb-4 p-4 rounded bg-green-50 text-green-700">{{ session('status') }}</div>
        @endif

        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Per Staff</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Currency</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SMS Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SMS Limit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($plans as $plan)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $plan->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $plan->code }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($plan->period) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">TZS {{ number_format($plan->price) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">TZS {{ number_format($plan->price_per_staff ?? 0) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $plan->currency }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">TZS {{ number_format($plan->sms_price_per_unit ?? 30, 0) }}/sms</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($plan->sms_volume_limit ?? 1000) }} sms</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <a href="{{ route('admin.plans.edit', $plan) }}" class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-4 text-center text-sm text-gray-500">No plans found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $plans->links() }}
                </div>
            </div>
        </div>

        <!-- Tenant Subscriptions Section -->
        <div class="mt-8 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Tenant Subscriptions</h2>
                
                <!-- Manual Subscription Update Form -->
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h3 class="text-md font-medium text-blue-900 mb-3">Update/Create Tenant Subscription (Manual Payment)</h3>
                    <form id="subForm" action="{{ route('admin.plans.update-subscription') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Tenant</label>
                            <select id="sf_tenant_id" name="tenant_id" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md">
                                <option value="">Select Tenant</option>
                                @foreach($tenants as $tenant)
                                    <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Plan</label>
                            <select id="sf_plan_id" name="plan_id" required onchange="recalcSubscription()" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md">
                                @foreach($allPlans as $p)
                                    <option value="{{ $p->id }}" data-price="{{ $p->price }}">{{ $p->name }} (TZS {{ number_format($p->price) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                            <select id="sf_status" name="status" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md">
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Start Date</label>
                            <input id="sf_start_date" type="date" name="start_date" required value="{{ now()->format('Y-m-d') }}" onchange="recalcSubscription()" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Months</label>
                            <input id="sf_months" type="number" name="months" required min="1" max="120" value="1" onchange="recalcSubscription()" oninput="recalcSubscription()" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Valid Until (auto)</label>
                            <input id="sf_period_end" type="date" name="current_period_end" required value="{{ now()->addMonth()->format('Y-m-d') }}" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Total Amount (TZS)</label>
                            <div id="sf_total_display" class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-md bg-green-50 text-green-800 font-semibold">TZS 0</div>
                            <input type="hidden" id="sf_total_amount" name="total_amount" value="0">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Notes</label>
                            <input id="sf_notes" type="text" name="notes" placeholder="e.g., Cash payment" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md">
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                                Update Subscription
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Subscriptions Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tenant</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($subscriptions as $sub)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $sub->tenant->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $sub->plan->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                            {{ $sub->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $sub->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $sub->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $sub->status === 'expired' ? 'bg-gray-100 text-gray-800' : '' }}">
                                            {{ ucfirst($sub->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ optional($sub->current_period_start)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ optional($sub->current_period_end)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $sub->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                        <div class="inline-flex items-center gap-2">
                                            <button type="button"
                                                onclick="prefillSubscriptionForm({{ $sub->tenant_id }}, {{ $sub->plan_id }}, '{{ $sub->status }}', '{{ optional($sub->current_period_end)->format('Y-m-d') }}', '{{ optional($sub->current_period_start)->format('Y-m-d') }}')"
                                                class="inline-flex items-center px-2 py-1 bg-yellow-500 text-white text-xs font-medium rounded hover:bg-yellow-600">
                                                Edit
                                            </button>
                                            <form method="POST" action="{{ route('admin.plans.delete-subscription', $sub) }}"
                                                  onsubmit="return confirm('Delete subscription for {{ addslashes($sub->tenant->name ?? 'this tenant') }}? This cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center px-2 py-1 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-4 text-center text-sm text-gray-500">No subscriptions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $subscriptions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function recalcSubscription() {
    const planSelect = document.getElementById('sf_plan_id');
    const months = parseInt(document.getElementById('sf_months').value) || 1;
    const startDate = document.getElementById('sf_start_date').value;
    const selectedOption = planSelect.options[planSelect.selectedIndex];
    const price = parseFloat(selectedOption ? selectedOption.dataset.price : 0) || 0;

    const total = price * months;
    document.getElementById('sf_total_display').textContent = 'TZS ' + total.toLocaleString();
    document.getElementById('sf_total_amount').value = total;

    if (startDate) {
        const d = new Date(startDate);
        d.setMonth(d.getMonth() + months);
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        document.getElementById('sf_period_end').value = `${yyyy}-${mm}-${dd}`;
    }
}

function prefillSubscriptionForm(tenantId, planId, status, periodEnd, startDate) {
    document.getElementById('sf_tenant_id').value = tenantId;
    document.getElementById('sf_plan_id').value = planId;
    document.getElementById('sf_status').value = status;
    document.getElementById('sf_start_date').value = startDate || '{{ now()->format("Y-m-d") }}';
    document.getElementById('sf_months').value = 1;
    document.getElementById('sf_notes').value = '';
    recalcSubscription();
    document.getElementById('sf_period_end').value = periodEnd;
    document.getElementById('subForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
    const form = document.getElementById('subForm').closest('.bg-blue-50');
    form.classList.add('ring-2', 'ring-blue-500');
    setTimeout(() => form.classList.remove('ring-2', 'ring-blue-500'), 2000);
}

document.addEventListener('DOMContentLoaded', recalcSubscription);
</script>
@endpush
@endsection