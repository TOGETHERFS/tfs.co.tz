@extends('layouts.app')

@section('title', 'Client Analysis Report')

@push('styles')
<style>
@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    body { background: white !important; }
    .shadow { box-shadow: none !important; }
    @page { margin: 1cm; size: A4; }
}
.print-only { display: none; }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Print Header -->
    <div class="print-only mb-4 text-center">
        <h1 class="text-2xl font-bold">{{ auth()->user()->tenant->name ?? 'Microfinance' }}</h1>
        <p class="text-lg">Client Analysis Report</p>
        <p class="text-sm text-gray-500">Period: {{ ($dateFrom instanceof \Carbon\Carbon ? $dateFrom : \Carbon\Carbon::parse($dateFrom))->format('M d, Y') }} - {{ ($dateTo instanceof \Carbon\Carbon ? $dateTo : \Carbon\Carbon::parse($dateTo))->format('M d, Y') }}</p>
        <p class="text-xs text-gray-400">Generated: {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <div class="flex justify-between items-start mb-6 no-print">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Client Analysis Report</h1>
            <p class="text-gray-600">Borrower demographics and performance analysis</p>
        </div>
        <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Print Report
        </button>
    </div>

    <!-- Date Filter -->
    <div class="bg-white rounded-lg shadow p-4 mb-6 no-print">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700">From Date</label>
                <input type="date" name="date_from" value="{{ $dateFrom instanceof \Carbon\Carbon ? $dateFrom->format('Y-m-d') : $dateFrom }}" 
                       class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">To Date</label>
                <input type="date" name="date_to" value="{{ $dateTo instanceof \Carbon\Carbon ? $dateTo->format('Y-m-d') : $dateTo }}" 
                       class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Filter
            </button>
            <a href="{{ route('reports.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50">Back</a>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Total Clients</h3>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($data['total_clients'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Active Borrowers</h3>
            <p class="text-2xl font-bold text-green-600">{{ number_format($data['active_borrowers'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">New Clients</h3>
            <p class="text-2xl font-bold text-purple-600">{{ number_format($data['new_clients'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Repeat Borrowers</h3>
            <p class="text-2xl font-bold text-orange-600">{{ number_format($data['repeat_borrowers'] ?? 0) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Gender Distribution -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Gender Distribution</h3>
            @if(!empty($data['gender_distribution']))
            <div class="space-y-3">
                @foreach($data['gender_distribution'] as $gender)
                <div class="flex items-center justify-between">
                    <span class="text-gray-600 capitalize">{{ $gender->gender ?? 'Unknown' }}</span>
                    <div class="flex items-center">
                        <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                            <div class="bg-blue-600 h-2 rounded-full" 
                                 style="width: {{ ($data['total_clients'] ?? 0) > 0 ? (($gender->count / $data['total_clients']) * 100) : 0 }}%"></div>
                        </div>
                        <span class="text-sm font-medium">{{ $gender->count ?? 0 }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 text-center py-4">No data available</p>
            @endif
        </div>

        <!-- Client Status -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Client Status</h3>
            @if(!empty($data['status_distribution']))
            <div class="space-y-3">
                @foreach($data['status_distribution'] as $status)
                <div class="flex items-center justify-between">
                    <span class="text-gray-600 capitalize">{{ $status->status ?? 'Unknown' }}</span>
                    <div class="flex items-center">
                        <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                            <div class="{{ $status->status === 'active' ? 'bg-green-600' : 'bg-gray-400' }} h-2 rounded-full" 
                                 style="width: {{ ($data['total_clients'] ?? 0) > 0 ? (($status->count / $data['total_clients']) * 100) : 0 }}%"></div>
                        </div>
                        <span class="text-sm font-medium">{{ $status->count ?? 0 }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 text-center py-4">No data available</p>
            @endif
        </div>
    </div>

    <!-- Top Borrowers -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Borrowers by Loan Volume</h3>
        @if(!empty($data['top_borrowers']))
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Loans</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Repaid</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($data['top_borrowers'] as $borrower)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $borrower->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ $borrower->loan_count ?? 0 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($borrower->total_amount ?? 0, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($borrower->total_repaid ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-gray-500 text-center py-4">No data available</p>
        @endif
    </div>
</div>
@endsection
