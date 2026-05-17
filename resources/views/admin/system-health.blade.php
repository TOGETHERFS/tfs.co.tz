@extends('layouts.admin')

@section('title', 'System Health')
@section('page-title', 'System Health')

@section('content')
<div class="space-y-6" x-data="{ refreshing: false }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">System Health</h1>
            <p class="mt-1 text-sm text-gray-500">Monitor the health status of system components</p>
        </div>
        <button @click="refreshing = true; setTimeout(() => location.reload(), 500)" 
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 disabled:opacity-50"
                :disabled="refreshing">
            <svg class="w-4 h-4 mr-2" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span x-text="refreshing ? 'Refreshing...' : 'Refresh'"></span>
        </button>
    </div>

    <!-- Overall Status -->
    @php
        $allHealthy = $health['database']['status'] === 'healthy' 
                   && $health['storage']['status'] === 'healthy' 
                   && $health['cache']['status'] === 'healthy';
        $hasWarning = $health['storage']['status'] === 'warning';
    @endphp
    
    <div class="p-4 rounded-lg {{ $allHealthy ? 'bg-green-50 border border-green-200' : ($hasWarning ? 'bg-yellow-50 border border-yellow-200' : 'bg-red-50 border border-red-200') }}">
        <div class="flex items-center">
            @if($allHealthy)
                <svg class="w-8 h-8 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="text-lg font-medium text-green-800">All Systems Operational</h3>
                    <p class="text-sm text-green-600">All system components are functioning normally</p>
                </div>
            @elseif($hasWarning)
                <svg class="w-8 h-8 text-yellow-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <h3 class="text-lg font-medium text-yellow-800">System Warning</h3>
                    <p class="text-sm text-yellow-600">Some components need attention</p>
                </div>
            @else
                <svg class="w-8 h-8 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="text-lg font-medium text-red-800">System Issues Detected</h3>
                    <p class="text-sm text-red-600">One or more components are not functioning properly</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Health Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Database Health -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full {{ $health['database']['status'] === 'healthy' ? 'bg-green-100' : 'bg-red-100' }}">
                            <svg class="w-6 h-6 {{ $health['database']['status'] === 'healthy' ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Database</h3>
                            <p class="text-sm text-gray-500">MySQL Connection</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $health['database']['status'] === 'healthy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ ucfirst($health['database']['status']) }}
                    </span>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-sm text-gray-600">{{ $health['database']['message'] }}</p>
                </div>
            </div>
        </div>

        <!-- Storage Health -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full {{ $health['storage']['status'] === 'healthy' ? 'bg-green-100' : ($health['storage']['status'] === 'warning' ? 'bg-yellow-100' : 'bg-red-100') }}">
                            <svg class="w-6 h-6 {{ $health['storage']['status'] === 'healthy' ? 'text-green-600' : ($health['storage']['status'] === 'warning' ? 'text-yellow-600' : 'text-red-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Storage</h3>
                            <p class="text-sm text-gray-500">Disk Space</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $health['storage']['status'] === 'healthy' ? 'bg-green-100 text-green-800' : ($health['storage']['status'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ ucfirst($health['storage']['status']) }}
                    </span>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <div class="mb-2">
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>Used: {{ $health['storage']['used_percentage'] }}%</span>
                            <span>Free: {{ $health['storage']['free_space'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full {{ $health['storage']['used_percentage'] > 90 ? 'bg-red-500' : ($health['storage']['used_percentage'] > 75 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                 style="width: {{ min($health['storage']['used_percentage'], 100) }}%"></div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500">Total: {{ $health['storage']['total_space'] }}</p>
                </div>
            </div>
        </div>

        <!-- Cache Health -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full {{ $health['cache']['status'] === 'healthy' ? 'bg-green-100' : 'bg-red-100' }}">
                            <svg class="w-6 h-6 {{ $health['cache']['status'] === 'healthy' ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Cache</h3>
                            <p class="text-sm text-gray-500">Redis/File Cache</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $health['cache']['status'] === 'healthy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ ucfirst($health['cache']['status']) }}
                    </span>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-sm text-gray-600">{{ $health['cache']['message'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional System Info -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">System Information</h3>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">PHP Version</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ phpversion() }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Laravel Version</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ app()->version() }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Environment</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ ucfirst(app()->environment()) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Last Checked</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ now()->format('M d, Y H:i:s') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
