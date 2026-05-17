@extends('layouts.app')

@section('title', __('messages.new_loan_application'))
@section('page-title', __('messages.new_loan_application'))

@section('content')
<div x-data="loanCreate()" x-init="init()" class="max-w-4xl mx-auto">
    <form @submit.prevent="submitForm($event)" class="space-y-8">
        <!-- Header -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">{{ __('messages.new_loan_application') }}</h1>
                        <p class="mt-1 text-sm text-gray-500">{{ __('messages.create_loan_for_borrower') }}</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('loans.index') }}" 
                           class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            {{ __('messages.cancel') }}
                        </a>
                        <button type="submit" 
                                name="action"
                                value="save_and_new"
                                :disabled="submitting"
                                class="inline-flex items-center px-6 py-3 border border-blue-600 shadow-sm text-sm font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                            <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="submitting ? '{{ __('messages.saving') }}...' : '{{ __('messages.save_create_another') }}'"></span>
                        </button>
                        <button type="submit" 
                                name="action"
                                value="save"
                                :disabled="submitting"
                                class="inline-flex items-center px-6 py-3 border border-transparent shadow-lg text-sm font-semibold rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                            <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="submitting ? '{{ __('messages.creating_loan') }}...' : '{{ __('messages.create_loan') }}'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Borrower Selection -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">{{ __('messages.borrower_information') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.select_borrower_for_loan') }}</p>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Client Search -->
                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between mb-2">
                            <label for="client_search" class="block text-sm font-medium text-gray-700">
                                {{ __('messages.search_borrower') }} <span class="text-red-500">*</span>
                            </label>
                            <button type="button" @click="showBorrowerModal = true" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                                {{ __('messages.search_borrower') }}
                            </button>
                        </div>
                        <div class="relative">
                            <input type="text" 
                                   id="client_search"
                                   x-model="clientSearch"
                                   @input.debounce.300ms="searchClients()"
                                   placeholder="{{ __('messages.search_by_name_id_phone') }}"
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Search Results -->
                        <div x-show="clientSearchResults.length > 0" class="mt-2 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="client in clientSearchResults" :key="client.id">
                                <div @click="selectClient(client)" 
                                     class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900" x-text="client.first_name + ' ' + client.last_name"></div>
                                            <div class="text-sm text-gray-500">
                                                ID: <span x-text="client.id_number || 'N/A'"></span> | 
                                                Phone: <span x-text="client.phone || 'N/A'"></span>
                                            </div>
                                        </div>
                                        <div class="text-sm text-gray-400">
                                            <span x-text="client.status === 'active' ? 'Active' : 'Inactive'"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div x-show="showBorrowerModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
                        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl">
                            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('messages.search_borrower') }}</h3>
                                <button type="button" @click="showBorrowerModal = false" class="text-gray-500 hover:text-gray-700">&times;</button>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="relative">
                                    <input type="text" x-model="clientSearch" @input.debounce.300ms="searchClients()" placeholder="{{ __('messages.search_by_name_id_phone') }}" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </div>
                                </div>
                                <div class="mt-2 bg-white border border-gray-300 rounded-md shadow-sm max-h-72 overflow-y-auto">
                                    <template x-for="client in clientSearchResults" :key="client.id">
                                        <div @click="selectClient(client); showBorrowerModal = false" class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900" x-text="client.first_name + ' ' + client.last_name"></div>
                                                    <div class="text-sm text-gray-500">
                                                        ID: <span x-text="client.id_number || 'N/A'"></span> |
                                                        Phone: <span x-text="client.phone || 'N/A'"></span>
                                                    </div>
                                                </div>
                                                <div class="text-sm text-gray-400">
                                                    <span x-text="client.status === 'active' ? 'Active' : 'Inactive'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="clientSearch && clientSearchResults.length === 0" class="px-4 py-3 text-sm text-gray-500">{{ __('messages.no_borrowers_found') }}</div>
                                </div>
                            </div>
                            <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                                <button type="button" @click="showBorrowerModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">{{ __('messages.close') }}</button>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Client Display -->
                    <div x-show="form.client_id" class="md:col-span-2 bg-gray-50 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-900 mb-3">{{ __('messages.selected_borrower') }}</h3>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                        <span class="text-sm font-medium text-primary-700" x-text="getInitials(selectedClient?.first_name, selectedClient?.last_name)"></span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900" x-text="selectedClient?.first_name + ' ' + selectedClient?.last_name"></div>
                                    <div class="text-sm text-gray-500">
                                        ID: <span x-text="selectedClient?.id_number || 'N/A'"></span> | 
                                        Phone: <span x-text="selectedClient?.phone || 'N/A'"></span> |
                                        Email: <span x-text="selectedClient?.email || 'N/A'"></span>
                                    </div>
                                </div>
                            </div>
                            <button type="button" @click="clearClient()" class="text-red-600 hover:text-red-800 text-sm">
                                {{ __('messages.remove') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loan Product Selection -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">{{ __('messages.loan_product') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.select_loan_product_desc') }}</p>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Product Selection -->
                    <div class="md:col-span-2">
                        <label for="loan_product_id" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('messages.loan_product') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="loan_product_id" 
                                x-model="form.loan_product_id"
                                @change="onProductChange()"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="">{{ __('messages.select_a_loan_product') }}</option>
                            <template x-for="product in loanProducts" :key="product.id">
                                <option :value="product.id" x-text="product.name"></option>
                            </template>
                        </select>
                        <div x-show="errors.loan_product_id" class="mt-1 text-sm text-red-600" x-text="errors.loan_product_id"></div>
                    </div>

                    <!-- Group Selection (shown only for Group Loan product) -->
                    <div class="md:col-span-2" x-show="isGroupLoan" x-cloak>
                        <label for="group_id" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('messages.select_group') ?? 'Select Group' }} <span class="text-red-500">*</span>
                        </label>
                        <select id="group_id"
                                x-model="form.group_id"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="">-- {{ __('messages.select_group') ?? 'Select a group' }} --</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ __('messages.group_loan_hint') ?? 'Group Loan requires selecting a group. Create a group first if none exist.' }}
                            <a href="{{ route('groups.create') }}" target="_blank" class="text-primary-600 underline ml-1">{{ __('messages.create_group') ?? 'Create Group' }}</a>
                        </p>
                        <div x-show="errors.group_id" class="mt-1 text-sm text-red-600" x-text="errors.group_id"></div>
                    </div>

                    <!-- Product Details -->
                    <div x-show="selectedProduct" class="md:col-span-2 bg-blue-50 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-blue-900 mb-3">{{ __('messages.product_details') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-blue-700 font-medium">{{ __('messages.interest_rate_per_month') }}:</span>
                                <span class="text-blue-900" x-text="selectedProduct?.interest_rate + '%'"> </span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">{{ __('messages.min_amount') }}:</span>
                                <span class="text-blue-900" x-text="formatCurrency(selectedProduct?.min_amount)"></span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">{{ __('messages.max_amount') }}:</span>
                                <span class="text-blue-900" x-text="formatCurrency(selectedProduct?.max_amount)"></span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">{{ __('messages.min_term') }}:</span>
                                <span x-text="getTermInUserUnits(selectedProduct?.min_term, form.repayment_schedule)"></span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">{{ __('messages.max_term') }}:</span>
                                <span x-text="getTermInUserUnits(selectedProduct?.max_term, form.repayment_schedule)"></span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">{{ __('messages.processing_fee') }}:</span>
                                <span class="text-blue-900" x-text="(selectedProduct?.processing_fee ?? selectedProduct?.processing_fee_rate) + '%'"> </span>
                            </div>
                        </div>
                    </div>

                    <!-- Principal Amount -->
                    <div>
                        <label for="principal_amount" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('messages.principal_amount') }} (TZS) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="principal_amount"
                               x-model="form.principal_amount"
                               @input="calculateLoan()"
                               step="1000"
                               placeholder="{{ __('messages.enter_loan_amount') }}"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        <div x-show="errors.principal_amount" class="mt-1 text-sm text-red-600" x-text="errors.principal_amount"></div>
                    </div>

                    <!-- Term -->
                    <div>
                        <label for="term" class="block text-sm font-medium text-gray-700 mb-2">
                            <span x-text="form.repayment_schedule === 'daily' ? 'Term (Days)' : (form.repayment_schedule === 'weekly' ? 'Term (Weeks)' : (form.repayment_schedule === 'biweekly' ? 'Term (14-day periods)' : 'Term (Months)'))"></span>
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="term"
                               x-model="form.term"
                               @input="calculateLoan()"
                               :min="getMinTermInUserUnits(selectedProduct?.min_term || 1, form.repayment_schedule)"
                               :max="getMaxTermInUserUnits(selectedProduct?.max_term || 120, form.repayment_schedule)"
                               :placeholder="form.repayment_schedule === 'daily' ? 'Enter number of days' : (form.repayment_schedule === 'weekly' ? 'Enter number of weeks' : (form.repayment_schedule === 'biweekly' ? 'Enter number of 14-day periods' : 'Enter number of months'))"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        <div x-show="errors.term" class="mt-1 text-sm text-red-600" x-text="errors.term"></div>
                    </div>

                    <!-- Repayment Schedule -->
                    <div>
                        <label for="repayment_schedule" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('messages.repayment_schedule') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="repayment_schedule"
                                x-model="form.repayment_schedule"
                                @change="calculateLoan()"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="">{{ __('messages.select_schedule') }}</option>
                            <option value="daily">{{ __('messages.daily') }}</option>
                            <option value="weekly">{{ __('messages.weekly') }}</option>
                            <option value="biweekly">{{ __('messages.biweekly') }}</option>
                            <option value="monthly">{{ __('messages.monthly') }}</option>
                        </select>
                        <div x-show="errors.repayment_schedule" class="mt-1 text-sm text-red-600" x-text="errors.repayment_schedule"></div>
                    </div>

                    <!-- Interest Rate -->
                    <div>
                        <label for="interest_rate" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('messages.interest_rate_per_month') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="interest_rate"
                               x-model="form.interest_rate"
                               @input="calculateLoan()"
                               step="0.01"
                               placeholder="Enter monthly interest rate"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        <div x-show="errors.interest_rate" class="mt-1 text-sm text-red-600" x-text="errors.interest_rate"></div>
                    </div>

                    <!-- Interest Method -->
                    <div>
                        <label for="interest_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Interest Method <span class="text-red-500">*</span>
                        </label>
                        <select id="interest_type"
                                x-model="form.interest_type"
                                @change="calculateLoan()"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="flat">Flat Rate</option>
                            <option value="reducing">Reducing Balance</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            <span x-show="form.interest_type === 'flat'">Interest is calculated on the original principal for the full term.</span>
                            <span x-show="form.interest_type === 'reducing'">Interest is calculated on the outstanding balance each period.</span>
                        </p>
                    </div>

                    <!-- Management Fee Type -->
                    <div>
                        <label for="processing_fee_type" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('messages.management_fee_type') }}
                        </label>
                        <select id="processing_fee_type"
                                x-model="form.processing_fee_type"
                                @change="calculateLoan()"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="percentage">{{ __('messages.percentage') }} (%)</option>
                            <option value="fixed">{{ __('messages.fixed_amount') }} (TZS)</option>
                        </select>
                    </div>

                    <!-- Management Fee -->
                    <div>
                        <label for="processing_fee_rate" class="block text-sm font-medium text-gray-700 mb-2">
                            <span x-text="form.processing_fee_type === 'percentage' ? 'Management Fee (%)' : 'Management Fee (TZS)'"></span>
                        </label>
                        <input type="number" 
                               id="processing_fee_rate"
                               x-model="form.processing_fee_rate"
                               @input="calculateLoan()"
                               step="0.01"
                               :placeholder="form.processing_fee_type === 'percentage' ? 'Enter percentage' : 'Enter amount in TZS'"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        <div x-show="errors.processing_fee_rate" class="mt-1 text-sm text-red-600" x-text="errors.processing_fee_rate"></div>
                    </div>

                    <!-- Late Payment Penalty -->
                    <div class="md:col-span-2 border border-orange-200 rounded-lg p-4 bg-orange-50">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="text-sm font-semibold text-orange-800">{{ __('messages.late_payment_penalty') }}</h3>
                                <p class="text-xs text-orange-600 mt-0.5">{{ __('messages.penalty_section_desc') }} <span class="font-medium">({{ __('messages.optional') }})</span></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.penalty_type') }}</label>
                                <select id="loan_penalty_type"
                                        x-model="form.penalty_type"
                                        @change="toggleLoanPenalty()"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    <option value="none">{{ __('messages.penalty_none') }}</option>
                                    <option value="percentage">{{ __('messages.penalty_percentage') }}</option>
                                    <option value="fixed">{{ __('messages.penalty_fixed') }}</option>
                                </select>
                            </div>
                            <div id="loan_penalty_value_wrap" :style="form.penalty_type === 'none' ? 'opacity:0.4' : ''">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.penalty_value') }}</label>
                                <input type="number"
                                       x-model="form.penalty_value"
                                       :disabled="form.penalty_type === 'none'"
                                       step="0.01" min="0"
                                       placeholder="0"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div id="loan_penalty_freq_wrap" :style="form.penalty_type === 'none' ? 'opacity:0.4' : ''">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.penalty_frequency') }}</label>
                                <select x-model="form.penalty_frequency"
                                        :disabled="form.penalty_type === 'none'"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    <option value="1">{{ __('messages.penalty_freq_daily') }}</option>
                                    <option value="7">{{ __('messages.penalty_freq_weekly') }}</option>
                                    <option value="14">{{ __('messages.penalty_freq_biweekly') }}</option>
                                    <option value="30">{{ __('messages.penalty_freq_monthly') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Loan Calculation -->
        <div x-show="loanCalculation.monthly_payment" class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">{{ __('messages.loan_calculation') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.review_loan_terms') }}</p>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-500"
                             x-text="form.repayment_type === 'interest_only'
                                ? 'Interest per installment (last = interest + principal)'
                                : (form.repayment_schedule === 'daily' ? 'Daily Payment' : (form.repayment_schedule === 'weekly' ? 'Weekly Payment' : (form.repayment_schedule === 'biweekly' ? 'Biweekly Payment' : 'Monthly Payment')))">
                        </div>
                        <div class="text-2xl font-bold text-gray-900" x-text="formatCurrency(loanCalculation.monthly_payment)"></div>
                        <div x-show="form.repayment_type === 'interest_only'" class="mt-1 text-xs text-orange-600">
                            Last installment: <span x-text="formatCurrency((loanCalculation.monthly_payment || 0) + parseFloat(form.principal_amount || 0))"></span>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-500">{{ __('messages.total_interest') }}</div>
                        <div class="text-2xl font-bold text-gray-900" x-text="formatCurrency(loanCalculation.total_interest)"></div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-500">{{ __('messages.total_amount') }}</div>
                        <div class="text-2xl font-bold text-gray-900" x-text="formatCurrency(loanCalculation.total_amount)"></div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-500">{{ __('messages.processing_fee') }}</div>
                        <div class="text-xl font-semibold text-gray-900" x-text="formatCurrency(loanCalculation.processing_fee)"></div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-500">{{ __('messages.net_disbursement') }}</div>
                        <div class="text-xl font-semibold text-gray-900" x-text="formatCurrency(loanCalculation.net_disbursement)"></div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-500">{{ __('messages.first_payment_date') }}</div>
                        <div class="text-lg font-semibold text-gray-900" x-text="formatDate(loanCalculation.first_payment_date)"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">{{ __('messages.additional_information') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.optional_loan_details') }}</p>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 gap-6">
                    <!-- Application Date -->
                    <div>
                        <label for="application_date" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('messages.applied') }} (Application Date)
                        </label>
                        <input type="date" id="application_date"
                               x-model="form.application_date"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-400">Date the client submitted the loan application. Defaults to today if left blank.</p>
                    </div>

                    <!-- Purpose -->
                    <div>
                        <label for="purpose" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('messages.loan_purpose') }}
                        </label>
                        <select id="purpose" 
                                x-model="form.purpose"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="">{{ __('messages.select_purpose') }}</option>
                            <option value="business">{{ __('messages.business') }}</option>
                            <option value="agriculture">{{ __('messages.agriculture') }}</option>
                            <option value="education">{{ __('messages.education') }}</option>
                            <option value="healthcare">{{ __('messages.healthcare') }}</option>
                            <option value="housing">{{ __('messages.housing') }}</option>
                            <option value="emergency">{{ __('messages.emergency') }}</option>
                            <option value="other">{{ __('messages.other') }}</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('messages.notes') }}
                        </label>
                        <textarea id="notes" 
                                  x-model="form.notes"
                                  rows="3"
                                  placeholder="Enter any additional notes or comments..."
                                  class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"></textarea>
                    </div>

                    <!-- Collateral Required -->
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="collateral_required"
                               x-model="form.collateral_required"
                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="collateral_required" class="ml-2 block text-sm text-gray-900">
                            {{ __('messages.collateral_required') }}
                        </label>
                    </div>

                    <!-- Collateral Information (shown when checkbox is ticked) -->
                    <div x-show="form.collateral_required" x-cloak class="border border-gray-200 rounded-lg p-4 bg-yellow-50 space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ __('messages.collateral_information') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.collateral_type') }} <span class="text-red-500">*</span></label>
                                <select x-model="form.collateral.type" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    <option value="">{{ __('messages.select_collateral_type') }}</option>
                                    <option value="land">{{ __('messages.land') }}</option>
                                    <option value="building">{{ __('messages.building') }}</option>
                                    <option value="vehicle">{{ __('messages.vehicle') }}</option>
                                    <option value="machinery">{{ __('messages.machinery') }}</option>
                                    <option value="livestock">{{ __('messages.livestock') }}</option>
                                    <option value="inventory">{{ __('messages.inventory') }}</option>
                                    <option value="equipment">{{ __('messages.equipment') }}</option>
                                    <option value="other">{{ __('messages.other') }}</option>
                                </select>
                            </div>
                            <div x-show="form.collateral.type === 'other'" class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.specify_other_collateral') }} <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.collateral.other_type" placeholder="Please specify the collateral type" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.current_value') }} (TZS)</label>
                                <input type="number" x-model="form.collateral.value" placeholder="0.00" step="0.01" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.buying_price') }} (TZS)</label>
                                <input type="number" x-model="form.collateral.buying_price" placeholder="0.00" step="0.01" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.selling_price') }} (TZS)</label>
                                <input type="number" x-model="form.collateral.selling_price" placeholder="0.00" step="0.01" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.description') }}</label>
                                <input type="text" x-model="form.collateral.description" placeholder="Brief description" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Guarantor Required -->
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="guarantor_required"
                               x-model="form.guarantor_required"
                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="guarantor_required" class="ml-2 block text-sm text-gray-900">
                            {{ __('messages.guarantor_required') }}
                        </label>
                    </div>

                    <!-- Guarantor Information (shown when checkbox is ticked) -->
                    <div x-show="form.guarantor_required" x-cloak class="border border-gray-200 rounded-lg p-4 bg-blue-50 space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ __('messages.guarantor_information') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.guarantor_type') }} <span class="text-red-500">*</span></label>
                                <select x-model="form.guarantor.type" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    <option value="">{{ __('messages.select_relationship') }}</option>
                                    <option value="spouse">{{ __('messages.spouse') }}</option>
                                    <option value="sister">{{ __('messages.sister') }}</option>
                                    <option value="brother">{{ __('messages.brother') }}</option>
                                    <option value="parent">{{ __('messages.parent') }}</option>
                                    <option value="friend">{{ __('messages.friend') }}</option>
                                    <option value="colleague">{{ __('messages.colleague') }}</option>
                                    <option value="other">{{ __('messages.other') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.full_name') }} <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.guarantor.name" placeholder="Guarantor's full name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.phone_number') }} <span class="text-red-500">*</span></label>
                                <input type="tel" x-model="form.guarantor.phone" placeholder="0712345678" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.email_address') }} <span class="text-gray-400">({{ __('messages.optional') }})</span></label>
                                <input type="email" x-model="form.guarantor.email" placeholder="email@example.com" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.street_village') }}</label>
                                <input type="text" x-model="form.guarantor.street" placeholder="Street address" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.ward') }}</label>
                                <input type="text" x-model="form.guarantor.ward" placeholder="Ward name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.district') }}</label>
                                <input type="text" x-model="form.guarantor.district" placeholder="District name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.region') }}</label>
                                <input type="text" x-model="form.guarantor.region" placeholder="Region name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Local Government Authority Information -->
                    <div class="border border-gray-200 rounded-lg p-4 bg-green-50 space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ __('messages.lga_information') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.officer_name') }}</label>
                                <input type="text" x-model="form.lga.officer_name" placeholder="Street/Ward officer name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.position') }}</label>
                                <input type="text" x-model="form.lga.position" placeholder="e.g., Ward Executive Officer" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.phone_number') }}</label>
                                <input type="tel" x-model="form.lga.phone" placeholder="0712345678" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.street_village') }}</label>
                                <input type="text" x-model="form.lga.street" placeholder="Street name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.ward') }}</label>
                                <input type="text" x-model="form.lga.ward" placeholder="Ward name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.district') }}</label>
                                <input type="text" x-model="form.lga.district" placeholder="District name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.region') }}</label>
                                <input type="text" x-model="form.lga.region" placeholder="Region name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Attachments Section -->
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    {{ __('messages.loan_attachments') }}
                                </label>
                                <p class="text-xs text-gray-500">{{ __('messages.attachments_hint') }}</p>
                            </div>
                            <button type="button" @click="addAttachment()" 
                                    x-show="attachments.length < 10"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                {{ __('messages.add_attachment') }}
                            </button>
                        </div>
                        
                        <div class="space-y-3">
                            <template x-for="(att, index) in attachments" :key="index">
                                <div class="flex items-center gap-3 bg-white p-3 rounded-md border border-gray-200">
                                    <div class="flex-1">
                                        <select x-model="att.type" class="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 mb-2">
                                            <option value="loan_contract">Loan Contract</option>
                                            <option value="spouse_consent">Spouse Consent</option>
                                            <option value="guarantor_form">Guarantor Form</option>
                                            <option value="collateral">Collateral</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <input type="file" 
                                               @change="handleFileSelect($event, index)"
                                               accept=".pdf,.jpg,.jpeg,.png"
                                               class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer">
                                        <p x-show="att.error" class="mt-1 text-xs text-red-600" x-text="att.error"></p>
                                        <p x-show="att.file && !att.error" class="mt-1 text-xs text-green-600">
                                            <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span x-text="att.file?.name"></span> 
                                            (<span x-text="formatFileSize(att.file?.size)"></span>)
                                        </p>
                                    </div>
                                    <button type="button" @click="removeAttachment(index)" class="text-red-500 hover:text-red-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                            
                            <div x-show="attachments.length === 0" class="text-center py-4 text-sm text-gray-500">
                                {{ __('messages.no_attachments') }}
                </div>
            </div>
        </div>

        <!-- Form Footer -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('loans.index') }}"
                   class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200">
                    {{ __('messages.cancel') }}
                </a>
                <button type="submit"
                        name="action"
                        value="save_and_new"
                        :disabled="submitting"
                        class="inline-flex items-center px-6 py-3 border border-blue-600 shadow-sm text-sm font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                    <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="submitting ? '{{ __('messages.saving') }}...' : '{{ __('messages.save_create_another') }}'"></span>
                </button>
                <button type="submit" 
                        name="action"
                        value="save"
                        :disabled="submitting"
                        class="inline-flex items-center px-6 py-3 border border-transparent shadow-lg text-sm font-semibold rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                    <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="submitting ? '{{ __('messages.creating_loan') }}...' : '{{ __('messages.create_loan') }}'"></span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function loanCreate() {
    return {
        form: {
            client_id: '',
            loan_product_id: '',
            group_id: '',
            principal_amount: '',
            term: '',
            repayment_schedule: 'monthly',
            interest_rate: 10,
            interest_type: 'flat',
            repayment_type: 'amortized',
            processing_fee_type: 'percentage',
            processing_fee_rate: 0,
            penalty_type: 'none',
            penalty_value: 0,
            penalty_frequency: 30,
            application_date: new Date().toISOString().split('T')[0],
            purpose: '',
            notes: '',
            collateral_required: false,
            collateral: {
                type: '',
                value: '',
                buying_price: '',
                selling_price: '',
                description: ''
            },
            guarantor_required: false,
            guarantor: {
                type: '',
                name: '',
                phone: '',
                email: '',
                street: '',
                ward: '',
                district: '',
                region: ''
            },
            lga: {
                officer_name: '',
                position: '',
                phone: '',
                street: '',
                ward: '',
                district: '',
                region: ''
            }
        },
        errors: {},
        submitting: false,
        clientSearch: '',
        clientSearchResults: [],
        showBorrowerModal: false,
        selectedClient: null,
        loanProducts: (window.initialLoanProducts || @json($products ?? [])),
        selectedProduct: null,
        loanCalculation: {},
        attachments: [],
        isGroupLoan: false,

        toggleLoanPenalty() {
            // reactivity handled via x-model and :disabled/:style bindings
        },

        getTermInUserUnits(termInMonths, repaymentSchedule) {
            if (!termInMonths) return '';
            
            let termInUserUnits = termInMonths;
            let unit = 'months';
            
            if (repaymentSchedule === 'daily') {
                termInUserUnits = termInMonths * 30;
                unit = 'days';
            } else if (repaymentSchedule === 'weekly') {
                termInUserUnits = termInMonths * 4;
                unit = 'weeks';
            } else if (repaymentSchedule === 'biweekly') {
                termInUserUnits = termInMonths * 2;
                unit = '14-day periods';
            }
            
            return `${termInUserUnits} ${unit}`;
        },

        getMinTermInUserUnits(minTermInMonths, repaymentSchedule) {
            if (!minTermInMonths) return 1;
            
            let minTermInUserUnits = minTermInMonths;
            
            if (repaymentSchedule === 'daily') {
                minTermInUserUnits = minTermInMonths * 30;
            } else if (repaymentSchedule === 'weekly') {
                minTermInUserUnits = minTermInMonths * 4;
            } else if (repaymentSchedule === 'biweekly') {
                minTermInUserUnits = minTermInMonths * 2;
            }
            
            return minTermInUserUnits;
        },

        getMaxTermInUserUnits(maxTermInMonths, repaymentSchedule) {
            if (!maxTermInMonths) return 120;
            
            let maxTermInUserUnits = maxTermInMonths;
            
            if (repaymentSchedule === 'daily') {
                maxTermInUserUnits = maxTermInMonths * 30;
            } else if (repaymentSchedule === 'weekly') {
                maxTermInUserUnits = maxTermInMonths * 4;
            } else if (repaymentSchedule === 'biweekly') {
                maxTermInUserUnits = maxTermInMonths * 2;
            }
            
            return maxTermInUserUnits;
        },

        async init() {
            try { await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' }); } catch (e) {}
            await this.loadLoanProducts();
            this.prefillFromQuery();
        },

        async loadLoanProducts() {
            try {
                const response = await fetch('/api/loan-products', { credentials: 'same-origin' });
                const json = await response.json();
                this.loanProducts = Array.isArray(json) ? json : (Array.isArray(json?.data) ? json.data : this.loanProducts);
            } catch (error) {
                console.error('Error loading loan products:', error);
            }
        },

        async prefillFromQuery() {
            const params = new URLSearchParams(window.location.search);
            const clientId = params.get('client_id');
            const productId = params.get('product_id');

            // Prefill client by fetching details
            if (clientId) {
                try {
                    const resp = await fetch(`/clients/json/${encodeURIComponent(clientId)}`, { credentials: 'same-origin' });
                    const data = await resp.json();
                    const client = data?.data || data;
                    if (client?.id) {
                        this.selectClient(client);
                    }
                } catch (e) {
                    console.error('Failed to prefill borrower from query:', e);
                }
            }

            // Prefill product and apply details
            if (productId) {
                this.form.loan_product_id = productId;
                this.onProductChange();
            }
        },

        async searchClients() {
            const q = (this.clientSearch || '').trim();
            if (q.length < 2) {
                this.clientSearchResults = [];
                return;
            }

            try {
                // Primary: restricted session-auth search endpoint
                const primary = await fetch(`/clients/search?query=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                let raw1 = {};
                try { raw1 = await primary.json(); } catch (_) { raw1 = {}; }
                if (primary.ok && Array.isArray(raw1?.data)) {
                    this.clientSearchResults = raw1.data;
                    return;
                }

                // Fallback: broader data endpoint without role restriction, paginated
                const fallback = await fetch(`/clients/data?search=${encodeURIComponent(q)}&per_page=10&status=all`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                let raw2 = {};
                try { raw2 = await fallback.json(); } catch (_) { raw2 = {}; }
                if (fallback.ok) {
                    const rows = Array.isArray(raw2?.data) ? raw2.data : (Array.isArray(raw2?.data?.data) ? raw2.data.data : []);
                    this.clientSearchResults = rows;
                    return;
                }

                console.error('Borrower search failed:', primary.status, raw1?.message, fallback.status, raw2?.message);
                this.clientSearchResults = [];
            } catch (error) {
                console.error('Error searching borrowers:', error);
                this.clientSearchResults = [];
            }
        },

        selectClient(client) {
            this.selectedClient = client;
            this.form.client_id = client.id;
            this.clientSearch = `${client.first_name} ${client.last_name}`;
            this.clientSearchResults = [];
            this.errors.client_id = '';
        },

        clearClient() {
            this.selectedClient = null;
            this.form.client_id = '';
            this.clientSearch = '';
            this.clientSearchResults = [];
        },

        onProductChange() {
            this.selectedProduct = this.loanProducts.find(p => p.id == this.form.loan_product_id);
            if (this.selectedProduct) {
                this.form.interest_rate = (this.selectedProduct.interest_rate ?? 10);
                const rawIt = (this.selectedProduct.interest_type ?? 'flat').toLowerCase();
                this.form.interest_type = (rawIt === 'reducing' || rawIt === 'reducing_balance' || rawIt === 'reducing-balance') ? 'reducing' : 'flat';
                this.form.repayment_type = (this.selectedProduct.repayment_type ?? 'amortized');
                this.form.processing_fee_type = (this.selectedProduct.processing_fee_type ?? 'percentage');
                if (this.form.processing_fee_type === 'fixed') {
                    this.form.processing_fee_rate = (this.selectedProduct.processing_fee ?? 0);
                } else {
                    this.form.processing_fee_rate = (this.selectedProduct.processing_fee_rate ?? this.selectedProduct.processing_fee ?? 0);
                }
                this.isGroupLoan = (this.selectedProduct.name || '').toLowerCase().includes('group');
                if (!this.isGroupLoan) this.form.group_id = '';
                // Auto-fill penalty from product defaults (user can override)
                if (this.selectedProduct.penalty_type && this.selectedProduct.penalty_type !== 'none') {
                    this.form.penalty_type = this.selectedProduct.penalty_type;
                    this.form.penalty_value = this.selectedProduct.penalty_value || 0;
                    this.form.penalty_frequency = this.selectedProduct.penalty_frequency || 30;
                }
                this.calculateLoan();
            } else {
                this.isGroupLoan = false;
                this.form.group_id = '';
            }
            this.errors.loan_product_id = '';
        },

        calculateLoan() {
            if (!this.form.principal_amount || !this.form.term || !this.form.interest_rate || this.form.principal_amount <= 0 || this.form.term <= 0) {
                this.loanCalculation = {};
                return;
            }

            const principal = parseFloat(this.form.principal_amount);
            const term = parseInt(this.form.term); // number of installments
            const monthlyRate = parseFloat(this.form.interest_rate) / 100; // interest rate is ALWAYS per month
            const processingFeeInput = parseFloat(this.form.processing_fee_rate || 0);
            const processingFeeType = this.form.processing_fee_type || 'percentage';
            const repaymentSchedule = this.form.repayment_schedule || 'monthly';
            const it = (this.form.interest_type || 'flat').toLowerCase();
            const isReducing = it === 'reducing' || it === 'reducing_balance';
            const repaymentType = (this.form.repayment_type || 'amortized');

            // Convert term to months based on repayment schedule
            // Interest rate is always monthly, so we need to know how many months the loan covers
            let termInMonths = term;
            if (repaymentSchedule === 'daily') {
                termInMonths = term / 30; // 30 days = 1 month
            } else if (repaymentSchedule === 'weekly') {
                termInMonths = term / 4; // 4 weeks = 1 month
            } else if (repaymentSchedule === 'biweekly') {
                termInMonths = term / 2; // 2 biweekly periods (28 days) ≈ 1 month
            }

            let installmentPayment;
            let totalAmount;
            let totalInterest;

            if (repaymentType === 'interest_only') {
                // Special/Agriculture: each installment = interest only (principal * rate)
                // Last installment = interest + full principal (balloon)
                installmentPayment = principal * monthlyRate; // interest-only amount per period
                totalInterest = installmentPayment * term;
                totalAmount = totalInterest + principal; // total interest across all periods + principal
            } else if (isReducing && monthlyRate > 0) {
                // Reducing balance: convert monthly rate to per-installment rate
                let ratePerInstallment = monthlyRate;
                if (repaymentSchedule === 'daily') {
                    ratePerInstallment = monthlyRate / 30;
                } else if (repaymentSchedule === 'weekly') {
                    ratePerInstallment = monthlyRate / 4;
                } else if (repaymentSchedule === 'biweekly') {
                    ratePerInstallment = monthlyRate / 2;
                }
                
                // Correct PMT formula for reducing balance
                if (ratePerInstallment > 0) {
                    installmentPayment = principal * (ratePerInstallment * Math.pow(1 + ratePerInstallment, term)) / (Math.pow(1 + ratePerInstallment, term) - 1);
                } else {
                    installmentPayment = principal / term;
                }
                
                totalAmount = installmentPayment * term;
                totalInterest = totalAmount - principal;
            } else {
                // Flat interest: total interest = principal * monthlyRate * termInMonths
                totalInterest = principal * monthlyRate * termInMonths;
                const interestPerInstallment = totalInterest / term;
                const principalPerInstallment = principal / term;
                installmentPayment = principalPerInstallment + interestPerInstallment;
                totalAmount = principal + totalInterest;
            }
            // Calculate processing fee based on type
            let processingFee = 0;
            if (processingFeeType === 'fixed') {
                processingFee = processingFeeInput;
            } else {
                processingFee = (principal * processingFeeInput / 100);
            }
            const netDisbursement = principal - processingFee;

            // Calculate first payment date based on repayment schedule
            const firstPaymentDate = new Date();
            let daysToAdd = 30; // default for monthly
            if (repaymentSchedule === 'daily') {
                daysToAdd = 1;
            } else if (repaymentSchedule === 'weekly') {
                daysToAdd = 7;
            } else if (repaymentSchedule === 'biweekly') {
                daysToAdd = 14;
            }
            firstPaymentDate.setDate(firstPaymentDate.getDate() + daysToAdd);

            this.loanCalculation = {
                monthly_payment: installmentPayment,
                total_amount: totalAmount,
                total_interest: totalInterest,
                processing_fee: processingFee,
                net_disbursement: netDisbursement,
                first_payment_date: firstPaymentDate.toISOString().split('T')[0]
            };
        },

        async submitForm(event) {
            this.errors = {};
            this.submitting = true;

            try {
                const clickedButton = event?.submitter;
                const actionValue = clickedButton?.value || 'save';

                const payload = {
                    client_id: this.form.client_id,
                    group_id: this.form.group_id || null,
                    product_id: this.form.loan_product_id,
                    principal: parseFloat(this.form.principal_amount || 0),
                    term: parseInt(this.form.term || 0),
                    repayment_schedule: this.form.repayment_schedule,
                    interest_rate: parseFloat(this.form.interest_rate || 0),
                    interest_type: this.form.interest_type || 'flat',
                    first_payment_date: this.loanCalculation.first_payment_date,
                    application_date: this.form.application_date || null,
                    purpose: this.form.purpose || null,
                    collateral_required: this.form.collateral_required,
                    collateral: this.form.collateral_required ? this.form.collateral : null,
                    guarantor_required: this.form.guarantor_required,
                    guarantor: this.form.guarantor_required ? this.form.guarantor : null,
                    lga: this.form.lga,
                    processing_fee_type: this.form.processing_fee_type,
                    processing_fee_rate: parseFloat(this.form.processing_fee_rate || 0),
                    penalty_type: this.form.penalty_type || 'none',
                    penalty_value: parseFloat(this.form.penalty_value || 0),
                    penalty_frequency: parseInt(this.form.penalty_frequency || 30),
                };

                if (!payload.first_payment_date) {
                    this.calculateLoan();
                    payload.first_payment_date = this.loanCalculation?.first_payment_date;
                    if (!payload.first_payment_date) {
                        alert('First payment date was not calculated. Please fill principal, term, repayment schedule, and interest rate.');
                        this.submitting = false;
                        return;
                    }
                }

                const response = await fetch('/loans', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload),
                    credentials: 'same-origin'
                });

                let data = null;
                const contentType = (response.headers.get('content-type') || '').toLowerCase();
                if (contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    const text = await response.text();
                    data = { message: text };
                }

                if (response.ok) {
                    if (response.url && response.url.includes('/login')) {
                        alert('Your session has expired. Please log in again.');
                        window.location.href = response.url;
                        return;
                    }

                    const id = data?.data?.id ?? data?.id;
                    if (id) {
                        try { await this.uploadAttachments(id); } catch (e) { console.error('Attachment upload failed:', e); }
                        
                        // Handle save_and_new action
                        if (actionValue === 'save_and_new') {
                            // Reset form to create another loan
                            this.resetForm();
                            alert('Loan created successfully. You can now create another loan.');
                            window.scrollTo(0, 0);
                            return;
                        }
                        
                        window.location.href = `/loans/${id}`;
                        return;
                    }

                    const message = (data && data.message) ? data.message : null;
                    alert(message || 'Loan was created but no record ID was returned by the server.');
                    return;
                } else {
                    if (data && data.errors) {
                        this.errors = data.errors;
                        // Show field + message so user knows what's wrong
                        const firstField = Object.keys(data.errors)[0];
                        const firstMsg = Object.values(data.errors).flat()[0];
                        alert('Error in field "' + firstField + '": ' + firstMsg);
                    } else {
                        const message = (data && data.message) ? data.message : null;
                        alert(message || `Error creating loan application (HTTP ${response.status})`);
                    }
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                alert('Error creating loan application');
            } finally {
                this.submitting = false;
            }
        },

        getInitials(firstName, lastName) {
            return (firstName?.charAt(0) || '') + (lastName?.charAt(0) || '');
        },

        formatCurrency(amount) {
            const s = window.AppSettings || {};
            const locale = s.locale || 'en-TZ';
            const currency = s.currency || 'TZS';
            return new Intl.NumberFormat(locale, {
                style: 'currency',
                currency,
                minimumFractionDigits: 0
            }).format(amount);
        },

        formatDate(date) {
            const s = window.AppSettings || {};
            const locale = s.locale || 'en-US';
            return new Date(date).toLocaleDateString(locale, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        // Attachment management methods
        addAttachment() {
            if (this.attachments.length < 10) {
                this.attachments.push({ type: 'loan_contract', file: null, error: null });
            }
        },

        removeAttachment(index) {
            this.attachments.splice(index, 1);
        },

        handleFileSelect(event, index) {
            const file = event.target.files[0];
            if (!file) {
                this.attachments[index].file = null;
                this.attachments[index].error = null;
                return;
            }

            const maxSize = 1000 * 1024 * 1024; // 1000MB
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
            
            if (!allowedTypes.includes(file.type)) {
                this.attachments[index].error = 'Only PDF, JPG, and PNG files are allowed';
                this.attachments[index].file = null;
                return;
            }
            
            if (file.size > maxSize) {
                this.attachments[index].error = `File exceeds 1000MB limit (${this.formatFileSize(file.size)})`;
                this.attachments[index].file = null;
                return;
            }

            this.attachments[index].file = file;
            this.attachments[index].error = null;
        },

        formatFileSize(bytes) {
            if (!bytes) return '0 KB';
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
            if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return bytes + ' bytes';
        },

        async uploadAttachments(loanId) {
            const validAttachments = this.attachments.filter(a => a.file && !a.error);
            if (validAttachments.length === 0) return;

            const formData = new FormData();
            validAttachments.forEach((att, idx) => {
                formData.append('files[]', att.file);
                formData.append('attachment_types[]', att.type);
            });

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const resp = await fetch(`/loans/${loanId}/documents`, {
                method: 'POST',
                headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {},
                body: formData,
                credentials: 'same-origin'
            });
            if (!resp.ok) {
                let err;
                try { err = await resp.json(); } catch (_) {}
                throw new Error(err?.message || 'Upload failed');
            }
        },

        resetForm() {
            this.form = {
                client_id: '',
                loan_product_id: '',
                principal_amount: '',
                term: '',
                repayment_schedule: 'monthly',
                interest_rate: 10,
                processing_fee_type: 'percentage',
                processing_fee_rate: 0,
                penalty_type: 'none',
                penalty_value: 0,
                penalty_frequency: 30,
                purpose: '',
                notes: '',
                collateral_required: false,
                collateral: {
                    type: '',
                    value: '',
                    buying_price: '',
                    selling_price: '',
                    description: ''
                },
                guarantor_required: false,
                guarantor: {
                    type: '',
                    name: '',
                    phone: '',
                    email: '',
                    street: '',
                    ward: '',
                    district: '',
                    region: ''
                },
                lga: {
                    officer_name: '',
                    position: '',
                    phone: '',
                    street: '',
                    ward: '',
                    district: '',
                    region: ''
                }
            };
            this.selectedClient = null;
            this.clientSearch = '';
            this.clientSearchResults = [];
            this.selectedProduct = null;
            this.loanCalculation = {};
            this.attachments = [];
            this.errors = {};
        }
    }
}
</script>

@endpush
@endsection