@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
<div x-data="reportsIndex()" x-init="init()" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Reports & Analytics</h1>
            <p class="mt-1 text-sm text-gray-500">Comprehensive financial and operational reports</p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3">
            <button @click="exportAllReports()" 
                    class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export Reports
            </button>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Report Filters</h3>
            
            <!-- Duration Quick Select -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Quick Duration</label>
                <div class="flex flex-wrap gap-2">
                    <button @click="setQuickRangeBtn('today')" :class="quickRange === 'today' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors">Today</button>
                    <button @click="setQuickRangeBtn('yesterday')" :class="quickRange === 'yesterday' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors">Yesterday</button>
                    <button @click="setQuickRangeBtn('this_week')" :class="quickRange === 'this_week' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors">This Week</button>
                    <button @click="setQuickRangeBtn('last_week')" :class="quickRange === 'last_week' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors">Last Week</button>
                    <button @click="setQuickRangeBtn('this_month')" :class="quickRange === 'this_month' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors">This Month</button>
                    <button @click="setQuickRangeBtn('last_month')" :class="quickRange === 'last_month' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors">Last Month</button>
                    <button @click="setQuickRangeBtn('this_quarter')" :class="quickRange === 'this_quarter' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors">This Quarter</button>
                    <button @click="setQuickRangeBtn('this_year')" :class="quickRange === 'this_year' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors">This Year</button>
                    <button @click="setQuickRangeBtn('last_year')" :class="quickRange === 'last_year' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors">Last Year</button>
                </div>
            </div>

            <!-- Date Range & Filters -->
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" 
                           id="date_from"
                           x-model="dateRange.from"
                           @change="quickRange = ''; loadReports()"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" 
                           id="date_to"
                           x-model="dateRange.to"
                           @change="quickRange = ''; loadReports()"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                </div>
                <div>
                    <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                    <select id="branch_id" 
                            x-model="filters.branch_id"
                            @change="loadReports()"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        <option value="">All Branches</option>
                        @foreach(\App\Models\Branch::where('tenant_id', auth()->user()->tenant_id)->get() as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="staff_id" class="block text-sm font-medium text-gray-700 mb-1">Loan Officer</label>
                    <select id="staff_id" 
                            x-model="filters.staff_id"
                            @change="loadReports()"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        <option value="">All Staff</option>
                        @foreach(\App\Models\User::where('tenant_id', auth()->user()->tenant_id)->whereIn('role', ['admin', 'loan_officer', 'manager'])->get() as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Loan Product</label>
                    <select id="product_id" 
                            x-model="filters.product_id"
                            @change="loadReports()"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        <option value="">All Products</option>
                        @foreach(\App\Models\LoanProduct::where('tenant_id', auth()->user()->tenant_id)->where('is_active', true)->get() as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button @click="loadReports()" 
                            class="w-full inline-flex justify-center items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Apply Filters
                    </button>
                </div>
            </div>
            
            <!-- Active Filters Display -->
            <div class="mt-4 flex flex-wrap gap-2" x-show="hasActiveFilters()">
                <span class="text-sm text-gray-500">Active filters:</span>
                <span x-show="dateRange.from" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <span x-text="dateRange.from + ' to ' + dateRange.to"></span>
                </span>
                <span x-show="filters.branch_id" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Branch selected
                </span>
                <span x-show="filters.staff_id" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    Staff selected
                </span>
                <span x-show="filters.product_id" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                    Product selected
                </span>
                <button @click="clearFilters()" class="text-xs text-red-600 hover:text-red-800 underline">Clear All</button>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500">Total Disbursed</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="formatCurrency(metrics.total_disbursed || 0)"></p>
                        <p class="text-sm text-gray-600" x-text="(metrics.disbursed_loans_count || 0) + ' loans'"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500">Collections</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="formatCurrency(metrics.total_collections || 0)"></p>
                        <p class="text-sm text-gray-600" x-text="(metrics.collection_rate || 0) + '% collection rate'"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500">Outstanding</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="formatCurrency(metrics.outstanding_amount || 0)"></p>
                        <p class="text-sm text-gray-600" x-text="(metrics.active_loans_count || 0) + ' active loans'"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500">Overdue</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="formatCurrency(metrics.overdue_amount || 0)"></p>
                        <p class="text-sm text-gray-600" x-text="(metrics.overdue_loans_count || 0) + ' overdue loans'"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Loan Disbursement Trends -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Loan Disbursement Trends</h3>
            </div>
            <div class="p-6">
                <canvas id="disbursementChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Collection Performance -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Collection Performance</h3>
            </div>
            <div class="p-6">
                <canvas id="collectionChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Portfolio Quality -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Portfolio Quality</h3>
            </div>
            <div class="p-6">
                <canvas id="portfolioChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Loan Products Performance -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Loan Products Performance</h3>
            </div>
            <div class="p-6">
                <canvas id="productsChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick Navigation to Report Dashboards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <a href="{{ route('reports.loan-portfolio') }}"
           class="flex flex-col items-center gap-2 p-4 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md hover:border-blue-400 transition-all group">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center group-hover:bg-blue-200">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 text-center">Loan Portfolio</span>
        </a>
        <a href="{{ route('reports.arrears-aging') }}"
           class="flex flex-col items-center gap-2 p-4 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md hover:border-red-400 transition-all group">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center group-hover:bg-red-200">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 text-center">Arrears Aging</span>
        </a>
        <a href="{{ route('reports.profit-loss') }}"
           class="flex flex-col items-center gap-2 p-4 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md hover:border-emerald-400 transition-all group">
            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center group-hover:bg-emerald-200">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 text-center">Profit & Loss</span>
        </a>
        <a href="{{ route('reports.collections') }}"
           class="flex flex-col items-center gap-2 p-4 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md hover:border-yellow-400 transition-all group">
            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center group-hover:bg-yellow-200">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 text-center">Collections</span>
        </a>
        <a href="{{ route('reports.par-report') }}"
           class="flex flex-col items-center gap-2 p-4 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md hover:border-orange-400 transition-all group">
            <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center group-hover:bg-orange-200">
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 text-center">PAR Analysis</span>
        </a>
        <a href="{{ route('reports.client-analysis') }}"
           class="flex flex-col items-center gap-2 p-4 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md hover:border-purple-400 transition-all group">
            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center group-hover:bg-purple-200">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 text-center">Borrower Analysis</span>
        </a>
    </div>

    <!-- Loading Modal -->
    <div x-show="loading" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="text-center">
                        <svg class="animate-spin mx-auto h-12 w-12 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg leading-6 font-medium text-gray-900">
                            Generating Report
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Please wait while we prepare your report...
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function reportsIndex() {
    return {
        metrics: {},
        loading: false,
        quickRange: 'this_month',
        dateRange: {
            from: '',
            to: ''
        },
        filters: {
            branch_id: '',
            staff_id: '',
            product_id: ''
        },
        charts: {
            disbursement: null,
            collection: null,
            portfolio: null,
            products: null
        },

        init() {
            this.setQuickRange();
            this.loadReports();
        },

        setQuickRangeBtn(range) {
            this.quickRange = range;
            this.setQuickRange();
            this.loadReports();
        },

        setQuickRange() {
            const today = new Date();
            const ranges = {
                today: {
                    from: today.toISOString().split('T')[0],
                    to: today.toISOString().split('T')[0]
                },
                yesterday: {
                    from: new Date(today.getTime() - 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                    to: new Date(today.getTime() - 24 * 60 * 60 * 1000).toISOString().split('T')[0]
                },
                this_week: {
                    from: new Date(today.getTime() - (today.getDay() * 24 * 60 * 60 * 1000)).toISOString().split('T')[0],
                    to: today.toISOString().split('T')[0]
                },
                last_week: {
                    from: new Date(today.getTime() - ((today.getDay() + 7) * 24 * 60 * 60 * 1000)).toISOString().split('T')[0],
                    to: new Date(today.getTime() - (today.getDay() * 24 * 60 * 60 * 1000)).toISOString().split('T')[0]
                },
                this_month: {
                    from: new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0],
                    to: today.toISOString().split('T')[0]
                },
                last_month: {
                    from: new Date(today.getFullYear(), today.getMonth() - 1, 1).toISOString().split('T')[0],
                    to: new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0]
                },
                this_quarter: {
                    from: new Date(today.getFullYear(), Math.floor(today.getMonth() / 3) * 3, 1).toISOString().split('T')[0],
                    to: today.toISOString().split('T')[0]
                },
                this_year: {
                    from: new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0],
                    to: today.toISOString().split('T')[0]
                },
                last_year: {
                    from: new Date(today.getFullYear() - 1, 0, 1).toISOString().split('T')[0],
                    to: new Date(today.getFullYear() - 1, 11, 31).toISOString().split('T')[0]
                }
            };

            if (this.quickRange && ranges[this.quickRange]) {
                this.dateRange = ranges[this.quickRange];
            }
        },

        hasActiveFilters() {
            return this.dateRange.from || this.filters.branch_id || this.filters.staff_id || this.filters.product_id;
        },

        clearFilters() {
            this.filters = { branch_id: '', staff_id: '', product_id: '' };
            this.quickRange = 'this_month';
            this.setQuickRange();
            this.loadReports();
        },

        async loadReports() {
            try {
                const params = new URLSearchParams({
                    from: this.dateRange.from,
                    to: this.dateRange.to,
                    branch_id: this.filters.branch_id,
                    staff_id: this.filters.staff_id,
                    product_id: this.filters.product_id
                });

                const response = await fetch(`/api/reports/dashboard?${params}`);
                const data = await response.json();
                
                this.metrics = data.metrics || {};
                this.initializeCharts(data.charts || {});
            } catch (error) {
                console.error('Error loading reports:', error);
            }
        },

        initializeCharts(chartData) {
            // Destroy existing charts
            Object.values(this.charts).forEach(chart => {
                if (chart) chart.destroy();
            });

            // Disbursement Chart
            const disbursementCtx = document.getElementById('disbursementChart').getContext('2d');
            this.charts.disbursement = new Chart(disbursementCtx, {
                type: 'line',
                data: {
                    labels: chartData.disbursement?.labels || [],
                    datasets: [{
                        label: 'Disbursements',
                        data: chartData.disbursement?.data || [],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('en-TZ', {
                                        style: 'currency',
                                        currency: 'TZS',
                                        minimumFractionDigits: 0
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            });

            // Collection Chart
            const collectionCtx = document.getElementById('collectionChart').getContext('2d');
            this.charts.collection = new Chart(collectionCtx, {
                type: 'bar',
                data: {
                    labels: chartData.collection?.labels || [],
                    datasets: [{
                        label: 'Collections',
                        data: chartData.collection?.data || [],
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('en-TZ', {
                                        style: 'currency',
                                        currency: 'TZS',
                                        minimumFractionDigits: 0
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            });

            // Portfolio Chart
            const portfolioCtx = document.getElementById('portfolioChart').getContext('2d');
            this.charts.portfolio = new Chart(portfolioCtx, {
                type: 'doughnut',
                data: {
                    labels: chartData.portfolio?.labels || ['Current', 'Overdue'],
                    datasets: [{
                        data: chartData.portfolio?.data || [0, 0],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderColor: [
                            'rgb(34, 197, 94)',
                            'rgb(239, 68, 68)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Products Chart
            const productsCtx = document.getElementById('productsChart').getContext('2d');
            this.charts.products = new Chart(productsCtx, {
                type: 'bar',
                data: {
                    labels: chartData.products?.labels || [],
                    datasets: [{
                        label: 'Loan Count',
                        data: chartData.products?.data || [],
                        backgroundColor: 'rgba(168, 85, 247, 0.8)',
                        borderColor: 'rgb(168, 85, 247)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        generateReport(reportType) {
            const routes = {
                'profit_loss': '/reports/profit-loss',
                'balance_sheet': '/reports/balance-sheet',
                'cash_flow': '/accounting/reports/cash-flow',
                'loan_portfolio': '/reports/loan-portfolio',
                'collection_summary': '/reports/collections',
                'client_analysis': '/reports/client-analysis',
                'arrears_aging': '/reports/arrears-aging',
                'par_analysis': '/reports/loan-portfolio',
                'regulatory_report': '/reports'
            };

            const route = routes[reportType] || '/reports';
            const params = new URLSearchParams({
                date_from: this.dateRange.from,
                date_to: this.dateRange.to,
                branch_id: this.filters.branch_id,
                staff_id: this.filters.staff_id,
                product_id: this.filters.product_id
            });

            window.location.href = `${route}?${params}`;
        },

        exportAllReports() {
            alert('To export reports, navigate to each report page and use your browser\'s Print function (Ctrl+P) to save as PDF.');
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('en-TZ', {
                style: 'currency',
                currency: 'TZS',
                minimumFractionDigits: 0
            }).format(amount);
        }
    }
}
</script>
@endpush
@endsection