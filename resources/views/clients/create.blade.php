@extends('layouts.app')

@section('title', __('messages.add_new_borrower'))
@section('page-title', __('messages.add_new_borrower'))

@section('content')
<div x-data="clientCreate()" x-init="init()" class="max-w-4xl mx-auto">
    <!-- Error Display -->
    <div x-show="Object.keys(errors).length > 0" class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">{{ __('messages.error_submission') }}</h3>
                <div class="mt-2 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        <template x-for="(error, field) in errors" :key="field">
                            <li x-text="Array.isArray(error) ? error[0] : error"></li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <form @submit.prevent="submitForm($event)" class="space-y-6" enctype="multipart/form-data">
        <!-- Personal Information -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('messages.personal_information') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.basic_borrower_details') }}</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- First Name -->
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.first_name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="first_name"
                               x-model="form.first_name"
                               required
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                               :class="{'border-red-300': errors.first_name}"
                               placeholder="{{ __('messages.enter_first_name') }}">
                        <p x-show="errors.first_name" x-text="errors.first_name" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.last_name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="last_name"
                               x-model="form.last_name"
                               required
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                               :class="{'border-red-300': errors.last_name}"
                               placeholder="{{ __('messages.enter_last_name') }}">
                        <p x-show="errors.last_name" x-text="errors.last_name" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <!-- Date of Birth -->
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.date_of_birth') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="date_of_birth"
                               x-model="form.date_of_birth"
                               required
                               placeholder="dd/mm/yyyy"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                               :class="{'border-red-300': errors.date_of_birth}">
                        <p x-show="errors.date_of_birth" x-text="errors.date_of_birth" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <!-- Gender -->
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.gender') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="gender"
                                x-model="form.gender"
                                required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                :class="{'border-red-300': errors.gender}">
                            <option value="">{{ __('messages.select_gender') }}</option>
                            <option value="male">{{ __('messages.male') }}</option>
                            <option value="female">{{ __('messages.female') }}</option>
                            <option value="other">{{ __('messages.other') }}</option>
                        </select>
                        <p x-show="errors.gender" x-text="errors.gender" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <!-- National ID -->
                    <div>
                        <label for="id_number" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.id_number') }}
                        </label>
                        <input type="text"
                               id="id_number"
                               x-model="form.id_number"
                               maxlength="50"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                               :class="{'border-red-300': errors.id_number}"
                               placeholder="{{ __('messages.enter_nida_voters') }}">
                        <p class="mt-1 text-xs text-gray-500">{{ __('messages.optional_nida') }}</p>
                        <p x-show="errors.id_number" x-text="errors.id_number" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <!-- Marital Status -->
                    <div>
                        <label for="marital_status" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.marital_status') }}
                        </label>
                        <select id="marital_status"
                                x-model="form.marital_status"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="">{{ __('messages.select_status') }}</option>
                            <option value="single">{{ __('messages.single') }}</option>
                            <option value="married">{{ __('messages.married') }}</option>
                            <option value="divorced">{{ __('messages.divorced') }}</option>
                            <option value="widowed">{{ __('messages.widowed') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('messages.contact_information') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.phone_email_address') }}</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.phone_number') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">255</span>
                            </div>
                            <input type="tel"
                                   id="phone"
                                   x-model="form.phone"
                                   required
                                   class="block w-full pl-12 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                   :class="{'border-red-300': errors.phone}"
                                   placeholder="712345678">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ __('messages.phone_format_hint') }}</p>
                        <p x-show="errors.phone" x-text="errors.phone" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.email_address') }}
                        </label>
                        <input type="email"
                               id="email"
                               x-model="form.email"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                               :class="{'border-red-300': errors.email}"
                               placeholder="client@example.com">
                        <p x-show="errors.email" x-text="errors.email" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <!-- Region -->
                    <div>
                        <label for="region" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.region') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="region"
                                x-model="form.region"
                                @change="loadDistricts()"
                                required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                :class="{'border-red-300': errors.region}">
                            <option value="">{{ __('messages.select_region') }}</option>
                            <template x-for="region in regions" :key="region">
                                <option :value="region" x-text="region"></option>
                            </template>
                        </select>
                        <p x-show="errors.region" x-text="errors.region" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <!-- District -->
                    <div>
                        <label for="district" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.district') }} <span class="text-red-500">*</span>
                        </label>
                        <!-- Select when districts available -->
                        <template x-if="districts.length > 0">
                            <select id="district"
                                    x-model="form.district"
                                    required
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    :class="{'border-red-300': errors.district}">
                                <option value="">{{ __('messages.select_district') }}</option>
                                <template x-for="district in districts" :key="district">
                                    <option :value="district" x-text="district"></option>
                                </template>
                            </select>
                        </template>
                        <!-- Fallback text input when no districts mapped -->
                        <template x-if="districts.length === 0">
                            <input type="text"
                                   id="district"
                                   x-model="form.district"
                                   required
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                   :class="{'border-red-300': errors.district}"
                                   placeholder="{{ __('messages.enter_district') }}">
                        </template>
                        <p x-show="errors.district" x-text="errors.district" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <!-- Ward -->
                    <div>
                        <label for="ward" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.ward') }}
                        </label>
                        <input type="text"
                               id="ward"
                               x-model="form.ward"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                               placeholder="{{ __('messages.enter_ward') }}">
                    </div>

                    <!-- Street -->
                    <div>
                        <label for="street" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.street_village') }}
                        </label>
                        <input type="text"
                               id="street"
                               x-model="form.street"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                               placeholder="{{ __('messages.enter_street_village') }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- Employment Information -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('messages.employment_information') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.occupation_income_details') }}</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Occupation -->
                    <div>
                        <label for="occupation" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.occupation') }}
                        </label>
                        <input type="text"
                               id="occupation"
                               x-model="form.occupation"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                               placeholder="{{ __('messages.enter_occupation') }}">
                    </div>

                    <!-- Monthly Income -->
                    <div>
                        <label for="monthly_income" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.monthly_income_tzs') }}
                        </label>
                        <input type="number"
                               id="monthly_income"
                               x-model="form.monthly_income"
                               min="0"
                               step="1"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                               placeholder="0">
                    </div>

                    <!-- Employer -->
                    <div>
                        <label for="employer" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.employer_business') }}
                        </label>
                        <input type="text"
                               id="employer"
                               x-model="form.employer"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                               placeholder="{{ __('messages.enter_employer') }}">
                    </div>

                    <!-- Employment Type -->
                    <div>
                        <label for="employment_type" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.employment_type') }}
                        </label>
                        <select id="employment_type"
                                x-model="form.employment_type"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="">{{ __('messages.select_type') }}</option>
                            <option value="employed">{{ __('messages.employed') }}</option>
                            <option value="self_employed">{{ __('messages.self_employed') }}</option>
                            <option value="business_owner">{{ __('messages.business_owner') }}</option>
                            <option value="farmer">{{ __('messages.farmer') }}</option>
                            <option value="unemployed">{{ __('messages.unemployed') }}</option>
                            <option value="retired">{{ __('messages.retired') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Branch & Officer -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('messages.branch_officer') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.assign_branch_officer') }}</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Branch Selection -->
                    <div>
                        <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.branch') }}
                        </label>
                        <select id="branch_id"
                                name="branch_id"
                                x-model="form.branch_id"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="">{{ __('messages.select_branch_option') }}</option>
                            @foreach($branches ?? [] as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Loan Officer Selection -->
                    <div>
                        <label for="loan_officer_id" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.loan_officer') }}
                        </label>
                        <select id="loan_officer_id"
                                name="loan_officer_id"
                                x-model="form.loan_officer_id"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="">{{ __('messages.select_loan_officer') }}</option>
                            @foreach($loanOfficers ?? [] as $officer)
                                <option value="{{ $officer->id }}">{{ $officer->name }} ({{ $officer->role }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Group Assignment -->
                    <div class="md:col-span-2">
                        <label for="group_id" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.group') ?? 'Group (Kikundi)' }}
                        </label>
                        <select id="group_id"
                                name="group_id"
                                x-model="form.group_id"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="">— {{ __('messages.no_group') ?? 'No group (individual loan)' }} —</option>
                            @foreach($groups ?? [] as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ __('messages.group_assign_hint') ?? 'Select a group to auto-assign this borrower as a group member.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Initial Loan Product -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('messages.initial_loan_product') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.initial_loan_product_desc') }}</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Product Selection -->
                    <div class="md:col-span-2">
                        <label for="initial_product_id" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('messages.loan_product_optional') }}
                        </label>
                        <select id="initial_product_id"
                                x-model="form.initial_product_id"
                                @change="onInitialProductChange()"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="">{{ __('messages.select_loan_product_option') }}</option>
                            <template x-for="product in loanProducts" :key="product.id">
                                <option :value="product.id" x-text="product.name + ' - ' + (product.description || '')"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Product Details -->
                    <div x-show="selectedProduct" class="md:col-span-2 bg-blue-50 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-blue-900 mb-3">{{ __('messages.selected_product_details') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-blue-700 font-medium">{{ __('messages.interest_rate_label') }}</span>
                                <span class="text-blue-900" x-text="selectedProduct?.interest_rate + '%' "></span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">{{ __('messages.amount_range') }}</span>
                                <span class="text-blue-900" x-text="formatCurrency(selectedProduct?.min_amount) + ' - ' + formatCurrency(selectedProduct?.max_amount)"></span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">{{ __('messages.term_range') }}</span>
                                <span class="text-blue-900" x-text="selectedProduct?.min_term + ' - ' + selectedProduct?.max_term + ' {{ __('messages.months') }}'"></span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">{{ __('messages.processing_fee_label') }}</span>
                                <span class="text-blue-900" x-text="selectedProduct?.processing_fee_rate + '%' "></span>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-500">{{ __('messages.redirect_loan_note') }}</p>
            </div>
        </div>

        <!-- Photo & Status -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('messages.photo_status') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.upload_photo_status') }}</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Photo Upload -->
                    <div>
                        <label for="photo" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.photo') }}
                        </label>
                        <input type="file"
                               id="photo"
                               x-ref="photoInput"
                               accept="image/*"
                               class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer bg-white focus:outline-none">
                        <p class="mt-1 text-xs text-gray-500">{{ __('messages.photo_formats') }}</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.loan_status') }}
                        </label>
                        <select id="status" x-model="form.status" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="active">{{ __('messages.status_active') }}</option>
                            <option value="inactive">{{ __('messages.inactive') }}</option>
                            <option value="blacklisted">{{ __('messages.blacklisted') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('messages.emergency_contact') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.next_of_kin') }}</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Emergency Contact Name -->
                    <div>
                        <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.contact_name') }}
                        </label>
                        <input type="text"
                               id="emergency_contact_name"
                               x-model="form.emergency_contact_name"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                               placeholder="{{ __('messages.enter_contact_name') }}">
                    </div>

                    <!-- Emergency Contact Phone -->
                    <div>
                        <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.contact_phone') }}
                        </label>
                        <input type="tel"
                               id="emergency_contact_phone"
                               x-model="form.emergency_contact_phone"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                               placeholder="255 XXX XXX XXX">
                    </div>

                    <!-- Relationship -->
                    <div>
                        <label for="emergency_contact_relationship" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('messages.relationship') }}
                        </label>
                        <select id="emergency_contact_relationship"
                                x-model="form.emergency_contact_relationship"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="">{{ __('messages.select_relationship') }}</option>
                            <option value="spouse">{{ __('messages.spouse') }}</option>
                            <option value="parent">{{ __('messages.parent') }}</option>
                            <option value="child">{{ __('messages.child') }}</option>
                            <option value="sibling">{{ __('messages.sibling') }}</option>
                            <option value="friend">{{ __('messages.friend') }}</option>
                            <option value="director">{{ __('messages.director') }}</option>
                            <option value="other">{{ __('messages.other') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end space-x-4 pt-6">
            <a href="{{ route('clients.index') }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                {{ __('messages.cancel') }}
            </a>
            <button type="submit"
                    name="action"
                    value="save_and_new"
                    :disabled="submitting"
                    @click="console.log('Save and Create Another clicked')"
                    class="inline-flex items-center px-4 py-2 border border-primary-600 text-sm font-medium rounded-md shadow-sm text-primary-600 bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg x-show="submitting" class="animate-spin -ml-1 mr-3 h-4 w-4 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="submitting ? '{{ __('messages.saving') }}...' : '{{ __('messages.save_create_another') }}'"></span>
            </button>
            <button type="submit"
                    name="action"
                    value="save"
                    :disabled="submitting"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg x-show="submitting" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="submitting ? '{{ __('messages.creating') }}...' : '{{ __('messages.create_borrower') }}'"></span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function clientCreate() {
    return {
        form: {
            first_name: '',
            last_name: '',
            date_of_birth: '',
            gender: '',
            id_number: '',
            marital_status: '',
            phone: '',
            email: '',
            region: '',
            district: '',
            ward: '',
            street: '',
            occupation: '',
            monthly_income: '',
            employer: '',
            employment_type: '',
            emergency_contact_name: '',
            emergency_contact_phone: '',
            emergency_contact_relationship: '',
            branch_id: '',
            loan_officer_id: '',
            group_id: '',
            status: 'active',
            initial_product_id: ''
        },
        errors: {},
        submitting: false,
        loanProducts: [],
        selectedProduct: null,
        regions: [
            'Arusha', 'Dar es Salaam', 'Dodoma', 'Geita', 'Iringa', 'Kagera', 'Katavi',
            'Kigoma', 'Kilimanjaro', 'Lindi', 'Manyara', 'Mara', 'Mbeya', 'Morogoro',
            'Mtwara', 'Mwanza', 'Njombe', 'Pemba North', 'Pemba South', 'Pwani',
            'Rukwa', 'Ruvuma', 'Shinyanga', 'Simiyu', 'Singida', 'Songwe', 'Tabora',
            'Tanga', 'Unguja North', 'Unguja South'
        ],
        districts: [],

        init() {
            this.loanProducts = (window.initialLoanProducts || @json($products ?? []));
            this.loadLoanProducts();
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

        onInitialProductChange() {
            this.selectedProduct = this.loanProducts.find(p => p.id == this.form.initial_product_id) || null;
        },

        async loadDistricts() {
            const districtMap = {
                'Dar es Salaam': ['Ilala', 'Kinondoni', 'Temeke', 'Ubungo', 'Kigamboni'],
                'Arusha': ['Arusha City', 'Arusha Rural', 'Karatu', 'Longido', 'Monduli', 'Ngorongoro'],
                'Mwanza': ['Ilemela', 'Nyamagana', 'Buchosa', 'Magu', 'Misungwi', 'Sengerema'],
                'Dodoma': ['Dodoma Urban', 'Bahi', 'Chamwino', 'Chemba', 'Kondoa', 'Mpwapwa'],
            };
            this.districts = districtMap[this.form.region] || [];
            this.form.district = '';
        },

        formatCurrency(value) {
            if (value == null) return '';
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(value);
        },

        async submitForm(event) {
            console.log('Submit form called', event);
            
            // Check if form is valid
            const form = event.target;
            if (!form.checkValidity()) {
                console.log('Form validation failed');
                form.reportValidity();
                return;
            }
            
            this.submitting = true;
            this.errors = {};

            try {
                const fd = new FormData();
                const address = [this.form.street, this.form.ward, this.form.district, this.form.region]
                    .filter(Boolean)
                    .join(', ');

                // Clean phone number - remove + and spaces, and ensure 255 prefix
                let cleanPhone = this.form.phone ? this.form.phone.replace(/[\s+]/g, '') : '';
                // Prepend 255 if not already present
                if (cleanPhone && !cleanPhone.startsWith('255')) {
                    cleanPhone = '255' + cleanPhone;
                }

                // Convert date_of_birth from DD/MM/YYYY to YYYY-MM-DD for server validation
                let dob = this.form.date_of_birth || '';
                if (dob && /^\d{2}\/\d{2}\/\d{4}$/.test(dob)) {
                    const [d, m, y] = dob.split('/');
                    dob = `${y}-${m}-${d}`;
                }

                const payload = { ...this.form, address, phone: cleanPhone, date_of_birth: dob };
                Object.entries(payload).forEach(([key, value]) => {
                    fd.append(key, value ?? '');
                });

                // Capture which button was clicked
                const clickedButton = event.submitter;
                const actionValue = clickedButton?.value || 'save';
                console.log('Action value:', actionValue);
                if (clickedButton && clickedButton.name === 'action') {
                    fd.append('action', clickedButton.value);
                }

                const photoFile = this.$refs.photoInput?.files?.[0];
                if (photoFile) {
                    fd.append('photo', photoFile);
                }

                console.log('Sending request to /clients...');
                const response = await fetch('/clients', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: fd
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (response.ok) {
                    const contentType = response.headers.get('content-type') || '';
                    console.log('Content-Type:', contentType);
                    if (contentType.includes('application/json')) {
                        const data = await response.json();
                        console.log('Response data:', data);
                        const newClientId = data.client_id || data.data?.id;
                        
                        // Check if initial product is selected
                        if (newClientId && this.form.initial_product_id) {
                            window.location.href = `/loans/create?client_id=${encodeURIComponent(newClientId)}&product_id=${encodeURIComponent(this.form.initial_product_id)}`;
                            return;
                        }
                        
                        // Handle save_and_new action
                        if (actionValue === 'save_and_new') {
                            // Reset form and show success message
                            this.form = {
                                first_name: '',
                                last_name: '',
                                date_of_birth: '',
                                gender: '',
                                id_number: '',
                                marital_status: '',
                                phone: '',
                                email: '',
                                region: '',
                                district: '',
                                ward: '',
                                street: '',
                                occupation: '',
                                monthly_income: '',
                                employer: '',
                                employment_type: '',
                                emergency_contact_name: '',
                                emergency_contact_phone: '',
                                emergency_contact_relationship: '',
                                branch_id: '',
                                loan_officer_id: '',
                                status: 'active',
                                initial_product_id: ''
                            };
                            this.selectedProduct = null;
                            this.districts = [];
                            if (this.$refs.photoInput) {
                                this.$refs.photoInput.value = '';
                            }
                            alert('Borrower created successfully. You can now create another borrower.');
                            window.scrollTo(0, 0);
                            return;
                        }
                        
                        // Default: redirect to client details or list
                        window.location.href = data.redirect || '/clients';
                    } else {
                        window.location.href = '/clients';
                    }
                } else {
                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) {
                        const data = await response.json();
                        if (data.errors) {
                            this.errors = data.errors;
                        } else {
                            alert(data.message || 'An error occurred while creating the borrower');
                        }
                    } else {
                        alert('An error occurred while creating the borrower');
                    }
                }
            } catch (error) {
                console.error('Error creating borrower:', error);
                alert('An error occurred while creating the borrower');
            } finally {
                this.submitting = false;
            }
        }
    }
}
</script>
@endpush
@endsection