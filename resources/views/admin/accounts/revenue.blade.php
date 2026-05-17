@extends('layouts.admin')
@section('title', 'Revenue / Sales')
@section('content')
<div class="space-y-6">
  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Revenue / Sales</h1>
      <p class="text-sm text-gray-500">Track all income from subscriptions and SMS services</p>
    </div>
    <!-- Period Filter -->
    <form method="GET" class="flex items-center space-x-2">
      <select name="period" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500">
        <option value="today"  {{ $period==='today'  ? 'selected':'' }}>Today</option>
        <option value="week"   {{ $period==='week'   ? 'selected':'' }}>This Week</option>
        <option value="month"  {{ $period==='month'  ? 'selected':'' }}>This Month</option>
        <option value="year"   {{ $period==='year'   ? 'selected':'' }}>This Year</option>
        <option value="custom" {{ $period==='custom' ? 'selected':'' }}>Custom</option>
      </select>
      @if($period === 'custom')
        <input type="date" name="from" value="{{ request('from', $start->format('Y-m-d')) }}" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
        <input type="date" name="to"   value="{{ request('to',   $end->format('Y-m-d')) }}"   class="text-sm border border-gray-300 rounded-lg px-3 py-2">
        <button type="submit" class="px-3 py-2 bg-green-600 text-white text-sm rounded-lg">Apply</button>
      @endif
    </form>
  </div>

  <p class="text-xs text-gray-400">Period: {{ $start->format('M d, Y') }} — {{ $end->format('M d, Y') }}</p>

  <!-- KPI Cards -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-gray-500 uppercase font-medium">Subscription Revenue</p>
      <p class="text-2xl font-bold text-green-600 mt-1">TZS {{ number_format($subscriptionRevenue) }}</p>
      <p class="text-xs text-gray-400 mt-1">Paid plan subscriptions</p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-gray-500 uppercase font-medium">SMS Sales Revenue</p>
      <p class="text-2xl font-bold text-blue-600 mt-1">TZS {{ number_format($smsRevenue) }}</p>
      <p class="text-xs text-gray-400 mt-1">{{ number_format($smsUnitsSold) }} units sold</p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-gray-500 uppercase font-medium">SMS Buying Cost</p>
      <p class="text-2xl font-bold text-orange-600 mt-1">TZS {{ number_format($smsCost) }}</p>
      <p class="text-xs text-gray-400 mt-1">@ TZS {{ number_format($smsCostPerUnit, 2) }}/sms</p>
    </div>
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-gray-500 uppercase font-medium">SMS Margin</p>
      <p class="text-2xl font-bold {{ $smsMargin >= 0 ? 'text-emerald-600' : 'text-red-600' }} mt-1">TZS {{ number_format($smsMargin) }}</p>
      <p class="text-xs text-gray-400 mt-1">Sales minus buying cost</p>
    </div>
  </div>

  <!-- Total Revenue Banner -->
  <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-5 text-white flex items-center justify-between">
    <div>
      <p class="text-sm font-medium opacity-80">Total Revenue (Period)</p>
      <p class="text-3xl font-bold">TZS {{ number_format($subscriptionRevenue + $smsRevenue) }}</p>
    </div>
    <svg class="w-12 h-12 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  </div>

  <!-- Recent Payments Table -->
  <div class="bg-white rounded-xl border">
    <div class="p-4 border-b">
      <h2 class="text-base font-semibold text-gray-900">Recent Subscription Payments</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tenant</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse($recentPayments as $p)
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-600">{{ $p->paid_at ? $p->paid_at->format('M d, Y') : '-' }}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $p->tenant->name ?? '-' }}</td>
            <td class="px-4 py-3 text-sm font-semibold text-green-700">TZS {{ number_format($p->amount) }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ ucfirst(str_replace('_',' ',$p->payment_method ?? '-')) }}</td>
            <td class="px-4 py-3 text-sm text-gray-400">{{ $p->reference ?? '-' }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $p->status==='success' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                {{ ucfirst($p->status) }}
              </span>
            </td>
          </tr>
          @empty
          <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No payments in this period</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
