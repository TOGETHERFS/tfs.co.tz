@extends('layouts.admin')

@section('title', 'SMS Reports')
@section('page-title', 'SMS Reports')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">SMS Reports</h1>
            <p class="mt-1 text-sm text-gray-500">Analytics and usage reports for SMS services</p>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">From</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                       class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">To</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                       class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                Apply
            </button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                <p class="text-3xl font-bold text-green-600">TZS {{ number_format($revenue) }}</p>
                <p class="text-xs text-gray-400 mt-1">From package purchases</p>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <p class="text-sm font-medium text-gray-500">Total Messages</p>
                <p class="text-3xl font-bold text-blue-600">{{ number_format($messageStats->sum('count')) }}</p>
                <p class="text-xs text-gray-400 mt-1">In selected period</p>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <p class="text-sm font-medium text-gray-500">Total SMS Credits</p>
                <p class="text-3xl font-bold text-purple-600">{{ number_format($messageStats->sum('total_sms')) }}</p>
                <p class="text-xs text-gray-400 mt-1">Units consumed</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Message Status Breakdown -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Message Status</h3>
            </div>
            <div class="p-6">
                @php
                    $statusGroups = $messageStats->groupBy('status');
                    $totalMessages = $messageStats->sum('count');
                @endphp
                <div class="space-y-4">
                    @foreach(['delivered' => 'green', 'sent' => 'blue', 'failed' => 'red', 'queued' => 'yellow'] as $status => $color)
                        @php
                            $count = $statusGroups->get($status)?->sum('count') ?? 0;
                            $percent = $totalMessages > 0 ? ($count / $totalMessages) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 capitalize">{{ $status }}</span>
                                <span class="font-medium">{{ number_format($count) }} ({{ number_format($percent, 1) }}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-{{ $color }}-500 h-2 rounded-full" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Top Tenants by Usage -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Top Tenants by Usage</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tenant</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Messages</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SMS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($tenantUsage as $index => $usage)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $usage->tenant->name ?? 'Tenant #' . $usage->tenant_id }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ number_format($usage->message_count) }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ number_format($usage->total_sms) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Daily Breakdown -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Daily Message Volume</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Delivered</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sent</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Failed</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @php
                        $dailyStats = $messageStats->groupBy('date');
                    @endphp
                    @forelse($dailyStats as $date => $stats)
                        <tr>
                            <td class="px-6 py-3 text-sm text-gray-900">{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</td>
                            <td class="px-6 py-3 text-sm text-right text-green-600">{{ number_format($stats->where('status', 'delivered')->sum('count')) }}</td>
                            <td class="px-6 py-3 text-sm text-right text-blue-600">{{ number_format($stats->where('status', 'sent')->sum('count')) }}</td>
                            <td class="px-6 py-3 text-sm text-right text-red-600">{{ number_format($stats->where('status', 'failed')->sum('count')) }}</td>
                            <td class="px-6 py-3 text-sm text-right font-medium text-gray-900">{{ number_format($stats->sum('count')) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No data for selected period</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
