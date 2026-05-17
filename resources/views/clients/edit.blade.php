@extends('layouts.app')

@section('title', __('messages.edit_borrower'))
@section('page-title', __('messages.edit_borrower'))

@section('content')
<div class="max-w-4xl mx-auto">
    <form action="{{ route('clients.update', $client) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Personal Information -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('messages.personal_information') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.update_borrower_details') }}</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- First Name -->
                    <div>
                        <label for="first_name" class="block text sm font-medium text-gray-700 mb-1">{{ __('messages.first_name') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $client->first_name) }}" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label for="last_name" class="block text sm font-medium text-gray-700 mb-1">{{ __('messages.last_name') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $client->last_name) }}" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- Date of Birth -->
                    <div>
                        <label for="date_of_birth" class="block text sm font-medium text-gray-700 mb-1">{{ __('messages.date_of_birth') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', optional($client->date_of_birth)->format('d/m/Y')) }}" required placeholder="dd/mm/yyyy" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        @error('date_of_birth')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- Gender -->
                    <div>
                        <label for="gender" class="block text sm font-medium text-gray-700 mb-1">{{ __('messages.gender') }} <span class="text-red-500">*</span></label>
                        <select id="gender" name="gender" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="">{{ __('messages.select_gender') }}</option>
                            <option value="male" {{ old('gender', $client->gender) === 'male' ? 'selected' : '' }}>{{ __('messages.male') }}</option>
                            <option value="female" {{ old('gender', $client->gender) === 'female' ? 'selected' : '' }}>{{ __('messages.female') }}</option>
                            <option value="other" {{ old('gender', $client->gender) === 'other' ? 'selected' : '' }}>{{ __('messages.other') }}</option>
                        </select>
                        @error('gender')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- National ID -->
                    <div>
                        <label for="id_number" class="block text sm font-medium text-gray-700 mb-1">{{ __('messages.id_number') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="id_number" name="id_number" value="{{ old('id_number', $client->id_number) }}" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        @error('id_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text sm font-medium text-gray-700 mb-1">{{ __('messages.loan_status') }} <span class="text-red-500">*</span></label>
                        <select id="status" name="status" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="active" {{ old('status', $client->status) === 'active' ? 'selected' : '' }}>{{ __('messages.active') }}</option>
                            <option value="inactive" {{ old('status', $client->status) === 'inactive' ? 'selected' : '' }}>{{ __('messages.inactive') }}</option>
                            <option value="blacklisted" {{ old('status', $client->status) === 'blacklisted' ? 'selected' : '' }}>{{ __('messages.blacklisted') }}</option>
                        </select>
                        @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
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
                        <label for="phone" class="block text sm font-medium text-gray-700 mb-1">{{ __('messages.phone_number') }} <span class="text-red-500">*</span></label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone', $client->phone) }}" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text sm font-medium text-gray-700 mb-1">{{ __('messages.email_address') }}</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $client->email) }}" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- Address -->
                    <div class="md:col-span-2">
                        <label for="address" class="block text sm font-medium text-gray-700 mb-1">{{ __('messages.address') }}</label>
                        <textarea id="address" name="address" rows="3" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">{{ old('address', $client->address) }}</textarea>
                        @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- Region -->
                    <div>
                        <label for="region" class="block text sm font-medium text-gray-700 mb-1">{{ __('messages.region') }}</label>
                        <input type="text" id="region" name="region" value="{{ old('region', $client->region) }}" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        @error('region')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- District -->
                    <div>
                        <label for="district" class="block text sm font-medium text-gray-700 mb-1">{{ __('messages.district') }}</label>
                        <input type="text" id="district" name="district" value="{{ old('district', $client->district) }}" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        @error('district')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- Ward -->
                    <div>
                        <label for="ward" class="block text sm font-medium text-gray-700 mb-1">{{ __('messages.ward') }}</label>
                        <input type="text" id="ward" name="ward" value="{{ old('ward', $client->ward) }}" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        @error('ward')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- Street -->
                    <div>
                        <label for="street" class="block text sm font-medium text-gray-700 mb-1">{{ __('messages.street_village') }}</label>
                        <input type="text" id="street" name="street" value="{{ old('street', $client->street) }}" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        @error('street')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6 mt-6">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    * {{ __('messages.required_fields') }}
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('clients.show', $client) }}" class="inline-flex items-center px-6 py-2.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        {{ __('messages.cancel') }}
                    </a>
                    <button type="submit" class="inline-flex items-center px-6 py-2.5 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ __('messages.update_borrower') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection