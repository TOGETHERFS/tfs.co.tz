@extends('layouts.admin')
@section('title', 'Profit & Loss')
@section('content')
<div class="space-y-6">
  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Profit & Loss</h1>
      <p class="text-sm text-gray-500">Gross and net profit from all revenue streams</p>
    </div>
    <form method="GET" class="flex items-center space-x-2">
      <select name="period" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
        <option value="today"  {{ $period==='today'  ? 'selected':'' }}>Today</option>
        <option value="week"   {{ $period==='week'   ? 'selected':'' }}>This Week</option>
        <option value="month"  {{ $period==='month'  ? 'selected':'' }}>This Month</option>
        <option value="year"   {{ $period==='year'   ? 'selected':'' }}>This Year</option>
        <option value="custom" {{ $period==='custom' ? 'selected':'' }}>Custom</option>
      </select>
      @if($period === 'custom')
        <input type="date" name="from" value="{{ request('from', $start->format('Y-m-d')) }}" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
        <input type="date" name="to"   value="{{ request('to',   $end->format('Y-m-d')) }}"   class="text-sm border border-gray-300 rounded-lg px-3 py-2">
        <button type="submit" class="px-3 py-2 bg-indigo-600 text-white text-sm rounded-lg">Apply</button>
      @endif
    </form>
  </div>

  <p class="text-xs text-gray-400">Period: {{ $start->format('M d, Y') }} — {{ $end->format('M d, Y') }}</p>

  <!-- P&L Summary Cards -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-gray-500 uppercase font-medium">Total Revenue</p>
      <p class="text-2xl font-bold text-green-600 mt-1">TZS {{ number_format($totalRevenue) }}</p>
      <div class="mt-3 space-y-1 text-xs text-gray-500">
        <div class="flex justify-between"><span>Subscriptions</span><span class="font-medium text-gray-700">TZS {{ number_format($subscriptionRevenue) }}</span></div>
        <div class="flex justify-between"><span>SMS Sales</span><span class="font-medium text-gray-700">TZS {{ number_format($smsRevenue) }}</span></div>
      </div>
    </div>

    <div class="bg-white rounded-xl border p-5">
      <p class="text-xs text-gray-500 uppercase font-medium">Gross Profit</p>
      <p class="text-2xl font-bold {{ $grossProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }} mt-1">TZS {{ number_format($grossProfit) }}</p>
      <div class="mt-3 space-y-1 text-xs text-gray-500">
        <div class="flex justify-between"><span>Total Revenue</span><span class="font-medium text-green-700">+ TZS {{ number_format($totalRevenue) }}</span></div>
        <div class="flex justify-between"><span>SMS Buying Cost</span><span class="font-medium text-red-600">- TZS {{ number_format($smsBuyingCost) }}</span></div>
        <div class="border-t pt-1 flex justify-between font-semibold text-gray-700">
          <span>= Gross Profit</span><span>TZS {{ number_format($grossProfit) }}</span>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl border p-5 {{ $netProfit >= 0 ? 'border-emerald-300' : 'border-red-300' }}">
      <p class="text-xs text-gray-500 uppercase font-medium">Net Profit</p>
      <p class="text-3xl font-bold {{ $netProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }} mt-1">TZS {{ number_format($netProfit) }}</p>
      <div class="mt-3 space-y-1 text-xs text-gray-500">
        <div class="flex justify-between"><span>Gross Profit</span><span class="font-medium text-emerald-700">+ TZS {{ number_format($grossProfit) }}</span></div>
        <div class="flex justify-between"><span>Operating Expenses</span><span class="font-medium text-red-600">- TZS {{ number_format($totalExpenses) }}</span></div>
        <div class="border-t pt-1 flex justify-between font-semibold text-gray-700">
          <span>= Net Profit</span><span>TZS {{ number_format($netProfit) }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Detailed P&L Statement -->
  <div class="bg-white rounded-xl border">
    <div class="p-4 border-b">
      <h2 class="text-base font-semibold text-gray-900">Profit & Loss Statement</h2>
    </div>
    <div class="p-6 space-y-4 text-sm">
      <!-- Income -->
      <div>
        <h3 class="font-semibold text-gray-800 mb-2 uppercase text-xs tracking-wider">Income</h3>
        <div class="space-y-1 ml-4">
          <div class="flex justify-between py-1 border-b border-gray-100">
            <span class="text-gray-600">Subscription Revenue</span>
            <span class="font-medium text-green-700">TZS {{ number_format($subscriptionRevenue) }}</span>
          </div>
          <div class="flex justify-between py-1 border-b border-gray-100">
            <span class="text-gray-600">SMS Sales Revenue</span>
            <span class="font-medium text-green-700">TZS {{ number_format($smsRevenue) }}</span>
          </div>
          <div class="flex justify-between py-1 font-semibold text-gray-800">
            <span>Total Income</span>
            <span class="text-green-700">TZS {{ number_format($totalRevenue) }}</span>
          </div>
        </div>
      </div>

      <!-- Cost of Sales -->
      <div>
        <h3 class="font-semibold text-gray-800 mb-2 uppercase text-xs tracking-wider">Cost of Sales</h3>
        <div class="space-y-1 ml-4">
          <div class="flex justify-between py-1 border-b border-gray-100">
            <span class="text-gray-600">SMS Buying Cost</span>
            <span class="font-medium text-red-600">TZS {{ number_format($smsBuyingCost) }}</span>
          </div>
          <div class="flex justify-between py-1 font-semibold text-gray-800">
            <span>Total Cost of Sales</span>
            <span class="text-red-600">TZS {{ number_format($smsBuyingCost) }}</span>
          </div>
        </div>
      </div>

      <!-- Gross Profit -->
      <div class="bg-emerald-50 rounded-lg p-3">
        <div class="flex justify-between font-bold text-emerald-800">
          <span>GROSS PROFIT</span>
          <span>TZS {{ number_format($grossProfit) }}</span>
        </div>
      </div>

      <!-- Operating Expenses -->
      <div>
        <h3 class="font-semibold text-gray-800 mb-2 uppercase text-xs tracking-wider">Operating Expenses</h3>
        <div class="space-y-1 ml-4">
          @foreach(\App\Models\AdminExpense::$categories as $key => $label)
            @php $amt = $byCategory[$key] ?? 0; @endphp
            @if($amt > 0)
            <div class="flex justify-between py-1 border-b border-gray-100">
              <span class="text-gray-600">{{ $label }}</span>
              <span class="font-medium text-red-600">TZS {{ number_format($amt) }}</span>
            </div>
            @endif
          @endforeach
          @if($totalExpenses == 0)
            <p class="text-gray-400 py-1">No expenses recorded for this period</p>
          @endif
          <div class="flex justify-between py-1 font-semibold text-gray-800">
            <span>Total Operating Expenses</span>
            <span class="text-red-600">TZS {{ number_format($totalExpenses) }}</span>
          </div>
        </div>
      </div>

      <!-- Net Profit -->
      <div class="{{ $netProfit >= 0 ? 'bg-green-50' : 'bg-red-50' }} rounded-lg p-4">
        <div class="flex justify-between font-bold text-lg {{ $netProfit >= 0 ? 'text-green-800' : 'text-red-800' }}">
          <span>NET PROFIT {{ $netProfit < 0 ? '(LOSS)' : '' }}</span>
          <span>TZS {{ number_format(abs($netProfit)) }}</span>
        </div>
        @if($totalRevenue > 0)
        <p class="text-xs {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
          Net margin: {{ number_format(($netProfit / $totalRevenue) * 100, 1) }}%
        </p>
        @endif
      </div>
    </div>
  </div>

  <!-- Monthly Trend Table -->
  <div class="bg-white rounded-xl border">
    <div class="p-4 border-b">
      <h2 class="text-base font-semibold text-gray-900">Monthly Trend (Last 12 Months)</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Expenses</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Profit</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @foreach($monthlyTrend as $row)
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-medium text-gray-700">{{ $row['label'] }}</td>
            <td class="px-4 py-3 text-sm text-right text-green-700 font-medium">TZS {{ number_format($row['revenue']) }}</td>
            <td class="px-4 py-3 text-sm text-right text-red-600">TZS {{ number_format($row['expense']) }}</td>
            <td class="px-4 py-3 text-sm text-right font-semibold {{ $row['net'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
              TZS {{ number_format($row['net']) }}
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
