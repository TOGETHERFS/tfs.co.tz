@extends('layouts.app')

@section('title', __('messages.repayments'))
@section('page-title', __('messages.repayments'))

@section('content')
<div x-data="repaymentsIndex()" x-init="init()" class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.repayments') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.manage_repayments') }}</p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3">
            <button @click="showPaymentModal = true" 
                    class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                {{ __('messages.record_payment_btn') }}
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background:#16a34a33;">
                            <svg class="w-5 h-5" style="color:#16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-xs font-bold uppercase tracking-widest" style="color:#92400e;">{{ __('messages.todays_collections') }}</p>
                        <p class="text-xl font-bold mt-1" style="color:#111827;" x-text="formatCurrency(stats.today_collections || 0)"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background:#2563eb33;">
                            <svg class="w-5 h-5" style="color:#60a5fa;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-xs font-bold uppercase tracking-widest" style="color:#92400e;">{{ __('messages.this_month') }}</p>
                        <p class="text-xl font-bold mt-1" style="color:#111827;" x-text="formatCurrency(stats.month_collections || 0)"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background:#f5c51833;">
                            <svg class="w-5 h-5" style="color:#92400e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-xs font-bold uppercase tracking-widest" style="color:#92400e;">{{ __('messages.due_today') }}</p>
                        <p class="text-xl font-bold mt-1" style="color:#111827;" x-text="formatCurrency(stats.due_today || 0)"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background:#dc262633;">
                            <svg class="w-5 h-5" style="color:#f87171;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-xs font-bold uppercase tracking-widest" style="color:#92400e;">{{ __('messages.overdue') }}</p>
                        <p class="text-xl font-bold mt-1" style="color:#f87171;" x-text="formatCurrency(stats.overdue_amount || 0)"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
        <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <label for="search" class="block text-xs font-bold uppercase tracking-widest mb-2" style="color:#92400e;">{{ __('messages.search') }}</label>
                    <div class="relative">
                        <input type="text" 
                               id="search"
                               x-model="filters.search"
                               @input.debounce.300ms="loadRepayments()"
                               placeholder="{{ __('messages.search_placeholder') }}"
                               class="block w-full pl-10 pr-3 py-2 rounded-md text-sm"
                               style="background:#f9fafb;border:1px solid #e5e7eb;color:#374151;">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5" style="color:#6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-xs font-bold uppercase tracking-widest mb-2" style="color:#92400e;">{{ __('messages.status') }}</label>
                    <select id="status" 
                            x-model="filters.status"
                            @change="loadRepayments()"
                            class="block w-full px-3 py-2 rounded-md text-sm"
                            style="background:#f9fafb;border:1px solid #e5e7eb;color:#374151;">
                        <option value="">{{ __('messages.all_statuses') }}</option>
                        <option value="pending">{{ __('messages.pending') }}</option>
                        <option value="paid">{{ __('messages.paid') }}</option>
                        <option value="partial">{{ __('messages.partial') }}</option>
                        <option value="overdue">{{ __('messages.overdue') }}</option>
                    </select>
                </div>

                <!-- Loan Status Filter -->
                <div>
                    <label for="loan_status" class="block text-xs font-bold uppercase tracking-widest mb-2" style="color:#92400e;">Loan Status</label>
                    <select id="loan_status"
                            x-model="filters.loan_status"
                            @change="loadRepayments()"
                            class="block w-full px-3 py-2 rounded-md text-sm"
                            style="background:#f9fafb;border:1px solid #e5e7eb;color:#374151;">
                        <option value="">All Loans</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="disbursed">Disbursed</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label for="date_from" class="block text-xs font-bold uppercase tracking-widest mb-2" style="color:#92400e;">{{ __('messages.from_date') }}</label>
                    <input type="date" 
                           id="date_from"
                           x-model="filters.date_from"
                           @change="loadRepayments()"
                           class="block w-full px-3 py-2 rounded-md text-sm"
                           style="background:#f9fafb;border:1px solid #e5e7eb;color:#374151;">
                </div>

                <!-- Sort -->
                <div>
                    <label for="sort" class="block text-xs font-bold uppercase tracking-widest mb-2" style="color:#92400e;">{{ __('messages.sort_by') }}</label>
                    <select id="sort" 
                            x-model="filters.sort"
                            @change="loadRepayments()"
                            class="block w-full px-3 py-2 rounded-md text-sm"
                            style="background:#f9fafb;border:1px solid #e5e7eb;color:#374151;">
                        <option value="recent_first">Hivi Karibuni Zaidi</option>
                        <option value="paid_first">Waliolipa Kwanza</option>
                        <option value="month_asc">Mwezi (Wa Kwanza)</option>
                        <option value="month_desc">Mwezi (Wa Mwisho)</option>
                        <option value="due_date_asc">{{ __('messages.due_date_earliest') }}</option>
                        <option value="due_date_desc">{{ __('messages.due_date_latest') }}</option>
                        <option value="amount_desc">{{ __('messages.amount_highest') }}</option>
                        <option value="amount_asc">{{ __('messages.amount_lowest') }}</option>
                        <option value="created_at_desc">{{ __('messages.newest_first') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Repayments Table -->
    <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
        <div class="px-5 py-4" style="border-bottom:1px solid #e5e7eb;">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-widest" style="color:#92400e;">
                    {{ __('messages.payment_schedules') }} (<span x-text="pagination.total || 0"></span>)
                </h3>
                <button @click="exportRepayments()" 
                        class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-md" style="background:#fefce8;color:#92400e;border:1px solid #fde68a;">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ __('messages.export') }}
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="p-8 text-center" style="color:#6b7280;">
            <div class="inline-flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5" style="color:#92400e;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ __('messages.loading_repayments') }}
            </div>
        </div>

        <!-- Table -->
        <div x-show="!loading" class="overflow-x-auto">
            <table class="min-w-full" style="border-collapse:collapse;">
                <thead>
                    <tr style="background:#f3f4f6;">
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase" style="color:#92400e;border-bottom:2px solid #e5e7eb;">{{ __('messages.loan_details_col') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase" style="color:#92400e;border-bottom:2px solid #e5e7eb;">{{ __('messages.borrower') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase" style="color:#92400e;border-bottom:2px solid #e5e7eb;">{{ __('messages.schedule') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase" style="color:#92400e;border-bottom:2px solid #e5e7eb;">{{ __('messages.amount') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-bold uppercase" style="color:#92400e;border-bottom:2px solid #e5e7eb;">{{ __('messages.loan_status') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase" style="color:#92400e;border-bottom:2px solid #e5e7eb;">{{ __('messages.payment_info') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase" style="color:#92400e;border-bottom:2px solid #e5e7eb;">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(schedule, idx) in repayments" :key="schedule.id">
                        <tr :style="schedule.status === 'overdue' ? 'background:#fef2f2;' : (idx % 2 === 0 ? 'background:#f9fafb;' : 'background:#ffffff;')">
                            <td class="px-5 py-3 whitespace-nowrap" style="border-bottom:1px solid #e5e7eb;">
                                <div class="text-sm font-bold" style="color:#92400e;" x-text="schedule.loan?.loan_number"></div>
                                <div class="text-xs" style="color:#6b7280;">Principal: <span x-text="formatCurrency(schedule.loan?.principal_amount || schedule.loan?.amount || 0)"></span></div>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap" style="border-bottom:1px solid #e5e7eb;">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center flex-shrink-0" style="background:#f5c51822;">
                                        <span class="text-xs font-bold" style="color:#92400e;" x-text="getInitials(schedule.loan?.client?.first_name, schedule.loan?.client?.last_name)"></span>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium" style="color:#374151;" x-text="schedule.loan?.client?.first_name + ' ' + schedule.loan?.client?.last_name"></div>
                                        <div class="text-xs" style="color:#6b7280;" x-text="schedule.loan?.client?.phone_number"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap" style="border-bottom:1px solid #e5e7eb;">
                                <div class="text-sm font-medium" style="color:#374151;">
                                    Installment <span x-text="schedule.installment_number"></span> of <span x-text="schedule.loan?.term"></span>
                                </div>
                                <div class="text-xs" style="color:#6b7280;">Due: <span x-text="formatDate(schedule.due_date)"></span></div>
                                <div x-show="schedule.paid_date" class="text-xs" style="color:#16a34a;">Paid: <span x-text="formatDate(schedule.paid_date)"></span></div>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-right" style="border-bottom:1px solid #e5e7eb;">
                                <div class="text-sm font-semibold" style="color:#111827;" x-text="formatCurrency(schedule.total_amount)"></div>
                                <div x-show="schedule.paid_amount > 0" class="text-xs" style="color:#16a34a;">Paid: <span x-text="formatCurrency(schedule.paid_amount)"></span></div>
                                <div x-show="schedule.total_amount - schedule.paid_amount > 0" class="text-xs" style="color:#f87171;">Balance: <span x-text="formatCurrency(schedule.total_amount - schedule.paid_amount)"></span></div>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-center" style="border-bottom:1px solid #e5e7eb;">
                                <span :style="{
                                    background: schedule.status === 'paid' ? '#16a34a' : schedule.status === 'partial' ? '#c4621a' : schedule.status === 'overdue' ? '#dc2626' : '#4b5563',
                                    color:'#fff', padding:'2px 10px', borderRadius:'4px', fontSize:'0.72rem', fontWeight:'700', letterSpacing:'0.08em', display:'inline-block'
                                }" x-text="(schedule.status || '').charAt(0).toUpperCase() + (schedule.status || '').slice(1)">
                                </span>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap" style="border-bottom:1px solid #e5e7eb;">
                                <div x-show="schedule.paid_date" class="text-sm" style="color:#374151;">
                                    <div>Paid: <span x-text="formatDate(schedule.paid_date)"></span></div>
                                    <div x-show="schedule.payment_method" class="text-xs" style="color:#6b7280;">via <span x-text="schedule.payment_method || 'N/A'"></span></div>
                                </div>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-right" style="border-bottom:1px solid #e5e7eb;">
                                <div class="flex items-center justify-end space-x-3">
                                    <template x-if="schedule.status !== 'paid'">
                                        <button @click="recordPayment(schedule)" class="text-sm font-semibold" style="color:#92400e;">{{ __('messages.pay') }}</button>
                                    </template>
                                    <a :href="'/loans/' + schedule.loan_id" class="text-sm font-semibold" style="color:#60a5fa;">{{ __('messages.view_loan') }}</a>
                                    <template x-if="schedule.paid_amount > 0">
                                        <button @click="viewPaymentHistory(schedule)" class="text-sm font-semibold" style="color:#6b7280;">{{ __('messages.history') }}</button>
                                    </template>
                                    <template x-if="schedule.status === 'paid' && canEditRepayment()">
                                        <button @click="editRepayment(schedule)" class="text-sm font-semibold" style="color:#f59e0b;">Edit</button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- Empty State -->
            <div x-show="repayments.length === 0 && !loading" class="text-center py-12" style="color:#6b7280;">
                <svg class="mx-auto h-12 w-12" style="color:#4b5563;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium" style="color:#374151;">{{ __('messages.no_repayments_found') }}</h3>
                <p class="mt-1 text-sm">{{ __('messages.no_repayments_desc') }}</p>
            </div>
        </div>

        <!-- Pagination -->
        <div x-show="pagination.last_page > 1" class="px-5 py-3" style="border-top:1px solid #e5e7eb;">
            <div class="flex items-center justify-between">
                <p class="text-sm" style="color:#6b7280;">Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span> of <span x-text="pagination.total || 0"></span> results</p>
                <div class="flex items-center space-x-1">
                    <button @click="previousPage()" :disabled="pagination.current_page <= 1"
                            class="px-3 py-1 text-sm font-semibold rounded-md disabled:opacity-40"
                            style="background:#f9fafb;color:#374151;border:1px solid #e5e7eb;">{{ __('messages.previous') }}</button>
                    <template x-for="page in getPageNumbers()" :key="page">
                        <button @click="goToPage(page)"
                                :style="page === pagination.current_page ? 'background:#92400e;color:#fff;' : 'background:#f9fafb;color:#374151;border:1px solid #e5e7eb;'"
                                class="px-3 py-1 text-sm font-semibold rounded-md"
                                x-text="page">
                        </button>
                    </template>
                    <button @click="nextPage()" :disabled="pagination.current_page >= pagination.last_page"
                            class="px-3 py-1 text-sm font-semibold rounded-md disabled:opacity-40"
                            style="background:#f9fafb;color:#374151;border:1px solid #e5e7eb;">{{ __('messages.next') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div x-show="showPaymentModal" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="submitPayment()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Record Payment
                                </h3>
                                
                                <!-- Loan Search -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('messages.search_loan') }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text" 
                                               x-model="paymentForm.loanSearch"
                                               @input.debounce.300ms="searchLoansForPayment()"
placeholder="Search by loan number or borrower name..."
                                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    
                                    <!-- Loan Search Results -->
                                    <div x-show="loanSearchResults.length > 0" class="mt-2 bg-white border border-gray-300 rounded-md shadow-lg max-h-40 overflow-y-auto">
                                        <template x-for="loan in loanSearchResults" :key="loan.id">
                                            <div @click="selectLoanForPayment(loan)" 
                                                 class="px-4 py-2 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                                                <div class="text-sm font-medium text-gray-900" x-text="loan.loan_number"></div>
                                                <div class="text-sm text-gray-500" x-text="loan.client?.first_name + ' ' + loan.client?.last_name"></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Payment Amount -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('messages.payment_amount') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" 
                                           x-model="paymentForm.amount"
                                           @input="validatePaymentAmount()"
                                           step="0.01"
                                           min="0"
                                           :max="paymentForm.scheduleMaxAmount || ''"
                                           placeholder="Enter payment amount"
                                           :class="paymentAmountError ? 'block w-full px-3 py-2 border border-red-500 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm' : 'block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm'">
                                    <p x-show="paymentAmountError" x-text="paymentAmountError" class="mt-1 text-xs text-red-600"></p>
                                    <p x-show="paymentForm.scheduleMaxAmount !== null" class="mt-1 text-xs text-gray-500">
                                        Installment due: <span class="font-semibold text-gray-700" x-text="paymentForm.scheduleMaxAmount ? 'TZS ' + parseFloat(paymentForm.scheduleMaxAmount).toLocaleString('en-TZ', {minimumFractionDigits:2}) : ''"></span>
                                    </p>
                                </div>

                                <!-- Payment Method -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('messages.payment_method') }} <span class="text-red-500">*</span>
                                    </label>
                                    <select x-model="paymentForm.payment_method"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                        <option value="">Select payment method</option>
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="mobile_money">Mobile Money</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="selcom_till">Selcom TILL Payment</option>
                                        <option value="selcom_wallet">Selcom Wallet Payment</option>
                                        <option value="selcom_qr">Selcom QR Code Payment</option>
                                    </select>
                                </div>

                                <!-- Phone Number (for Selcom payments) -->
                                <div class="mb-4" x-show="['selcom_till', 'selcom_wallet'].includes(paymentForm.payment_method)">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Phone Number <span class="text-red-500">*</span>
                                    </label>
                                    <input type="tel" 
                                           x-model="paymentForm.phone_number"
                                           placeholder="Enter phone number (e.g., 255712345678)"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    <p class="mt-1 text-xs text-gray-500">Enter phone number in international format (255XXXXXXXXX)</p>
                                </div>

                                <!-- Email (for Selcom payments) -->
                                <div class="mb-4" x-show="['selcom_till', 'selcom_wallet', 'selcom_qr'].includes(paymentForm.payment_method)">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Email Address
                                    </label>
                                    <input type="email" 
                                           x-model="paymentForm.email"
                                           placeholder="Enter email address (optional)"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                </div>

                                <!-- TILL Number (for Selcom TILL payments) -->
                                <div class="mb-4" x-show="paymentForm.payment_method === 'selcom_till'">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        TILL Number
                                    </label>
                                    <input type="text" 
                                           x-model="paymentForm.till_number"
                                           placeholder="Enter TILL number (optional - uses default if empty)"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    <p class="mt-1 text-xs text-gray-500">Leave empty to use the default TILL number configured in the system</p>
                                </div>

                                <!-- Reference Number -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Reference Number
                                    </label>
                                    <input type="text" 
                                           x-model="paymentForm.reference_number"
                                           placeholder="Enter reference number (optional)"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                </div>

                                <!-- Payment Date -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Payment Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date"
                                           x-model="paymentForm.payment_date"
                                           :max="new Date(new Date().getTime()+3*60*60*1000).toISOString().split('T')[0]"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                </div>

                                <!-- Notes -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Notes
                                    </label>
                                    <textarea x-model="paymentForm.notes"
                                              rows="2"
                                              placeholder="Enter any additional notes..."
                                              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                        <button type="button" 
                                @click="closePaymentModal()"
                                class="w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:text-sm">
                            Cancel
                        </button>
                        <button type="submit" 
                                :disabled="paymentSubmitting"
                                class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="paymentSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="paymentSubmitting ? 'Processing...' : 'Record Payment'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Repayment Modal (Admin Only) -->
    <div x-show="showEditModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="background:rgba(0,0,0,0.7);">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="w-full max-w-lg rounded-xl shadow-2xl" style="background:#ffffff;border:1px solid #e5e7eb;" @click.outside="closeEditModal()">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4" style="border-bottom:1px solid #e5e7eb;">
                    <div>
                        <h3 class="text-lg font-bold" style="color:#92400e;">Edit Repayment</h3>
                        <p class="text-xs mt-1" style="color:#6b7280;">Correct a mistake on a paid repayment. Schedule balances will be recalculated.</p>
                    </div>
                    <button @click="closeEditModal()" class="text-gray-400 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <!-- Body -->
                <div class="px-6 py-5 space-y-4">
                    <!-- Error -->
                    <div x-show="editError" class="px-4 py-3 rounded-md text-sm font-medium" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;" x-text="editError"></div>

                    <!-- Amount -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest mb-2" style="color:#92400e;">Amount (TZS) <span style="color:#f87171;">*</span></label>
                        <input type="number" step="0.01" min="0.01"
                               x-model="editForm.amount"
                               class="block w-full px-3 py-2 rounded-md text-sm"
                               style="background:#f9fafb;border:1px solid #e5e7eb;color:#374151;">
                    </div>

                    <!-- Payment Method + Date (side by side) -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest mb-2" style="color:#92400e;">Payment Method <span style="color:#f87171;">*</span></label>
                            <select x-model="editForm.payment_method"
                                    class="block w-full px-3 py-2 rounded-md text-sm"
                                    style="background:#f9fafb;border:1px solid #e5e7eb;color:#374151;">
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest mb-2" style="color:#92400e;">Payment Date <span style="color:#f87171;">*</span></label>
                            <input type="date"
                                   x-model="editForm.payment_date"
                                   class="block w-full px-3 py-2 rounded-md text-sm"
                                   style="background:#f9fafb;border:1px solid #e5e7eb;color:#374151;">
                        </div>
                    </div>

                    <!-- Reference -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest mb-2" style="color:#6b7280;">Reference Number</label>
                        <input type="text"
                               x-model="editForm.reference_number"
                               placeholder="Optional"
                               class="block w-full px-3 py-2 rounded-md text-sm"
                               style="background:#f9fafb;border:1px solid #e5e7eb;color:#374151;">
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest mb-2" style="color:#6b7280;">Notes / Reason for Edit</label>
                        <textarea x-model="editForm.notes" rows="2" placeholder="Explain the correction made..."
                                  class="block w-full px-3 py-2 rounded-md text-sm"
                                  style="background:#f9fafb;border:1px solid #e5e7eb;color:#374151;resize:none;"></textarea>
                    </div>
                </div>
                <!-- Footer -->
                <div class="flex items-center justify-end space-x-3 px-6 py-4" style="border-top:1px solid #e5e7eb;">
                    <button @click="closeEditModal()" class="px-4 py-2 text-sm font-semibold rounded-md" style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;">Cancel</button>
                    <button @click="submitEditRepayment()" :disabled="editSubmitting"
                            class="px-5 py-2 text-sm font-bold rounded-md disabled:opacity-50"
                            style="background:#f59e0b;color:#111827;">
                        <span x-text="editSubmitting ? 'Saving...' : 'Save Changes'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function repaymentsIndex() {
        return {
            repayments: [],
            stats: {},
            loading: false,
            showPaymentModal: false,
            paymentSubmitting: false,
            paymentAmountError: '',
            loanSearchResults: [],
            showEditModal: false,
            editSubmitting: false,
            editError: null,
            editForm: {
                scheduleId: null,
                amount: '',
                payment_method: '',
                payment_date: '',
                reference_number: '',
                notes: ''
            },
            filters: {
                search: '',
                status: '',
                loan_status: '',
                date_from: '',
                sort: 'recent_first'
            },
            pagination: {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
                from: 0,
                to: 0
            },
            paymentForm: {
                loan_id: '',
                schedule_id: null,
                scheduleMaxAmount: null,
                loanSearch: '',
                amount: '',
                payment_method: '',
                reference_number: '',
                notes: '',
                phone_number: '',
                email: '',
                till_number: ''
            },

            async init() {
                await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
                this.loadStats();
                this.loadRepayments();
            },

            async loadStats() {
                try {
                    const response = await fetch('/api/repayments/stats');
                    this.stats = await response.json();
                } catch (error) {
                    console.error('Error loading stats:', error);
                }
            },

            async loadRepayments() {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        page: this.pagination.current_page,
                        per_page: this.pagination.per_page,
                        ...this.filters
                    });

                    const response = await fetch(`/api/repayments?${params}`);
                    const data = await response.json();
                    
                    this.repayments = data.data || [];
                    this.pagination = {
                        current_page: data.current_page || 1,
                        last_page: data.last_page || 1,
                        per_page: data.per_page || 15,
                        total: data.total || 0,
                        from: data.from || 0,
                        to: data.to || 0
                    };
                } catch (error) {
                    console.error('Error loading repayments:', error);
                    this.repayments = [];
                } finally {
                    this.loading = false;
                }
            },

            async searchLoansForPayment() {
                if (this.paymentForm.loanSearch.length < 2) {
                    this.loanSearchResults = [];
                    return;
                }

                try {
                    const response = await fetch(`/api/loans/search?q=${encodeURIComponent(this.paymentForm.loanSearch)}&status=active`);
                    this.loanSearchResults = await response.json();
                } catch (error) {
                    console.error('Error searching loans:', error);
                    this.loanSearchResults = [];
                }
            },

            selectLoanForPayment(loan) {
                this.paymentForm.loan_id = loan.id;
                this.paymentForm.loanSearch = `${loan.loan_number} - ${loan.client?.first_name} ${loan.client?.last_name}`;
                this.loanSearchResults = [];
            },

            recordPayment(schedule) {
                const installmentUnpaid = Math.round((schedule.total_amount - schedule.paid_amount) * 100) / 100;
                const outstandingBalance = Math.round(parseFloat(schedule.loan?.outstanding_balance || installmentUnpaid) * 100) / 100;
                const maxAllowed = Math.round(Math.min(installmentUnpaid, outstandingBalance) * 100) / 100;
                this.paymentForm.loan_id = schedule.loan_id;
                this.paymentForm.schedule_id = schedule.id;
                this.paymentForm.scheduleMaxAmount = maxAllowed;
                this.paymentForm.loanSearch = `${schedule.loan?.loan_number} - ${schedule.loan?.client?.first_name} ${schedule.loan?.client?.last_name}`;
                this.paymentForm.amount = maxAllowed;
                this.paymentAmountError = '';
                this.showPaymentModal = true;
            },

            validatePaymentAmount() {
                const amount = parseFloat(this.paymentForm.amount);
                const max = this.paymentForm.scheduleMaxAmount;
                if (!amount || amount <= 0) {
                    this.paymentAmountError = 'Please enter a valid amount.';
                    return false;
                }
                if (max !== null && amount > max) {
                    this.paymentAmountError = `Amount cannot exceed installment due of TZS ${max.toLocaleString('en-TZ', {minimumFractionDigits: 2, maximumFractionDigits: 2})}.`;
                    return false;
                }
                this.paymentAmountError = '';
                return true;
            },

            async submitPayment() {
                if (!this.validatePaymentAmount()) return;
                this.paymentSubmitting = true;
                try {
                    const response = await fetch('/api/repayments', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            loan_id: this.paymentForm.loan_id,
                            schedule_id: this.paymentForm.schedule_id || null,
                            amount: this.paymentForm.amount,
                            payment_method: this.paymentForm.payment_method,
                            reference_number: this.paymentForm.reference_number,
                            payment_date: this.paymentForm.payment_date || new Date(new Date().getTime() + 3*60*60*1000).toISOString().split('T')[0],
                            notes: this.paymentForm.notes
                        })
                    });

                    const data = await response.json();
                    if (response.ok && data.success) {
                        this.closePaymentModal();
                        await this.loadRepayments();
                        await this.loadStats();
                    } else {
                        let msg = data.message || 'Error recording payment';
                        if (data.errors) {
                            msg += '\n' + Object.values(data.errors).flat().join('\n');
                        }
                        alert(msg);
                    }
                } catch (error) {
                    console.error('Error submitting payment:', error);
                    alert('Error recording payment');
                } finally {
                    this.paymentSubmitting = false;
                }
            },

            closePaymentModal() {
                this.showPaymentModal = false;
                this.paymentAmountError = '';
                this.paymentForm.scheduleMaxAmount = null;
                this.paymentForm = {
                    loan_id: '',
                    schedule_id: null,
                    loanSearch: '',
                    amount: '',
                    payment_method: '',
                    reference_number: '',
                    notes: '',
                    phone_number: '',
                    email: '',
                    till_number: ''
                };
                this.loanSearchResults = [];
            },

            async exportRepayments() {
                try {
                    const params = new URLSearchParams(this.filters);
                    window.open(`/repayments/export?${params}`, '_blank');
                } catch (error) {
                    console.error('Error exporting repayments:', error);
                    alert('Error exporting repayments');
                }
            },

            viewPaymentHistory(schedule) {
                window.location.href = `/loans/${schedule.loan_id}/repayments`;
            },

            canEditRepayment() {
                const perms = window.AppUser?.permissions || [];
                if (perms.includes('loans.edit')) return true;
                const role = (window.AppUser?.role || '').toLowerCase();
                const slugs = (window.AppUser?.roleSlugs || []).map(s => s.toLowerCase());
                const adminRoles = ['admin', 'administrator', 'super_admin', 'superadmin'];
                return adminRoles.includes(role) || slugs.some(s => adminRoles.includes(s));
            },

            editRepayment(schedule) {
                // Pre-fill from schedule data — works even when no repayment is linked via schedule_id
                this.editForm = {
                    scheduleId: schedule.id,
                    amount: schedule.paid_amount || schedule.total_amount,
                    payment_method: schedule.latest_repayment?.payment_method || 'cash',
                    payment_date: (schedule.paid_date || '').split('T')[0],
                    reference_number: schedule.latest_repayment?.reference || '',
                    notes: ''
                };
                this.editError = null;
                this.showEditModal = true;
            },

            closeEditModal() {
                this.showEditModal = false;
                this.editError = null;
            },

            async submitEditRepayment() {
                if (!this.editForm.amount || !this.editForm.payment_method || !this.editForm.payment_date) {
                    this.editError = 'Amount, payment method and payment date are required.';
                    return;
                }
                this.editSubmitting = true;
                this.editError = null;
                try {
                    const response = await fetch(`/api/repayments/schedules/${this.editForm.scheduleId}/correct`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            amount: this.editForm.amount,
                            payment_method: this.editForm.payment_method,
                            payment_date: this.editForm.payment_date,
                            reference_number: this.editForm.reference_number || null,
                            notes: this.editForm.notes || null
                        })
                    });
                    const data = await response.json();
                    if (response.ok && data.success) {
                        this.closeEditModal();
                        await this.loadRepayments();
                        await this.loadStats();
                    } else {
                        this.editError = data.message || 'Failed to update payment.';
                        if (data.errors) {
                            this.editError += ' ' + Object.values(data.errors).flat().join(' ');
                        }
                    }
                } catch (err) {
                    this.editError = 'Network error. Please try again.';
                } finally {
                    this.editSubmitting = false;
                }
            },

            previousPage() {
                if (this.pagination.current_page > 1) {
                    this.pagination.current_page--;
                    this.loadRepayments();
                }
            },

            nextPage() {
                if (this.pagination.current_page < this.pagination.last_page) {
                    this.pagination.current_page++;
                    this.loadRepayments();
                }
            },

            goToPage(page) {
                this.pagination.current_page = page;
                this.loadRepayments();
            },

            getPageNumbers() {
                const pages = [];
                const current = this.pagination.current_page;
                const last = this.pagination.last_page;
                
                if (current > 3) pages.push(1);
                if (current > 4) pages.push('...');
                
                for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
                    pages.push(i);
                }
                
                if (current < last - 3) pages.push('...');
                if (current < last - 2) pages.push(last);
                
                return pages.filter(page => page !== '...' || pages.indexOf(page) === pages.lastIndexOf(page));
            },

            getInitials(firstName, lastName) {
                return (firstName?.charAt(0) || '') + (lastName?.charAt(0) || '');
            },

            getDaysOverdue(dueDate) {
                const today = new Date();
                const due = new Date(dueDate);
                const diffTime = today - due;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                return diffDays > 0 ? diffDays : 0;
            },

            formatCurrency(amount) {
                return new Intl.NumberFormat('en-TZ', {
                    style: 'currency',
                    currency: 'TZS',
                    minimumFractionDigits: 0
                }).format(amount);
            },

            formatDate(date) {
                try { const d = new Date(date); if (isNaN(d)) return date || ''; const dd = String(d.getDate()).padStart(2,'0'); const mm = String(d.getMonth()+1).padStart(2,'0'); const yyyy = d.getFullYear(); return dd+'/'+mm+'/'+yyyy; } catch(_) { return date || ''; }
            }
        }
    }
    </script>
    @endpush
@endsection