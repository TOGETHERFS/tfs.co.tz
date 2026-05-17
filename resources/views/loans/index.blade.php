@extends('layouts.app')

@section('title', __('messages.loans'))
@section('page-title', __('messages.loans'))

@section('content')
<div x-data="loansIndex(@js($stats), @js($loans), @js($loan_products))" x-init="init()" class="space-y-6">
    <!-- Success/Error Messages from Import -->
    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            <p class="text-sm font-medium">{{ __('messages.success_import') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('import_errors') && is_array(session('import_errors')) && count(session('import_errors')))
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded">
            <p class="text-sm font-medium">{{ __('messages.import_warnings') }}</p>
            <ul class="list-disc pl-5 text-sm mt-2 max-h-40 overflow-y-auto">
                @foreach (session('import_errors') as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Header with Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.loans') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.manage_loans') }}</p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3">
            <a href="{{ route('loans.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                {{ __('messages.new_loan') }}
            </a>
            <a href="{{ route('loans.import') }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v16h16V8l-6-4H4zm4 8h8M8 12v4m8-4v4" />
                </svg>
                {{ __('messages.import_loans') }}
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500">{{ __('messages.total_loans') }}</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="stats.total_loans || '0'"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500">{{ __('messages.active_loans') }}</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="stats.active_loans || '0'"></p>
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
                        <p class="text-sm font-medium text-gray-500">{{ __('messages.pending_loans') }}</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="stats.pending_loans || '0'"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500">{{ __('messages.total_outstanding') }}</p>
                        <p class="text-lg font-semibold text-gray-900" x-text="formatCurrency(stats.total_outstanding || 0)"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Loans (Top 5) -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">{{ __('messages.recent_loans') }}</h3>
            <a href="{{ route('loans.index') }}" class="text-primary-600 hover:text-primary-800 text-sm">{{ __('messages.view_all') }}</a>
        </div>
        <div class="p-6">
            <div x-show="recentLoans.length === 0" class="text-sm text-gray-500">{{ __('messages.no_recent_loans') }}</div>
            <div x-show="recentLoans.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="loan in recentLoans" :key="loan.id">
                    <div class="border border-gray-200 rounded-md p-4 hover:border-primary-300">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-900" x-text="loan.loan_number"></div>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                  :class="{
                                      'bg-yellow-100 text-yellow-800': loan.status === 'pending',
                                      'bg-green-100 text-green-800': loan.status === 'approved',
                                      'bg-blue-100 text-blue-800': loan.status === 'disbursed' || loan.status === 'active',
                                      'bg-gray-100 text-gray-800': loan.status === 'completed',
                                      'bg-red-100 text-red-800': loan.status === 'rejected' || loan.status === 'defaulted'
                                  }"
                                  x-text="(loan.status || '').charAt(0).toUpperCase() + (loan.status || '').slice(1)">
                            </span>
                        </div>
                        <div class="mt-2 text-sm text-gray-600" x-text="(loan.client?.first_name || '') + ' ' + (loan.client?.last_name || '')"></div>
                        <div class="mt-1 text-xs text-gray-500">
                            {{ __('messages.applied') }}: <span x-text="formatDate(loan.created_at)"></span>
                        </div>
                        <div class="mt-2 text-sm font-medium text-gray-900" x-text="formatCurrency(loan.principal)"></div>
                        <div class="mt-3">
                            <template x-if="loan?.id">
                                <a :href="'/loans/' + loan.id" class="text-primary-600 hover:text-primary-900 text-sm">{{ __('messages.view') }}</a>
                            </template>
                            <template x-if="!loan?.id">
                                <span class="text-gray-400 text-sm cursor-not-allowed">{{ __('messages.view') }}</span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.search') }}</label>
                    <div class="relative">
                        <input type="text" 
                               id="search"
                               x-model="filters.search"
                               @input.debounce.300ms="loadLoans()"
                               placeholder="{{ __('messages.search_placeholder') }}"
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.loan_status') }}</label>
                    <select id="status" x-model="filters.status" @change="loadLoans()"
                            class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">{{ __('messages.all') }}</option>
                        <option value="pending">{{ __('messages.pending') }}</option>
                        <option value="approved">{{ __('messages.approved') }}</option>
                        <option value="disbursed">{{ __('messages.disbursed') }}</option>
                        <option value="active">{{ __('messages.active') }}</option>
                        <option value="completed">{{ __('messages.completed') }}</option>
                        <option value="rejected">{{ __('messages.rejected') }}</option>
                        <option value="defaulted">{{ __('messages.defaulted') }}</option>
                    </select>
                </div>

                <!-- Product Filter -->
                <div>
                    <label for="product" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.product') }}</label>
                    <select id="product" x-model="filters.product_id" @change="loadLoans()"
                            class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">{{ __('messages.all') }}</option>
                        <template x-for="prod in loanProducts" :key="prod.id">
                            <option :value="prod.id" x-text="prod.name"></option>
                        </template>
                    </select>
                </div>

                <!-- Sort -->
                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.sort_by') }}</label>
                    <select id="sort" x-model="filters.sort" @change="loadLoans()"
                            class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500">
                        <option value="created_at_desc">{{ __('messages.newest_first') }}</option>
                        <option value="created_at_asc">{{ __('messages.oldest_first') }}</option>
                        <option value="amount_desc">{{ __('messages.highest_amount') }}</option>
                        <option value="amount_asc">{{ __('messages.lowest_amount') }}</option>
                        <option value="due_date_asc">{{ __('messages.due_date') }}</option>
                    </select>
                </div>
            </div>

            <!-- Period Filter Row -->
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">Period</label>
                        <div class="flex gap-1 flex-wrap">
                            <template x-for="p in [{v:'all',l:'All Time'},{v:'daily',l:'Today'},{v:'weekly',l:'This Week'},{v:'monthly',l:'This Month'},{v:'yearly',l:'This Year'},{v:'custom',l:'Custom'}]" :key="p.v">
                                <button @click="setPeriod(p.v)"
                                        :class="filters.period === p.v ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                        class="px-3 py-1.5 text-xs font-medium border rounded-md transition-colors"
                                        x-text="p.l">
                                </button>
                            </template>
                        </div>
                    </div>
                    <!-- Custom date inputs -->
                    <template x-if="filters.period === 'custom'">
                        <div class="flex items-end gap-2">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">From</label>
                                <input type="date" x-model="filters.date_from" @change="loadLoans()"
                                       class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:ring-1 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">To</label>
                                <input type="date" x-model="filters.date_to" @change="loadLoans()"
                                       class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:ring-1 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Loans Table -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">
                    {{ __('messages.loans') }} (<span x-text="pagination.total || 0"></span>)
                </h3>
                <div class="flex items-center space-x-2">
                    <button @click="exportLoans()" 
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        {{ __('messages.export') }}
                    </button>
                    <a href="{{ route('loans.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        {{ __('messages.new_loan') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="p-8 text-center">
            <div class="inline-flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ __('messages.loading_loans') }}
            </div>
        </div>

        <!-- Table -->
        <div x-show="!loading" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.loan_details') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.borrower') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.product') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.amount') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.loan_status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.stage') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.progress') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="loan in loans" :key="loan.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900" x-text="loan.loan_number"></div>
                                    <div class="text-sm text-gray-500">
                                        Applied: <span x-text="formatDate(loan.created_at)"></span>
                                    </div>
                                    <div x-show="loan.disbursed_at" class="text-sm text-gray-500">
                                        Disbursed: <span x-text="formatDate(loan.disbursed_at)"></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center">
                                            <span class="text-xs font-medium text-primary-700" x-text="getInitials(loan.client?.first_name, loan.client?.last_name)"></span>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900" x-text="(loan.client?.first_name || '') + ' ' + (loan.client?.last_name || '')"></div>
                                        <div class="text-sm text-gray-500" x-text="loan.client?.client_id"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="loan.product?.name || 'N/A'"></div>
                                <div class="text-sm text-gray-500">
                                    <span x-text="loan.term"></span> <span x-text="({'daily':'days','weekly':'weeks','biweekly':'bi-weeks','monthly':'months'}[loan.repayment_schedule] || 'months')"></span> @ <span x-text="loan.interest_rate"></span>%
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900" x-text="formatCurrency(loan.principal)"></div>
                                <div x-show="loan.status === 'active' || loan.status === 'completed'" class="text-sm text-gray-500">
                                    Outstanding: <span x-text="formatCurrency(loan.outstanding_balance || 0)"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                      :class="{
                                          'bg-yellow-100 text-yellow-800': loan.status === 'pending',
                                          'bg-green-100 text-green-800': loan.status === 'approved',
                                          'bg-blue-100 text-blue-800': loan.status === 'disbursed' || loan.status === 'active',
                                          'bg-gray-100 text-gray-800': loan.status === 'completed',
                                          'bg-red-100 text-red-800': loan.status === 'rejected' || loan.status === 'defaulted'
                                      }"
                                      x-text="(loan.status || '').charAt(0).toUpperCase() + (loan.status || '').slice(1)">
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <template x-if="!['disbursed','active','completed','rejected'].includes(loan.status)">
                                    <div class="space-y-1">
                                        <div class="text-xs text-gray-500" x-text="formatStage(loan.approval_stage)"></div>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                              :class="{
                                                  'bg-yellow-100 text-yellow-800': loan.approval_stage_status === 'pending',
                                                  'bg-green-100 text-green-800': loan.approval_stage_status === 'approved',
                                                  'bg-red-100 text-red-800': loan.approval_stage_status === 'rejected'
                                              }"
                                              x-text="loan.approval_stage_status ? loan.approval_stage_status.charAt(0).toUpperCase() + loan.approval_stage_status.slice(1) : '-'">
                                        </span>
                                    </div>
                                </template>
                                <template x-if="['disbursed','active','completed','rejected'].includes(loan.status)">
                                    <span class="text-sm text-gray-400">-</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div x-show="loan.status === 'active' || loan.status === 'completed'" class="w-full">
                                    <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                                        <span x-text="loan.payments_made || 0"></span>/<span x-text="loan.term"></span> payments
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-primary-600 h-2 rounded-full" 
                                             :style="`width: ${Math.min(100, ((loan.payments_made || 0) / loan.term) * 100)}%`"></div>
                                    </div>
                                </div>
                                <div x-show="loan.status !== 'active' && loan.status !== 'completed'" class="text-sm text-gray-500">
                                    -
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <template x-if="loan?.id">
                                        <a :href="'/loans/' + loan.id" class="text-primary-600 hover:text-primary-900 text-sm">{{ __('messages.view') }}</a>
                                    </template>
                                    <template x-if="!loan?.id">
                                        <span class="text-gray-400 text-sm cursor-not-allowed">{{ __('messages.view') }}</span>
                                    </template>

                                    <template x-if="loan?.id">
                                        <a :href="'/loans/' + loan.id + '/edit'" class="text-blue-600 hover:text-blue-900 text-sm">{{ __('messages.edit') }}</a>
                                    </template>

                                    <template x-if="loan.status === 'pending'">
                                        <div class="flex space-x-2">
                                            <button @click="openDecisionModal('approve', loan)" class="text-green-600 hover:text-green-900 text-sm">{{ __('messages.approve') }}</button>
                                            <button @click="openDecisionModal('reject', loan)" class="text-red-600 hover:text-red-900 text-sm">{{ __('messages.reject') }}</button>
                                            <button @click="openHistory(loan)" class="text-gray-600 hover:text-gray-900 text-sm">{{ __('messages.history') }}</button>
                                        </div>
                                    </template>

                                    <template x-if="loan.status === 'approved' && loan?.id && canDisburse()">
                                        <button @click="disburseLoan(loan.id)" class="text-blue-600 hover:text-blue-900 text-sm">{{ __('messages.disburse') }}</button>
                                    </template>

                                    <template x-if="loan.status === 'active' && loan?.id">
                                        <a :href="'/loans/' + loan.id + '/repayments'" class="text-indigo-600 hover:text-indigo-900 text-sm">{{ __('messages.payments') }}</a>
                                    </template>

                                    @if(auth()->user()->role === 'superadmin')
                                    <template x-if="loan?.id">
                                        <button @click="deleteLoan(loan)" class="text-red-600 hover:text-red-900 text-sm font-medium">{{ __('messages.delete') }}</button>
                                    </template>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>

                <!-- Totals Summary Footer -->
                <tfoot x-show="summary.total_count > 0">
                    <tr class="bg-gray-50 border-t-2 border-gray-300">
                        <td colspan="3" class="px-6 py-3 text-sm font-bold text-gray-800">
                            Summary
                            <span class="ml-2 text-xs font-normal text-gray-500" x-text="periodLabel"></span>
                        </td>
                        <td class="px-6 py-3">
                            <div class="text-sm font-bold text-gray-900" x-text="formatCurrency(summary.total_principal)"></div>
                            <div class="text-xs text-gray-500">Total Principal (<span x-text="summary.total_count"></span> loans)</div>
                        </td>
                        <td colspan="4" class="px-6 py-3">
                            <div class="flex flex-wrap gap-3">
                                <template x-if="summary.by_status?.disbursed">
                                    <div class="text-xs">
                                        <span class="inline-block w-2 h-2 rounded-full bg-blue-500 mr-1"></span>
                                        <span class="font-semibold text-blue-700" x-text="'Disbursed: ' + summary.by_status.disbursed.count"></span>
                                        <span class="text-gray-500 ml-1" x-text="formatCurrency(summary.by_status.disbursed.total_principal)"></span>
                                    </div>
                                </template>
                                <template x-if="summary.by_status?.active">
                                    <div class="text-xs">
                                        <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>
                                        <span class="font-semibold text-green-700" x-text="'Active: ' + summary.by_status.active.count"></span>
                                        <span class="text-gray-500 ml-1" x-text="formatCurrency(summary.by_status.active.total_principal)"></span>
                                    </div>
                                </template>
                                <template x-if="summary.by_status?.partially_paid">
                                    <div class="text-xs">
                                        <span class="inline-block w-2 h-2 rounded-full bg-indigo-500 mr-1"></span>
                                        <span class="font-semibold text-indigo-700" x-text="'Partial: ' + summary.by_status.partially_paid.count"></span>
                                        <span class="text-gray-500 ml-1" x-text="formatCurrency(summary.by_status.partially_paid.total_principal)"></span>
                                    </div>
                                </template>
                                <template x-if="summary.by_status?.pending">
                                    <div class="text-xs">
                                        <span class="inline-block w-2 h-2 rounded-full bg-yellow-500 mr-1"></span>
                                        <span class="font-semibold text-yellow-700" x-text="'Pending: ' + summary.by_status.pending.count"></span>
                                        <span class="text-gray-500 ml-1" x-text="formatCurrency(summary.by_status.pending.total_principal)"></span>
                                    </div>
                                </template>
                                <template x-if="summary.by_status?.completed">
                                    <div class="text-xs">
                                        <span class="inline-block w-2 h-2 rounded-full bg-gray-500 mr-1"></span>
                                        <span class="font-semibold text-gray-700" x-text="'Completed: ' + summary.by_status.completed.count"></span>
                                        <span class="text-gray-500 ml-1" x-text="formatCurrency(summary.by_status.completed.total_principal)"></span>
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>

            <!-- Empty State -->
            <div x-show="loans.length === 0 && !loading" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('messages.no_loans_found') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.get_started_loan') }}</p>
                <div class="mt-6">
                    <a href="{{ route('loans.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        {{ __('messages.new_loan') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div x-show="pagination.last_page > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button @click="previousPage()" 
                            :disabled="pagination.current_page <= 1"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ __('messages.previous') }}
                    </button>
                    <button @click="nextPage()" 
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ __('messages.next') }}
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            {{ __('messages.showing') }} <span x-text="pagination.from || 0"></span> {{ __('messages.to') }} <span x-text="pagination.to || 0"></span> {{ __('messages.of') }} <span x-text="pagination.total || 0"></span> {{ __('messages.results') }}
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <button @click="previousPage()" 
                                    :disabled="pagination.current_page <= 1"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="sr-only">{{ __('messages.previous') }}</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            <template x-for="page in getPageNumbers()" :key="page">
                                <button @click="goToPage(page)" 
                                        :class="page === pagination.current_page ? 
                                            'bg-primary-50 border-primary-500 text-primary-600' : 
                                            'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                                        class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                        x-text="page">
                                </button>
                            </template>
                            <button @click="nextPage()" 
                                    :disabled="pagination.current_page >= pagination.last_page"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="sr-only">{{ __('messages.next') }}</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Disburse Modal -->
    <div x-show="disburseModal.open" class="fixed inset-0 z-50 flex items-center justify-center" x-cloak>
      <div class="fixed inset-0 bg-black bg-opacity-40" @click="disburseModal.open=false"></div>
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 relative z-10">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Disburse Loan</h3>
        <p class="text-sm text-gray-500 mb-4">Enter disbursement details before confirming.</p>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Disbursement Date <span class="text-red-500">*</span></label>
            <input type="date" x-model="disburseModal.disbursed_at"
                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">First Repayment Date <span class="text-red-500">*</span></label>
            <input type="date" x-model="disburseModal.first_payment_date"
                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
          </div>
        </div>

        <div class="mt-6 flex items-center justify-end space-x-3">
          <button @click="disburseModal.open=false"
                  class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">Cancel</button>
          <button @click="confirmDisburse()"
                  :disabled="disburseModal.submitting || !disburseModal.disbursed_at || !disburseModal.first_payment_date"
                  class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
            <span x-show="!disburseModal.submitting">Disburse Loan</span>
            <span x-show="disburseModal.submitting">Disbursing...</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Decision Modal (inside Alpine scope) -->
    <div x-show="decisionModal.open" class="fixed inset-0 z-50 flex items-center justify-center" x-cloak>
      <div class="fixed inset-0 bg-black bg-opacity-30" @click="decisionModal.open=false"></div>
      <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 relative">
        <h3 class="text-lg font-medium text-gray-900" x-text="decisionModal.action === 'approve' ? 'Approve Loan Stage' : 'Reject Loan Stage'"></h3>
        <p class="mt-2 text-sm text-gray-500">{{ __('messages.optional_comment') }}</p>
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.comment') }}</label>
          <textarea x-model="decisionModal.comment" rows="4" class="block w-full border border-gray-300 rounded-md p-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
        </div>
        <div class="mt-6 flex items-center justify-end space-x-3">
          <button @click="decisionModal.open=false" class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md">{{ __('messages.cancel') }}</button>
          <button @click="submitDecision()" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">{{ __('messages.submit') }}</button>
        </div>
      </div>
    </div>

    <!-- History Modal (inside Alpine scope) -->
    <div x-show="historyModal.open" class="fixed inset-0 z-50 flex items-center justify-center" x-cloak>
      <div class="fixed inset-0 bg-black bg-opacity-30" @click="historyModal.open=false"></div>
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 relative">
        <h3 class="text-lg font-medium text-gray-900">{{ __('messages.approval_history') }}</h3>
        <div class="mt-4" x-show="historyModal.loading">{{ __('messages.loading') }}...</div>
        <div class="mt-4" x-show="!historyModal.loading && historyModal.records.length === 0">{{ __('messages.no_approval_records') }}</div>
        <div class="mt-4 space-y-3" x-show="!historyModal.loading && historyModal.records.length > 0">
          <template x-for="rec in historyModal.records" :key="rec.id">
            <div class="border border-gray-200 rounded-md p-3">
              <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700" x-text="formatStage(rec.stage)"></div>
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      :class="{
                          'bg-yellow-100 text-yellow-800': rec.status === 'pending',
                          'bg-green-100 text-green-800': rec.status === 'approved',
                          'bg-red-100 text-red-800': rec.status === 'rejected'
                      }" x-text="rec.status.charAt(0).toUpperCase() + rec.status.slice(1)"></span>
              </div>
              <div class="mt-1 text-xs text-gray-500" x-text="rec.user?.name ? ('By ' + rec.user.name) : ''"></div>
              <div class="mt-2 text-sm text-gray-600" x-text="rec.comment || ''"></div>
              <div class="mt-2 text-xs text-gray-400" x-text="rec.decided_at ? formatDate(rec.decided_at) : ''"></div>
            </div>
          </template>
        </div>
        <div class="mt-6 flex items-center justify-end">
          <button @click="historyModal.open=false" class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md">{{ __('messages.close') }}</button>
        </div>
      </div>
    </div>
</div>

@push('scripts')
<script>
function loansIndex(serverStats = {}, serverLoans = {}, serverProducts = []) {
    const ssrLoans   = Array.isArray(serverLoans.data) ? serverLoans.data : [];
    const ssrMeta    = serverLoans;
    return {
        loading: false,
        error: null,
        loans: ssrLoans,
        recentLoans: ssrLoans.slice(0, 5),
        loanProducts: Array.isArray(serverProducts) ? serverProducts : [],
        stats: serverStats || {},
        summary: { total_count: 0, total_principal: 0, total_repayable: 0, by_status: {} },
        pagination: {
            current_page: ssrMeta.current_page || 1,
            last_page:    ssrMeta.last_page    || 1,
            per_page:     ssrMeta.per_page     || 15,
            total:        ssrMeta.total        || ssrLoans.length,
            from:         ssrMeta.from         || (ssrLoans.length ? 1 : 0),
            to:           ssrMeta.to           || ssrLoans.length
        },
        filters: {
            status: '',
            product_id: '',
            search: '',
            sort: 'created_at_desc',
            period: 'all',
            date_from: '',
            date_to: '',
        },
        decisionModal: { open: false, mode: 'approve', loan: null, comment: '' },
        historyModal: { open: false, loan: null, history: [] },
        disburseModal: { open: false, loanId: null, disbursed_at: '', first_payment_date: '', submitting: false },

        async init() {
            // Data already loaded server-side — nothing to fetch on init
        },
        fetchOptions(method = 'GET', body = null) {
            const opts = { method, headers: { 'Accept': 'application/json' }, credentials: 'same-origin' };
            
            // Add CSRF token for authentication
            const token = document.querySelector('meta[name="csrf-token"]');
            if (token) {
                opts.headers['X-CSRF-TOKEN'] = token.getAttribute('content');
            }
            
            if (body) {
                opts.headers['Content-Type'] = 'application/json';
                opts.body = JSON.stringify(body);
            }
            return opts;
        },
        async loadLoanProducts() {
            // Already loaded server-side; only call if loanProducts is empty
            if (this.loanProducts.length) return;
            try {
                const response = await fetch('/api/loan-products', this.fetchOptions('GET'));
                const raw = await response.json();
                const payload = raw?.data ?? raw;
                this.loanProducts = Array.isArray(payload) ? payload : [];
            } catch (e) {
                console.error('Error loading loan products:', e);
            }
        },
        async loadStats() {
            // Re-fetch stats only after mutations (approve/disburse/delete)
            try {
                const response = await fetch('/api/loans/stats', this.fetchOptions('GET'));
                const raw = await response.json();
                const payload = raw?.data ?? raw;
                if (payload && typeof payload === 'object') this.stats = payload;
            } catch (e) {
                console.error('Error loading loan stats:', e);
            }
        },
        async loadLoans(page = 1) {
            this.loading = true;
            this.error = null;
            try {
                const params = new URLSearchParams();
                params.set('page', page);
                if (this.filters.status)     params.set('status',     this.filters.status);
                if (this.filters.search)     params.set('search',     this.filters.search);
                if (this.filters.product_id) params.set('product_id', this.filters.product_id);
                if (this.filters.sort)       params.set('sort',       this.filters.sort);
                if (this.filters.period)     params.set('period',     this.filters.period);
                if (this.filters.date_from)  params.set('date_from',  this.filters.date_from);
                if (this.filters.date_to)    params.set('date_to',    this.filters.date_to);
                const response = await fetch(`/api/loans?${params.toString()}`, this.fetchOptions('GET'));
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    throw new Error('Session expired. Please refresh the page.');
                }
                if (response.status === 401) {
                    throw new Error('Not authenticated. Please refresh and log in again.');
                }
                const raw = await response.json();
                const payload = raw?.data ?? raw;
                this.loans = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
                const meta = payload?.meta ?? payload;
                if (meta?.current_page) {
                    this.pagination = {
                        current_page: meta.current_page,
                        last_page:    meta.last_page,
                        per_page:     meta.per_page,
                        total:        meta.total,
                        from:         meta.from,
                        to:           meta.to
                    };
                } else {
                    this.pagination = { current_page: 1, last_page: 1, per_page: 15, total: this.loans.length };
                }
                if (raw?.summary) this.summary = raw.summary;
            } catch (e) {
                console.error('Error loading loans:', e);
                this.error = e.message || 'Failed to load loans.';
            } finally {
                this.loading = false;
            }
        },
        get periodLabel() {
            const map = { all: 'All Time', daily: 'Today', weekly: 'This Week', monthly: 'This Month', yearly: 'This Year', custom: 'Custom Range' };
            return map[this.filters.period] || '';
        },
        setPeriod(val) {
            this.filters.period = val;
            if (val !== 'custom') {
                this.filters.date_from = '';
                this.filters.date_to = '';
                this.loadLoans();
            }
        },
        async approveLoan(id) {
            try {
                const response = await fetch(`/api/loans/${id}/approve`, this.fetchOptions('PATCH', JSON.stringify({}))); 
                if (!response.ok) throw new Error('Approve failed');
                await this.loadStats();
                await this.loadLoans(this.pagination.current_page);
            } catch (e) { console.error('Error approving loan:', e); }
        },
        disburseLoan(id) {
            const today = new Date().toISOString().split('T')[0];
            // Default first payment date = today + 30 days
            const fp = new Date(); fp.setDate(fp.getDate() + 30);
            const fpStr = fp.toISOString().split('T')[0];
            this.disburseModal = { open: true, loanId: id, disbursed_at: today, first_payment_date: fpStr, submitting: false };
        },
        async confirmDisburse() {
            this.disburseModal.submitting = true;
            try {
                const payload = {
                    disbursed_at: this.disburseModal.disbursed_at,
                    first_payment_date: this.disburseModal.first_payment_date,
                };
                const response = await fetch(`/api/loans/${this.disburseModal.loanId}/disburse`, this.fetchOptions('PATCH', payload));
                const data = await response.json();
                if (!response.ok) {
                    alert(data.message || 'Failed to disburse loan');
                    return;
                }
                this.disburseModal.open = false;
                alert(data.message || 'Loan disbursed successfully');
                await this.loadStats();
                await this.loadLoans(this.pagination.current_page);
            } catch (e) {
                console.error('Error disbursing loan:', e);
                alert('An error occurred while disbursing the loan.');
            } finally {
                this.disburseModal.submitting = false;
            }
        },
        canDisburse() {
            // Check if user can disburse loans based on permission or default roles
            return window.AppUser?.canDisburse === true;
        },
        async rejectLoan(id) {
            try {
                const response = await fetch(`/api/loans/${id}/reject`, this.fetchOptions('PATCH', JSON.stringify({}))); 
                if (!response.ok) throw new Error('Reject failed');
                await this.loadStats();
                await this.loadLoans(this.pagination.current_page);
            } catch (e) { console.error('Error rejecting loan:', e); }
        },
        // Decision modal handlers for staged approvals
        openDecisionModal(action, loan) {
            this.decisionModal.action = action;
            this.decisionModal.loan = loan;
            this.decisionModal.comment = '';
            this.decisionModal.open = true;
        },
        async submitDecision() {
            try {
                const loan = this.decisionModal.loan;
                if (!loan?.id) return;
                const url = this.decisionModal.action === 'approve'
                    ? `/loans/${loan.id}/stage/approve`
                    : `/loans/${loan.id}/stage/reject`;
                const response = await fetch(url, this.fetchOptions('POST', JSON.stringify({ comment: this.decisionModal.comment || null })));
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(data?.message || 'Decision failed');
                this.decisionModal.open = false;
                this.decisionModal.loan = null;
                this.decisionModal.comment = '';
                await this.loadStats();
                await this.loadLoans(this.pagination.current_page);
            } catch (e) {
                console.error('Error submitting decision:', e);
                alert(e.message || 'Could not submit decision');
            }
        },
        // Approval history modal
        openHistory(loan) {
            this.historyModal.open = true;
            this.historyModal.records = [];
            this.historyModal.loading = true;
            this.historyModal.loanId = loan?.id || null;
            if (this.historyModal.loanId) this.loadHistory(this.historyModal.loanId);
        },
        async loadHistory(loanId) {
            try {
                const response = await fetch(`/api/loans/${loanId}/approvals`, this.fetchOptions('GET'));
                const raw = await response.json();
                const records = raw?.data ?? raw;
                this.historyModal.records = Array.isArray(records) ? records : [];
            } catch (e) {
                console.error('Error loading approval history:', e);
                this.historyModal.records = [];
            } finally {
                this.historyModal.loading = false;
            }
        },
        // Helpers
        formatStage(stage) {
            const map = {
                cso_review: 'CSO Review',
                loan_officer_review: 'Loan Officer Review',
                manager_review: 'Manager Review',
                gm_approval: 'GM Approval'
            };
            return map[stage] || (stage ? stage.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-');
        },
        formatDate(value) {
            try { return new Date(value).toLocaleString(); } catch (_) { return value || ''; }
        },
        getInitials(first = '', last = '') {
            const f = (first || '').trim().charAt(0).toUpperCase();
            const l = (last || '').trim().charAt(0).toUpperCase();
            return (f + l) || '-';
        },
        previousPage() {
            if ((this.pagination.current_page || 1) > 1) this.loadLoans(this.pagination.current_page - 1);
        },
        nextPage() {
            if ((this.pagination.current_page || 1) < (this.pagination.last_page || 1)) this.loadLoans(this.pagination.current_page + 1);
        },
        goToPage(page) { this.loadLoans(page); },
        getPageNumbers() {
            const last = this.pagination.last_page || 1;
            const cur = this.pagination.current_page || 1;
            const window = 5;
            let start = Math.max(1, cur - Math.floor(window / 2));
            let end = Math.min(last, start + window - 1);
            start = Math.max(1, end - window + 1);
            const arr = [];
            for (let i = start; i <= end; i++) arr.push(i);
            return arr.length ? arr : [1];
        },
        exportLoans() {
            try {
                const rows = this.loans.map(l => ({
                    LoanNumber: l.loan_number,
                    Borrower: `${l.client?.first_name || ''} ${l.client?.last_name || ''}`.trim(),
                    Product: l.product?.name || '',
                    Amount: l.principal,
                    Status: l.status,
                    Stage: l.approval_stage || '',
                    StageStatus: l.approval_stage_status || ''
                }));
                const headers = Object.keys(rows[0] || {LoanNumber:'',Borrower:'',Product:'',Amount:'',Status:'',Stage:'',StageStatus:''});
                const csv = [headers.join(','), ...rows.map(r => headers.map(h => JSON.stringify(r[h] ?? '')).join(','))].join('\n');
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `loans_export_${Date.now()}.csv`;
                a.click();
                URL.revokeObjectURL(url);
            } catch (e) { console.error('Export failed:', e); }
        },
        formatCurrency(value) {
            try {
                const s = window.AppSettings || {};
                const locale = s.locale || 'en-TZ';
                const currency = s.currency || 'TZS';
                const formatted = new Intl.NumberFormat(locale, { style: 'currency', currency }).format(Number(value || 0));
                return formatted.replace('TZS', (s.currency_symbol || 'TSHS'));
            } catch (_) {
                const num = Number(value || 0);
                return `${(window.AppSettings?.currency_symbol || 'TSHS')} ${num.toLocaleString('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            }
        },
        async deleteLoan(loan) {
            const clientName = `${loan.client?.first_name || ''} ${loan.client?.last_name || ''}`.trim();
            const amount = this.formatCurrency(loan.principal || 0);
            if (!confirm(`Are you sure you want to permanently delete this loan?\n\nLoan: ${loan.loan_number}\nBorrower: ${clientName}\nAmount: ${amount}\n\nThis action cannot be undone.`)) {
                return;
            }
            try {
                const response = await fetch(`/loans/${loan.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin'
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok) {
                    alert('Loan deleted successfully');
                    await this.loadStats();
                    await this.loadLoans(this.pagination.current_page);
                } else {
                    alert(data?.message || 'Failed to delete loan');
                }
            } catch (e) {
                console.error('Error deleting loan:', e);
                alert('Failed to delete loan: ' + e.message);
            }
        }
    };
}
</script>
@endpush
@endsection