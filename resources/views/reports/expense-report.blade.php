@extends('layouts.app')
@section('title', 'Expense Report')
@push('styles')
<style>
@media print { .no-print { display:none !important; } .print-only { display:block !important; } body { background:white !important; } @page { margin:1cm; size:A4; } }
.print-only { display:none; }
input[type="date"] { background-color:#ffffff !important; color:#111827 !important; color-scheme:light; }
</style>
@endpush

@section('content')
<div class="space-y-6">

    <div class="print-only mb-4 text-center">
        <h1 class="text-2xl font-bold">{{ auth()->user()->tenant->name ?? 'Microfinance' }}</h1>
        <p class="text-lg font-semibold">Expense Report</p>
        <p class="text-sm text-gray-500">Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
        <p class="text-xs text-gray-400">Generated: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reports</a>
                <span class="text-gray-400">/</span>
                <span class="text-sm font-medium text-gray-900">Expense Report</span>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Expense Report</h1>
            <p class="mt-1 text-sm text-gray-500">Operational expenses by category and period</p>
        </div>
        <button onclick="window.print()" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 no-print">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print / PDF
        </button>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white rounded-lg border border-gray-200 p-5 no-print">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Category</label>
                <select name="category_id" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected($categoryId == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Ref / Description / Payee" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Apply Filters</button>
        </div>
    </form>

    <!-- Summary -->
    <div class="bg-white rounded-lg border border-red-200 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Expenses</p>
                <p class="text-3xl font-bold text-red-600 mt-1">TZS {{ number_format($totalAmount, 2) }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Records</p>
                <p class="text-2xl font-bold text-gray-700">{{ $expenses instanceof \Illuminate\Pagination\LengthAwarePaginator ? $expenses->total() : $expenses->count() }}</p>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Expense No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Payee</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Method</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($expenses as $e)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ optional($e->expense_date)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">{{ $e->expense_number }}</td>
                        <td class="px-4 py-3">{{ optional($e->category)->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $e->description ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $e->payee ?? '—' }}</td>
                        <td class="px-4 py-3">{{ ucfirst(str_replace('_',' ',$e->payment_method ?? '')) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-red-600">{{ number_format($e->amount, 2) }}</td>
                        <td class="px-4 py-3">{{ optional($e->createdBy)->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No expenses found for this period.</td></tr>
                    @endforelse
                </tbody>
                @if($expenses->count())
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-right text-gray-700">Page Total:</td>
                        <td class="px-4 py-3 text-right text-red-600">{{ number_format($expenses->sum('amount'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if($expenses instanceof \Illuminate\Pagination\LengthAwarePaginator && $expenses->hasPages())
        <div class="px-5 py-4 border-t border-gray-200 no-print">{{ $expenses->links() }}</div>
        @endif
    </div>

</div>
@endsection
