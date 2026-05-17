@php
    $user = auth()->user();
    $isAdminContext = auth()->check() && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
    $layout = $isAdminContext ? 'layouts.admin' : 'layouts.user';
@endphp
@extends($layout)

@section('title', 'Billing & Subscription')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Billing & Subscription</h1>
            <p class="text-sm text-gray-600">Manage your subscription and view billing history</p>
        </div>
        <a href="{{ route('billing.subscription') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            Manage Subscription
        </a>
    </div>

    <!-- Current Subscription Card -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Current Subscription</h2>
        </div>
        <div class="p-6">
            @if($subscription && $subscription->plan)
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="mb-4 md:mb-0">
                        <div class="flex items-center mb-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                            <span class="ml-3 text-xl font-bold text-gray-900">{{ $subscription->plan->name }} Plan</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <p class="text-sm text-gray-500">Monthly Price</p>
                                <p class="text-lg font-bold text-gray-900">TZS {{ number_format($subscription->plan->price) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Valid Until</p>
                                <p class="text-lg font-bold text-gray-900">{{ optional($subscription->current_period_end)->format('M d, Y') ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Staff Members</p>
                                <p class="text-lg font-bold text-gray-900">{{ $branch_count ?? 1 }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Billing Period</p>
                                <p class="text-lg font-bold text-gray-900">{{ ucfirst($subscription->plan->period) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col space-y-2">
                        <a href="{{ route('billing.subscription') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md text-center hover:bg-blue-700">
                            Change Plan
                        </a>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No Active Subscription</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by choosing a subscription plan.</p>
                    <div class="mt-6">
                        <a href="{{ route('billing.subscription') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            View Plans
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Billing Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100">
                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Invoices</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_invoices'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Pending Payment</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['pending_invoices'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Paid</p>
                    <p class="text-2xl font-bold text-gray-900">TZS {{ number_format($stats['total_paid'] ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Invoices -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Recent Invoices</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices ?? [] as $invoice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $invoice->number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ optional($invoice->created_at)->format('M d, Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">TZS {{ number_format($invoice->amount) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($invoice->status == 'paid') bg-green-100 text-green-800
                                    @elseif($invoice->status == 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($invoice->status == 'overdue') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('billing.invoices.show', $invoice) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                @if($invoice->status != 'paid')
                                    <span class="mx-1 text-gray-300">|</span>
                                    <a href="{{ route('billing.invoices.show', $invoice) }}" class="text-green-600 hover:text-green-900">Pay Now</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-2">No invoices yet</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($isAdminContext)
    <!-- Super Admin Only Sections -->
    <div class="mt-8 border-t pt-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Admin Tools</h2>
        
        <!-- Admin Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="p-4 bg-white shadow rounded">
                <div class="text-gray-500 text-sm">Collections MTD (TZS)</div>
                <div class="text-xl font-bold">{{ number_format($collections_mtd ?? 0) }}</div>
            </div>
            <div class="p-4 bg-white shadow rounded">
                <div class="text-gray-500 text-sm">Successful</div>
                <div class="text-xl font-bold">{{ $txn_metrics['successful']['count'] ?? 0 }} / TZS {{ number_format($txn_metrics['successful']['amount'] ?? 0) }}</div>
            </div>
            <div class="p-4 bg-white shadow rounded">
                <div class="text-gray-500 text-sm">Pending</div>
                <div class="text-xl font-bold">{{ $txn_metrics['pending']['count'] ?? 0 }} / TZS {{ number_format($txn_metrics['pending']['amount'] ?? 0) }}</div>
            </div>
            <div class="p-4 bg-white shadow rounded">
                <div class="text-gray-500 text-sm">Failed</div>
                <div class="text-xl font-bold">{{ $txn_metrics['failed']['count'] ?? 0 }} / TZS {{ number_format($txn_metrics['failed']['amount'] ?? 0) }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="p-4 bg-white shadow rounded">
                <div class="text-gray-500 text-sm">Refunds</div>
                <div class="text-xl font-bold">{{ $txn_metrics['refunded']['count'] ?? 0 }} / TZS {{ number_format($txn_metrics['refunded']['amount'] ?? 0) }}</div>
            </div>
            <div class="p-4 bg-white shadow rounded">
                <div class="text-gray-500 text-sm">Chargebacks</div>
                <div class="text-xl font-bold">{{ $txn_metrics['chargeback']['count'] ?? 0 }} / TZS {{ number_format($txn_metrics['chargeback']['amount'] ?? 0) }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Revenue by Plan -->
            <div class="p-4 bg-white shadow rounded">
                <h2 class="text-xl font-semibold mb-2">Revenue by Plan</h2>
                <ul class="divide-y divide-gray-200">
                    @foreach($revenue_by_plan ?? [] as $planName => $amount)
                        <li class="py-2 flex justify-between">
                            <span class="text-gray-600">{{ $planName }}</span>
                            <span class="font-medium">TZS {{ number_format($amount) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- A/R Aging -->
            <div class="p-4 bg-white shadow rounded">
                <h2 class="text-xl font-semibold mb-2">A/R Aging</h2>
                <ul class="divide-y divide-gray-200">
                    <li class="py-2 flex justify-between">
                        <span class="text-gray-600">0–30 days</span>
                        <span class="font-medium">{{ $ar_aging['0_30']['count'] ?? 0 }} / TZS {{ number_format($ar_aging['0_30']['amount'] ?? 0) }}</span>
                    </li>
                    <li class="py-2 flex justify-between">
                        <span class="text-gray-600">31–60 days</span>
                        <span class="font-medium">{{ $ar_aging['31_60']['count'] ?? 0 }} / TZS {{ number_format($ar_aging['31_60']['amount'] ?? 0) }}</span>
                    </li>
                    <li class="py-2 flex justify-between">
                        <span class="text-gray-600">61–90 days</span>
                        <span class="font-medium">{{ $ar_aging['61_90']['count'] ?? 0 }} / TZS {{ number_format($ar_aging['61_90']['amount'] ?? 0) }}</span>
                    </li>
                    <li class="py-2 flex justify-between">
                        <span class="text-gray-600">90+ days</span>
                        <span class="font-medium">{{ $ar_aging['90_plus']['count'] ?? 0 }} / TZS {{ number_format($ar_aging['90_plus']['amount'] ?? 0) }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Selcom Top-up Ledger -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-2">Payment Ledger</h2>
            <div class="bg-white shadow rounded">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Reference</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Amount</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Paid At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($selcom_ledger ?? [] as $p)
                            <tr>
                                <td class="px-4 py-2">{{ $p->reference }}</td>
                                <td class="px-4 py-2">TZS {{ number_format($p->amount) }}</td>
                                <td class="px-4 py-2">{{ ucfirst($p->status) }}</td>
                                <td class="px-4 py-2">{{ optional($p->paid_at)->format('Y-m-d H:i') }}</td>
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

        <!-- Admin Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="p-4 bg-white shadow rounded">
                <h3 class="text-lg font-semibold mb-2">Generate Invoice</h3>
                <form method="POST" action="{{ route('billing.invoice.generate') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-600">Amount (TZS)</label>
                        <input type="number" name="amount" min="1000" class="mt-1 w-full border rounded px-3 py-2" placeholder="e.g. 50000" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600">Description</label>
                        <input type="text" name="description" class="mt-1 w-full border rounded px-3 py-2" placeholder="Optional">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600">Due date</label>
                        <input type="date" name="due_date" class="mt-1 w-full border rounded px-3 py-2">
                    </div>
                    <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded">Generate</button>
                </form>
            </div>

            <div class="p-4 bg-white shadow rounded">
                <h3 class="text-lg font-semibold mb-2">Suspend Account</h3>
                <p class="text-sm text-gray-600 mb-3">Suspend tenant if overdue invoices exist.</p>
                <form method="POST" action="{{ route('billing.suspend.nonpayment') }}">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded">Suspend</button>
                </form>
            </div>

            <div class="p-4 bg-white shadow rounded">
                <h3 class="text-lg font-semibold mb-2">Apply Credit</h3>
                <form method="POST" action="{{ route('billing.credit.goodwill') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-600">Amount (TZS)</label>
                        <input type="number" name="amount" min="100" class="mt-1 w-full border rounded px-3 py-2" placeholder="e.g. 10000" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600">Note</label>
                        <input type="text" name="note" class="mt-1 w-full border rounded px-3 py-2" placeholder="Optional">
                    </div>
                    <button type="submit" class="px-3 py-2 bg-green-600 text-white rounded">Apply Credit</button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection