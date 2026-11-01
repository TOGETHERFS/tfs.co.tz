@extends('layouts.app')
@section('title', 'Member Statement')
@push('styles')
<style>
@media print { .no-print { display:none !important; } .print-only { display:block !important; } body { background:white !important; } @page { margin:1.5cm; size:A4; } }
.print-only { display:none; }
input[type="date"] { background-color:#ffffff !important; color:#111827 !important; color-scheme:light; }
</style>
@endpush

@section('content')
<div class="space-y-6">

    <!-- Print Header -->
    @if($client)
    <div class="print-only mb-6">
        <div class="text-center mb-4">
            <h1 class="text-2xl font-bold">{{ auth()->user()->tenant->name ?? 'Microfinance' }}</h1>
            <p class="text-xl font-semibold mt-1">MEMBER STATEMENT</p>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm border-t border-b border-gray-300 py-3 mt-3">
            <div>
                <p><strong>Member:</strong> {{ $client->full_name }}</p>
                <p><strong>Member No:</strong> {{ $client->client_number ?? $client->id }}</p>
            </div>
            <div class="text-right">
                <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
                <p><strong>Generated:</strong> {{ now()->format('d M Y H:i') }}</p>
            </div>
        </div>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reports</a>
                <span class="text-gray-400">/</span>
                <span class="text-sm font-medium text-gray-900">Member Statement</span>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Member Statement</h1>
            <p class="mt-1 text-sm text-gray-500">Comprehensive repayment history for a member across all loans</p>
        </div>
        @if($client)
        <button onclick="window.print()" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 no-print">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print Statement
        </button>
        @endif
    </div>

    <!-- Selector Form -->
    <form method="GET" class="bg-white rounded-lg border border-gray-200 p-5 no-print">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-700 mb-1">Member</label>
                <select name="client_id" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="">— Select Member —</option>
                    @foreach($clients as $c)
                    <option value="{{ $c->id }}" @selected($clientId == $c->id)>{{ $c->full_name }}</option>
                    @endforeach
                </select>
            </div>
            @if($client && $loans->count() > 1)
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Loan (optional)</label>
                <select name="loan_id" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Loans</option>
                    @foreach($loans as $l)
                    <option value="{{ $l->id }}" @selected($loanId == $l->id)>{{ $l->loan_number }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Generate</button>
        </div>
    </form>

    @if($client)
    <!-- Member Summary -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-xs text-gray-500 uppercase">Member</p>
                <p class="font-bold text-lg mt-0.5">{{ $client->full_name }}</p>
                <p class="text-xs text-gray-400">{{ $client->phone ?? '' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Total Loans</p>
                <p class="font-semibold mt-1">{{ $loans->count() }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Payments in Period</p>
                <p class="font-semibold mt-1">{{ $repayments->count() }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Total Paid in Period</p>
                <p class="font-bold text-green-600 text-lg mt-0.5">TZS {{ number_format($repayments->sum('amount'), 2) }}</p>
            </div>
        </div>
    </div>

    @if($loans->count())
    <!-- Loan Portfolio Summary -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-700">Loan Portfolio</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Loan No</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Principal</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total Repayable</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total Paid</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Outstanding</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Disbursed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($loans as $l)
                    @php
                        $totalPaid = $l->repayments->sum('amount');
                        $outstanding = max(0, (float)$l->total_amount - $totalPaid);
                        $statusBadge = match($l->status) {
                            'disbursed','active' => 'bg-green-100 text-green-700',
                            'partially_paid' => 'bg-yellow-100 text-yellow-700',
                            'completed' => 'bg-blue-100 text-blue-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <tr class="{{ $loanId == $l->id ? 'bg-blue-50' : 'hover:bg-gray-50' }}">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-blue-600">{{ $l->loan_number }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($l->principal, 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($l->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right text-green-600 font-semibold">{{ number_format($totalPaid, 2) }}</td>
                        <td class="px-4 py-3 text-right {{ $outstanding > 0 ? 'text-orange-600 font-semibold' : 'text-gray-500' }}">{{ number_format($outstanding, 2) }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusBadge }}">{{ ucfirst(str_replace('_',' ',$l->status)) }}</span></td>
                        <td class="px-4 py-3 text-xs">{{ optional($l->disbursed_at)->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Payment History -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-700">
                Payment History: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                @if($selectedLoan) <span class="font-normal text-gray-400">({{ $selectedLoan->loan_number }} only)</span> @endif
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Receipt No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Loan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Method</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Notes</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Processed By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($repayments as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ optional($r->payment_date)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">{{ $r->receipt_number }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ optional($r->loan)->loan_number ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">{{ ucfirst(str_replace('_',' ',$r->payment_method)) }}</span></td>
                        <td class="px-4 py-3 text-right font-semibold text-green-600">{{ number_format($r->amount, 2) }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $r->notes ?? '—' }}</td>
                        <td class="px-4 py-3">{{ optional($r->user)->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No payments recorded in this period.</td></tr>
                    @endforelse
                </tbody>
                @if($repayments->count())
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right text-gray-700">Total Paid in Period:</td>
                        <td class="px-4 py-3 text-right text-green-600">{{ number_format($repayments->sum('amount'), 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    @else
    <div class="bg-white rounded-lg border border-gray-200 p-12 text-center text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <p class="text-sm">Select a member above to generate their statement.</p>
    </div>
    @endif

</div>
@endsection
