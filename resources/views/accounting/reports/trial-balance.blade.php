@extends('layouts.user')

@section('title', 'Trial Balance')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <a href="{{ route('accounting.reports.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Reports</a>
                <h1 class="text-2xl font-bold text-gray-900 mt-2">Trial Balance</h1>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('accounting.reports.trial-balance', ['as_of_date' => $asOfDate, 'export' => 'pdf']) }}" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Export PDF
                </a>
                <a href="{{ route('accounting.reports.trial-balance', ['as_of_date' => $asOfDate, 'export' => 'excel']) }}" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Export Excel
                </a>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="bg-white rounded-lg shadow mb-6 p-4">
            <form action="{{ route('accounting.reports.trial-balance') }}" method="GET" class="flex items-end gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">As of Date</label>
                    <input type="date" name="as_of_date" value="{{ $asOfDate }}" 
                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Generate</button>
            </form>
        </div>

        <!-- Report -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 text-center">
                <h2 class="text-xl font-bold text-gray-900">Trial Balance</h2>
                <p class="text-gray-600">As of {{ \Carbon\Carbon::parse($report['as_of_date'])->format('F d, Y') }}</p>
            </div>

            @if(!$report['has_accounts'])
            <div class="p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No Chart of Accounts</h3>
                <p class="mt-1 text-sm text-gray-500">You need to set up your Chart of Accounts before generating financial reports.</p>
                <div class="mt-6">
                    <a href="{{ route('accounting.chart-of-accounts.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Set Up Chart of Accounts
                    </a>
                </div>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account Name</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($report['accounts'] as $account)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm font-mono text-gray-900">{{ $account['account_code'] }}</td>
                            <td class="px-6 py-3 text-sm text-gray-900">{{ $account['account_name'] }}</td>
                            <td class="px-6 py-3 text-sm text-right font-medium {{ $account['debit_balance'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                {{ $account['debit_balance'] > 0 ? number_format($account['debit_balance'], 2) : '-' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-right font-medium {{ $account['credit_balance'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                {{ $account['credit_balance'] > 0 ? number_format($account['credit_balance'], 2) : '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">No data available</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-100">
                        <tr class="font-bold">
                            <td colspan="2" class="px-6 py-4 text-right text-gray-900">TOTALS</td>
                            <td class="px-6 py-4 text-right text-gray-900">{{ number_format($report['total_debits'], 2) }}</td>
                            <td class="px-6 py-4 text-right text-gray-900">{{ number_format($report['total_credits'], 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="px-6 py-2 text-right text-sm">Difference:</td>
                            <td colspan="2" class="px-6 py-2 text-center {{ $report['is_balanced'] ? 'text-green-600' : 'text-red-600' }} font-medium">
                                {{ number_format(abs($report['total_debits'] - $report['total_credits']), 2) }}
                                @if($report['is_balanced'])
                                <span class="ml-2">✓ Balanced</span>
                                @else
                                <span class="ml-2">⚠ Not Balanced</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
