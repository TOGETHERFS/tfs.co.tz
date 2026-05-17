@extends('layouts.admin')
@section('title', 'Expenses')
@section('content')
<div class="space-y-6">
  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Expenses</h1>
      <p class="text-sm text-gray-500">Record and track operating expenses</p>
    </div>
    <div class="flex items-center space-x-2">
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
          <button type="submit" class="px-3 py-2 bg-orange-600 text-white text-sm rounded-lg">Apply</button>
        @endif
      </form>
      <button onclick="document.getElementById('addExpenseModal').classList.remove('hidden')"
              class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700">
        + Record Expense
      </button>
    </div>
  </div>

  @if(session('success'))
    <div class="p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
  @endif

  <p class="text-xs text-gray-400">Period: {{ $start->format('M d, Y') }} — {{ $end->format('M d, Y') }}</p>

  <!-- KPI Cards -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border p-5 md:col-span-1">
      <p class="text-xs text-gray-500 uppercase font-medium">Total Expenses</p>
      <p class="text-2xl font-bold text-red-600 mt-1">TZS {{ number_format($totalExpenses) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-5 md:col-span-2">
      <p class="text-xs text-gray-500 uppercase font-medium mb-3">By Category</p>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
        @foreach(\App\Models\AdminExpense::$categories as $key => $label)
          @php $amt = $byCategory[$key] ?? 0; @endphp
          @if($amt > 0)
          <div class="text-center p-2 bg-orange-50 rounded-lg">
            <p class="text-xs text-gray-500">{{ $label }}</p>
            <p class="text-sm font-semibold text-orange-700">TZS {{ number_format($amt) }}</p>
          </div>
          @endif
        @endforeach
        @if($byCategory->isEmpty())
          <p class="col-span-4 text-sm text-gray-400">No expenses recorded</p>
        @endif
      </div>
    </div>
  </div>

  <!-- Expenses Table -->
  <div class="bg-white rounded-xl border">
    <div class="p-4 border-b">
      <h2 class="text-base font-semibold text-gray-900">Expense Records</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse($expenses as $exp)
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-600">{{ $exp->expense_date->format('M d, Y') }}</td>
            <td class="px-4 py-3 text-sm">
              <span class="px-2 py-0.5 bg-orange-100 text-orange-700 text-xs rounded-full font-medium">
                {{ \App\Models\AdminExpense::$categories[$exp->category] ?? ucfirst($exp->category) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-800">{{ $exp->description }}</td>
            <td class="px-4 py-3 text-sm font-semibold text-red-600">TZS {{ number_format($exp->amount) }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ ucfirst(str_replace('_',' ',$exp->payment_method)) }}</td>
            <td class="px-4 py-3 text-sm text-gray-400">{{ $exp->reference ?? '-' }}</td>
            <td class="px-4 py-3 text-right">
              <form method="POST" action="{{ route('admin.accounts.expenses.destroy', $exp) }}" onsubmit="return confirm('Delete this expense?')">
                @csrf @method('DELETE')
                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Delete</button>
              </form>
            </td>
          </tr>
          @empty
          <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No expenses in this period</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Expense Modal -->
<div id="addExpenseModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-xl bg-white">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Record Expense</h3>
      <button onclick="document.getElementById('addExpenseModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
    <form method="POST" action="{{ route('admin.accounts.expenses.store') }}" class="space-y-4">
      @csrf
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
          <select name="category" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            @foreach(\App\Models\AdminExpense::$categories as $key => $label)
              <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
          <input type="date" name="expense_date" required value="{{ now()->format('Y-m-d') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <input type="text" name="description" required placeholder="e.g. Google Ads - March 2026" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Amount (TZS)</label>
          <input type="number" name="amount" required min="1" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
          <select name="payment_method" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="mobile_money">Mobile Money</option>
            <option value="card">Card</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
          <input type="text" name="reference" placeholder="Receipt/ref no." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
          <input type="text" name="notes" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
      </div>
      <div class="flex justify-end space-x-3 pt-2">
        <button type="button" onclick="document.getElementById('addExpenseModal').classList.add('hidden')"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
          Save Expense
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
