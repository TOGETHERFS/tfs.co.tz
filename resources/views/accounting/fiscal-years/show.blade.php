@extends('layouts.user')

@section('title', 'Fiscal Year Details')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('accounting.fiscal-years.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Fiscal Years</a>
            <div class="mt-2 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $fiscalYear->name }}</h1>
                    <p class="text-sm text-gray-500">
                        {{ \Carbon\Carbon::parse($fiscalYear->start_date)->format('M d, Y') }} - 
                        {{ \Carbon\Carbon::parse($fiscalYear->end_date)->format('M d, Y') }}
                    </p>
                </div>
                <div class="flex space-x-3">
                    @if(!$fiscalYear->is_closed)
                    <form action="{{ route('accounting.fiscal-years.close', $fiscalYear) }}" method="POST" onsubmit="return confirm('Are you sure you want to close this fiscal year? This will lock all periods.');">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Close Fiscal Year
                        </button>
                    </form>
                    @else
                    <form action="{{ route('accounting.fiscal-years.reopen', $fiscalYear) }}" method="POST" onsubmit="return confirm('Are you sure you want to reopen this fiscal year?');">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Reopen Fiscal Year
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
        @endif

        <!-- Fiscal Year Status -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Fiscal Year Status</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <p class="text-lg font-semibold {{ $fiscalYear->is_closed ? 'text-red-600' : 'text-green-600' }}">
                        {{ $fiscalYear->is_closed ? 'Closed' : 'Active' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Periods</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $fiscalYear->periods->count() }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Open Periods</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $fiscalYear->periods->where('is_closed', false)->count() }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Closed Periods</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $fiscalYear->periods->where('is_closed', true)->count() }}</p>
                </div>
            </div>
        </div>

        <!-- Accounting Periods -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Accounting Periods</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Closed By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Closed At</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($fiscalYear->periods->sortBy('start_date') as $period)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $period->period_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($period->start_date)->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($period->end_date)->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($period->is_closed)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Closed</span>
                                @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Open</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $period->closedBy->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $period->closed_at ? \Carbon\Carbon::parse($period->closed_at)->format('M d, Y H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @if(!$fiscalYear->is_closed)
                                    @if(!$period->is_closed)
                                    <form action="{{ route('accounting.periods.close', $period) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to close this period?');">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-900">Close</button>
                                    </form>
                                    @else
                                    <form action="{{ route('accounting.periods.reopen', $period) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to reopen this period?');">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900">Reopen</button>
                                    </form>
                                    @endif
                                @else
                                <span class="text-gray-400">Locked</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                No accounting periods found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
