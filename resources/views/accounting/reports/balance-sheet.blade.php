@extends('layouts.user')

@section('title', 'Balance Sheet')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <a href="{{ route('accounting.reports.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Reports</a>
                <h1 class="text-2xl font-bold text-gray-900 mt-2">Balance Sheet</h1>
            </div>
            <a href="{{ route('accounting.reports.balance-sheet', ['as_of_date' => $asOfDate, 'export' => 'pdf']) }}" 
                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Export PDF
            </a>
        </div>

        <!-- Date Filter -->
        <div class="bg-white rounded-lg shadow mb-6 p-4">
            <form action="{{ route('accounting.reports.balance-sheet') }}" method="GET" class="flex items-end gap-4">
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
                <h2 class="text-xl font-bold text-gray-900">Statement of Financial Position</h2>
                <p class="text-gray-600">As of {{ \Carbon\Carbon::parse($report['as_of_date'])->format('F d, Y') }}</p>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Assets -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b-2 border-blue-600">ASSETS</h3>
                        <table class="w-full">
                            <tbody>
                                @foreach($report['assets']['accounts'] as $account)
                                <tr>
                                    <td class="py-1 text-sm text-gray-700">{{ $account['account_name'] }}</td>
                                    <td class="py-1 text-sm text-right font-medium text-gray-900">{{ number_format($account['balance'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-gray-300">
                                    <td class="py-2 font-bold text-gray-900">Total Assets</td>
                                    <td class="py-2 text-right font-bold text-blue-600">{{ number_format($report['assets']['total'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Liabilities & Equity -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b-2 border-red-600">LIABILITIES</h3>
                        <table class="w-full mb-6">
                            <tbody>
                                @foreach($report['liabilities']['accounts'] as $account)
                                <tr>
                                    <td class="py-1 text-sm text-gray-700">{{ $account['account_name'] }}</td>
                                    <td class="py-1 text-sm text-right font-medium text-gray-900">{{ number_format($account['balance'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-gray-200">
                                    <td class="py-2 font-medium text-gray-900">Total Liabilities</td>
                                    <td class="py-2 text-right font-medium text-red-600">{{ number_format($report['liabilities']['total'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>

                        <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b-2 border-green-600">EQUITY</h3>
                        <table class="w-full">
                            <tbody>
                                @foreach($report['equity']['accounts'] as $account)
                                <tr>
                                    <td class="py-1 text-sm text-gray-700">{{ $account['account_name'] }}</td>
                                    <td class="py-1 text-sm text-right font-medium text-gray-900">{{ number_format($account['balance'], 2) }}</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <td class="py-1 text-sm text-gray-700 italic">Retained Earnings (Current Period)</td>
                                    <td class="py-1 text-sm text-right font-medium {{ $report['equity']['retained_earnings'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($report['equity']['retained_earnings'], 2) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-gray-200">
                                    <td class="py-2 font-medium text-gray-900">Total Equity</td>
                                    <td class="py-2 text-right font-medium text-green-600">{{ number_format($report['equity']['total'], 2) }}</td>
                                </tr>
                                <tr class="border-t-2 border-gray-300">
                                    <td class="py-2 font-bold text-gray-900">Total Liabilities & Equity</td>
                                    <td class="py-2 text-right font-bold text-gray-900">{{ number_format($report['total_liabilities_and_equity'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Balance Check -->
                <div class="mt-6 pt-4 border-t text-center">
                    @if($report['is_balanced'])
                    <span class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Balance Sheet is Balanced (Assets = Liabilities + Equity)
                    </span>
                    @else
                    <span class="inline-flex items-center px-4 py-2 bg-red-100 text-red-800 rounded-full">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        Warning: Balance Sheet is NOT Balanced
                    </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
