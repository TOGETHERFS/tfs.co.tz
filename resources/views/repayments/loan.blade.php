@extends('layouts.app')

@section('title', 'Loan Repayments')
@section('page-title', 'Loan Repayments')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Repayments for Loan #{{ $loan->loan_number }}</h1>
            <p class="mt-1 text-sm text-gray-500">View and manage repayments for this loan</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('loans.show', $loan->id) }}" class="px-4 py-2 text-sm rounded-md font-semibold" style="background:#33383f;color:#f5c518;border:1px solid #f5c518;">
                Back to Loan
            </a>
            <a href="{{ route('repayments.create', ['loan_id' => $loan->id]) }}" class="px-4 py-2 text-sm font-semibold text-white rounded-md" style="background:#f5c518;color:#23272f;">
                Record Payment
            </a>
        </div>
    </div>

    <!-- Loan Summary -->
    <div class="overflow-hidden rounded-xl" style="background:#23272f;border:1px solid #33383f;">
        <div class="p-5">
            <h3 class="text-xs font-bold uppercase tracking-widest mb-4" style="color:#f5c518;">Loan Summary</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="font-semibold text-xs uppercase" style="color:#94a3b8;">Borrower:</span>
                    <p class="mt-1 font-medium" style="color:#e2e8f0;">{{ $loan->client->full_name ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="font-semibold text-xs uppercase" style="color:#94a3b8;">Principal:</span>
                    <p class="mt-1 font-medium" style="color:#e2e8f0;">TZS {{ number_format($loan->principal, 2) }}</p>
                </div>
                <div>
                    <span class="font-semibold text-xs uppercase" style="color:#94a3b8;">Total Amount:</span>
                    <p class="mt-1 font-medium" style="color:#e2e8f0;">TZS {{ number_format($loan->total_amount, 2) }}</p>
                </div>
                <div>
                    <span class="font-semibold text-xs uppercase" style="color:#94a3b8;">Outstanding:</span>
                    <p class="mt-1 font-bold" style="color:#f87171;">TZS {{ number_format($loan->outstanding_balance, 2) }}</p>
                </div>
                <div>
                    <span class="font-semibold text-xs uppercase" style="color:#94a3b8;">Repayment Schedule:</span>
                    <p class="mt-1 font-medium capitalize" style="color:#e2e8f0;">{{ $loan->repayment_schedule ?? 'Monthly' }}</p>
                </div>
                <div>
                    <span class="font-semibold text-xs uppercase" style="color:#94a3b8;">Status:</span>
                    <p class="mt-1">
                        <span style="
                            background: {{ $loan->status === 'active' ? '#16a34a' : ($loan->status === 'disbursed' ? '#2563eb' : ($loan->status === 'completed' ? '#4b5563' : '#b45309')) }};
                            color:#fff;padding:2px 10px;border-radius:4px;font-size:0.72rem;font-weight:700;letter-spacing:0.08em;display:inline-block;">
                            {{ ucfirst($loan->status) }}
                        </span>
                    </p>
                </div>
                <div>
                    <span class="font-semibold text-xs uppercase" style="color:#94a3b8;">Total Paid:</span>
                    <p class="mt-1 font-bold" style="color:#4ade80;">TZS {{ number_format($loan->repayments->sum('amount'), 2) }}</p>
                </div>
                <div>
                    <span class="font-semibold text-xs uppercase" style="color:#94a3b8;">Payments Made:</span>
                    <p class="mt-1 font-medium" style="color:#e2e8f0;">{{ $loan->repayments->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Schedule -->
    <div class="overflow-hidden rounded-xl" style="background:#23272f;border:1px solid #33383f;">
        <div class="px-5 pt-5 pb-2">
            <h3 class="text-xs font-bold uppercase tracking-widest mb-4" style="color:#f5c518;">Payment Schedule</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full" style="border-collapse:collapse;">
                <thead>
                    <tr>
                        <th class="px-5 py-4 text-left text-xs font-bold uppercase" style="background:#3a3f47;color:#fff;border-bottom:2px solid #23272f;">#</th>
                        <th class="px-5 py-4 text-left text-xs font-bold uppercase" style="background:#3a3f47;color:#fff;border-bottom:2px solid #23272f;">Due Date</th>
                        <th class="px-5 py-4 text-right text-xs font-bold uppercase" style="background:#4a7c3f;color:#fff;border-bottom:2px solid #23272f;">Principal</th>
                        <th class="px-5 py-4 text-right text-xs font-bold uppercase" style="background:#c4621a;color:#fff;border-bottom:2px solid #23272f;">Interest</th>
                        <th class="px-5 py-4 text-right text-xs font-bold uppercase" style="background:#3a3f47;color:#fff;border-bottom:2px solid #23272f;">Total Due</th>
                        <th class="px-5 py-4 text-right text-xs font-bold uppercase" style="background:#3a3f47;color:#fff;border-bottom:2px solid #23272f;">Paid</th>
                        <th class="px-5 py-4 text-center text-xs font-bold uppercase" style="background:#3a3f47;color:#fff;border-bottom:2px solid #23272f;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loan->schedules as $i => $schedule)
                    <tr style="background:{{ $i % 2 === 0 ? '#2c3038' : '#23272f' }};">
                        <td class="px-5 py-3 text-sm font-bold" style="color:#f5c518;border-bottom:1px solid #33383f;">{{ $schedule->installment_number }}</td>
                        <td class="px-5 py-3 text-sm" style="color:#e2e8f0;border-bottom:1px solid #33383f;">{{ $schedule->due_date->format('d/m/Y') }}</td>
                        <td class="px-5 py-3 text-sm text-right" style="color:#e2e8f0;border-bottom:1px solid #33383f;">{{ number_format($schedule->principal_amount, 2) }}</td>
                        <td class="px-5 py-3 text-sm text-right" style="color:#e2e8f0;border-bottom:1px solid #33383f;">{{ number_format($schedule->interest_amount, 2) }}</td>
                        <td class="px-5 py-3 text-sm text-right font-semibold" style="color:#fff;border-bottom:1px solid #33383f;">{{ number_format($schedule->total_amount, 2) }}</td>
                        <td class="px-5 py-3 text-sm text-right font-medium" style="color:#4ade80;border-bottom:1px solid #33383f;">{{ number_format($schedule->paid_amount, 2) }}</td>
                        <td class="px-5 py-3 text-center" style="border-bottom:1px solid #33383f;">
                            <span style="
                                background: {{ $schedule->status === 'paid' ? '#16a34a' : ($schedule->status === 'partial' ? '#c4621a' : ($schedule->status === 'overdue' ? '#dc2626' : '#4b5563')) }};
                                color:#fff;padding:2px 10px;border-radius:4px;font-size:0.72rem;font-weight:700;letter-spacing:0.08em;display:inline-block;">
                                {{ ucfirst($schedule->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-sm" style="color:#94a3b8;background:#23272f;">
                            No payment schedule found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Repayment History -->
    <div class="overflow-hidden rounded-xl" style="background:#23272f;border:1px solid #33383f;">
        <div class="px-5 pt-5 pb-2">
            <h3 class="text-xs font-bold uppercase tracking-widest mb-4" style="color:#f5c518;">Repayment History</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full" style="border-collapse:collapse;">
                <thead>
                    <tr style="background:#2d3348;">
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase" style="color:#f5c518;border-bottom:2px solid #33383f;">Receipt #</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase" style="color:#f5c518;border-bottom:2px solid #33383f;">Date</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase" style="color:#f5c518;border-bottom:2px solid #33383f;">Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase" style="color:#f5c518;border-bottom:2px solid #33383f;">Method</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase" style="color:#f5c518;border-bottom:2px solid #33383f;">Reference</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase" style="color:#f5c518;border-bottom:2px solid #33383f;">Processed By</th>
                        <th class="px-5 py-3 text-center text-xs font-bold uppercase" style="color:#f5c518;border-bottom:2px solid #33383f;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loan->repayments as $i => $repayment)
                    <tr style="background:{{ $i % 2 === 0 ? '#2c3038' : '#23272f' }};">
                        <td class="px-5 py-3 text-sm font-bold" style="color:#f5c518;border-bottom:1px solid #33383f;">{{ $repayment->receipt_number }}</td>
                        <td class="px-5 py-3 text-sm" style="color:#e2e8f0;border-bottom:1px solid #33383f;">{{ $repayment->payment_date->format('d/m/Y') }}</td>
                        <td class="px-5 py-3 text-sm text-right font-semibold" style="color:#fff;border-bottom:1px solid #33383f;">TZS {{ number_format($repayment->amount, 2) }}</td>
                        <td class="px-5 py-3 text-sm capitalize" style="color:#e2e8f0;border-bottom:1px solid #33383f;">{{ str_replace('_', ' ', $repayment->payment_method) }}</td>
                        <td class="px-5 py-3 text-sm" style="color:#94a3b8;border-bottom:1px solid #33383f;">{{ $repayment->reference ?? '-' }}</td>
                        <td class="px-5 py-3 text-sm" style="color:#94a3b8;border-bottom:1px solid #33383f;">{{ $repayment->user->name ?? 'System' }}</td>
                        <td class="px-5 py-3 text-center" style="border-bottom:1px solid #33383f;">
                            <a href="{{ route('repayments.show', $repayment->id) }}" class="text-sm font-semibold" style="color:#f5c518;">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-sm" style="color:#94a3b8;background:#23272f;">
                            No repayments recorded yet
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($loan->repayments->count() > 0)
                <tfoot>
                    <tr style="background:#2d3348;">
                        <td colspan="2" class="px-5 py-3 text-sm font-bold" style="color:#f5c518;">Total Paid:</td>
                        <td class="px-5 py-3 text-sm font-bold text-right" style="color:#4ade80;">TZS {{ number_format($loan->repayments->sum('amount'), 2) }}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
