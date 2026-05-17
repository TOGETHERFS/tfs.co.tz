@extends('layouts.user')

@section('title', 'Profit & Loss Statement')

@section('content')
<div class="py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <a href="{{ route('accounting.reports.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Reports</a>
                <h1 class="text-2xl font-bold text-gray-900 mt-2">Profit & Loss Statement</h1>
            </div>
            <a href="{{ route('accounting.reports.income-statement', ['start_date' => $startDate, 'end_date' => $endDate, 'export' => 'pdf']) }}" 
                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Export PDF
            </a>
        </div>

        <!-- Date Filter -->
        <div class="bg-white rounded-lg shadow mb-6 p-4">
            <form action="{{ route('accounting.reports.income-statement') }}" method="GET" class="flex flex-wrap items-end gap-4">
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
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Generate</button>
                <div class="flex gap-2">
                    <a href="{{ route('accounting.reports.income-statement', ['start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" 
                        class="px-3 py-2 text-sm text-blue-600 hover:text-blue-800">This Month</a>
                    <a href="{{ route('accounting.reports.income-statement', ['start_date' => now()->startOfYear()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" 
                        class="px-3 py-2 text-sm text-blue-600 hover:text-blue-800">YTD</a>
                </div>
            </form>
        </div>

        <!-- Report -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 text-center">
                <h2 class="text-xl font-bold text-gray-900">Income Statement</h2>
                <p class="text-gray-600">
                    {{ \Carbon\Carbon::parse($report['period_start'])->format('F d, Y') }} - 
                    {{ \Carbon\Carbon::parse($report['period_end'])->format('F d, Y') }}
                </p>
            </div>

            <div class="p-6">
                <!-- Income -->
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b-2 border-green-600">INCOME</h3>
                    <table class="w-full">
                        <tbody>
                            @foreach($report['income']['accounts'] as $account)
                            <tr>
                                <td class="py-2 text-gray-700">{{ $account['account_name'] }}</td>
                                <td class="py-2 text-right font-medium text-gray-900 w-40">{{ number_format($account['balance'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-300">
                                <td class="py-3 font-bold text-gray-900">Total Income</td>
                                <td class="py-3 text-right font-bold text-green-600">{{ number_format($report['income']['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Expenses -->
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b-2 border-red-600">EXPENSES</h3>
                    <table class="w-full">
                        <tbody>
                            @foreach($report['expenses']['accounts'] as $account)
                            <tr>
                                <td class="py-2 text-gray-700">{{ $account['account_name'] }}</td>
                                <td class="py-2 text-right font-medium text-gray-900 w-40">{{ number_format($account['balance'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-300">
                                <td class="py-3 font-bold text-gray-900">Total Expenses</td>
                                <td class="py-3 text-right font-bold text-red-600">{{ number_format($report['expenses']['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Net Income -->
                <div class="bg-gray-100 rounded-lg p-6 text-center">
                    <p class="text-lg text-gray-600 mb-2">{{ $report['is_profitable'] ? 'Net Profit' : 'Net Loss' }}</p>
                    <p class="text-4xl font-bold {{ $report['is_profitable'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format(abs($report['net_income']), 2) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
