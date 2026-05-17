@extends('layouts.user')

@section('title', 'General Ledger')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('accounting.reports.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Reports</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">General Ledger</h1>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow mb-6 p-4">
            <form action="{{ route('accounting.reports.general-ledger') }}" method="GET" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account *</label>
                    <select name="account_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select Account</option>
                        @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ $accountId == $account->id ? 'selected' : '' }}>
                            {{ $account->account_code }} - {{ $account->account_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" 
                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" 
                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">View Ledger</button>
            </form>
        </div>

        @if($report)
        <!-- Report Header -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $report['account']['code'] }} - {{ $report['account']['name'] }}</h2>
                    <p class="text-sm text-gray-500">
                        @if($report['period_start'] && $report['period_end'])
                        {{ \Carbon\Carbon::parse($report['period_start'])->format('M d, Y') }} - {{ \Carbon\Carbon::parse($report['period_end'])->format('M d, Y') }}
                        @else
                        All Transactions
                        @endif
                    </p>
                </div>
                <a href="{{ route('accounting.reports.general-ledger', ['account_id' => $accountId, 'start_date' => $startDate, 'end_date' => $endDate, 'export' => 'pdf']) }}" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Export PDF
                </a>
            </div>

            <!-- Opening Balance -->
            <div class="px-6 py-3 bg-gray-50 border-b">
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Opening Balance:</span>
                    <span class="font-bold text-gray-900">{{ number_format($report['opening_balance'], 2) }}</span>
                </div>
            </div>

            <!-- Transactions -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entry #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($report['entries'] as $entry)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $entry['date'] }}</td>
                            <td class="px-6 py-3 text-sm text-blue-600 whitespace-nowrap">{{ $entry['entry_number'] }}</td>
                            <td class="px-6 py-3 text-sm text-gray-700">{{ Str::limit($entry['description'], 50) }}</td>
                            <td class="px-6 py-3 text-sm text-right {{ $entry['debit'] > 0 ? 'text-gray-900 font-medium' : 'text-gray-400' }}">
                                {{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-right {{ $entry['credit'] > 0 ? 'text-gray-900 font-medium' : 'text-gray-400' }}">
                                {{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-right font-medium {{ $entry['balance'] < 0 ? 'text-red-600' : 'text-gray-900' }}">
                                {{ number_format($entry['balance'], 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">No transactions found for this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-100">
                        <tr class="font-bold">
                            <td colspan="3" class="px-6 py-3 text-right">Totals:</td>
                            <td class="px-6 py-3 text-right text-gray-900">{{ number_format($report['total_debits'], 2) }}</td>
                            <td class="px-6 py-3 text-right text-gray-900">{{ number_format($report['total_credits'], 2) }}</td>
                            <td class="px-6 py-3 text-right text-gray-900">{{ number_format($report['closing_balance'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @else
        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
            Select an account above to view its ledger.
        </div>
        @endif
    </div>
</div>
@endsection
