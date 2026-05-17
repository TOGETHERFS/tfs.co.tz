@extends('layouts.admin')

@section('content')
<div class="space-y-6">
  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Subscription Management</h1>
      <p class="text-sm text-gray-600">Manage all tenant subscriptions, payments, and billing</p>
    </div>
  </div>

  <!-- Stats Overview -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-lg border p-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">Total Tenants</p>
          <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_tenants'] }}</p>
        </div>
        <div class="p-3 bg-blue-100 rounded-full">
          <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg border p-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">Active Subscriptions</p>
          <p class="text-2xl font-semibold text-green-600">{{ $stats['active_subscriptions'] }}</p>
        </div>
        <div class="p-3 bg-green-100 rounded-full">
          <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg border p-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">Revenue (MTD)</p>
          <p class="text-2xl font-semibold text-gray-900">TZS {{ number_format($stats['total_revenue_mtd']) }}</p>
        </div>
        <div class="p-3 bg-purple-100 rounded-full">
          <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg border p-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">Overdue Invoices</p>
          <p class="text-2xl font-semibold text-red-600">{{ $stats['overdue_invoices'] }}</p>
        </div>
        <div class="p-3 bg-red-100 rounded-full">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
    </div>
  </div>

  <!-- Tenants Table -->
  <div class="bg-white rounded-lg border overflow-hidden">
    <div class="p-4 border-b">
      <h2 class="text-lg font-semibold text-gray-900">All Tenant Subscriptions</h2>
      <p class="text-sm text-gray-600 mt-1">View and manage all tenant billing and subscription status</p>
    </div>
    
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Billing Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trial End</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Renewal End</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @forelse($tenants as $tenant)
            @php
              $subscription = $tenant->subscriptions->first();
              $totalRevenue = $tenant->payments->whereIn('status', ['success', 'completed'])->sum('amount');
              $pendingInvoices = $tenant->invoices->where('status', 'pending');
              $overdueInvoices = $tenant->invoices->where('status', 'overdue');
              $pendingAmount = $pendingInvoices->sum('amount');
              $overdueAmount = $overdueInvoices->sum('amount');
            @endphp
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ $tenant->name }}</div>
                  <div class="text-sm text-gray-500">{{ $tenant->contact_email }}</div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                @if($subscription && $subscription->plan)
                  <span class="text-sm text-gray-900">{{ $subscription->plan->name }}</span>
                  <div class="text-xs text-gray-500">{{ ucfirst($subscription->billing_period ?? 'monthly') }}</div>
                @else
                  <span class="text-sm text-gray-500">{{ $tenant->plan_slug ?? 'No plan' }}</span>
                @endif
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                @if($tenant->status === 'active')
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                @elseif($tenant->status === 'trial')
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Trial</span>
                @elseif($tenant->status === 'suspended')
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Suspended</span>
                @else
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($tenant->status) }}</span>
                @endif
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="space-y-1">
                  @if($overdueAmount > 0)
                    <div class="flex items-center text-xs">
                      <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full font-semibold">
                        Overdue: TZS {{ number_format($overdueAmount) }}
                      </span>
                    </div>
                  @endif
                  @if($pendingAmount > 0)
                    <div class="flex items-center text-xs">
                      <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full font-semibold">
                        Pending: TZS {{ number_format($pendingAmount) }}
                      </span>
                    </div>
                  @endif
                  @if($overdueAmount == 0 && $pendingAmount == 0 && $totalRevenue > 0)
                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full font-semibold">
                      ✓ Paid
                    </span>
                  @endif
                  @if($overdueAmount == 0 && $pendingAmount == 0 && $totalRevenue == 0)
                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full font-semibold">
                      Pending
                    </span>
                  @endif
                </div>
              </td>
              {{-- Trial End --}}
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                @if($tenant->trial_ends_at)
                  @if($tenant->trial_ends_at->isFuture())
                    <div class="text-xs">
                      <div class="font-medium text-blue-600">{{ $tenant->trial_ends_at->format('M d, Y') }}</div>
                      <div class="text-blue-500">{{ $tenant->trial_ends_at->diffForHumans() }}</div>
                    </div>
                  @else
                    <div class="text-xs">
                      <div class="font-medium text-gray-500">{{ $tenant->trial_ends_at->format('M d, Y') }}</div>
                      <div class="text-red-500">Expired</div>
                    </div>
                  @endif
                @else
                  <span class="text-gray-400">-</span>
                @endif
              </td>
              {{-- Renewal End --}}
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                @if($tenant->plan_renews_at)
                  @php $renewsAt = \Carbon\Carbon::parse($tenant->plan_renews_at); @endphp
                  @if($renewsAt->isFuture())
                    <div class="text-xs">
                      <div class="font-medium text-green-700">{{ $renewsAt->format('M d, Y') }}</div>
                      <div class="text-green-600">{{ $renewsAt->diffForHumans() }}</div>
                    </div>
                  @else
                    <div class="text-xs">
                      <div class="font-medium text-gray-500">{{ $renewsAt->format('M d, Y') }}</div>
                      <div class="text-red-500">Expired</div>
                    </div>
                  @endif
                @elseif($subscription && $subscription->current_period_end)
                  @php $periodEnd = \Carbon\Carbon::parse($subscription->current_period_end); @endphp
                  @if($periodEnd->isFuture())
                    <div class="text-xs">
                      <div class="font-medium text-green-700">{{ $periodEnd->format('M d, Y') }}</div>
                      <div class="text-green-600">{{ $periodEnd->diffForHumans() }}</div>
                    </div>
                  @else
                    <div class="text-xs">
                      <div class="font-medium text-gray-500">{{ $periodEnd->format('M d, Y') }}</div>
                      <div class="text-red-500">Expired</div>
                    </div>
                  @endif
                @else
                  <span class="text-gray-400">-</span>
                @endif
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">TZS {{ number_format($totalRevenue) }}</div>
                @if($tenant->invoices->count() > 0)
                  <div class="text-xs text-gray-500">{{ $tenant->invoices->count() }} invoice(s)</div>
                @endif
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center space-x-2">
                  <a href="{{ route('admin.subscriptions.show', $tenant) }}" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Manage
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                No tenants found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
