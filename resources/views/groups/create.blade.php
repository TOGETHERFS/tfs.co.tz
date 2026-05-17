@extends('layouts.app')

@section('title', __('messages.add_group'))
@section('page-title', __('messages.add_group'))

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">{{ __('messages.create_new_group') }}</h3>
        </div>

        <form method="POST" action="{{ route('groups.store') }}" class="p-6 space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">{{ __('messages.group_name') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="branch_name" class="block text-sm font-medium text-gray-700">{{ __('messages.main_branch_name') }}</label>
                    <select id="branch_name" name="branch_name" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">-- {{ __('messages.select_branch') ?? 'Select Branch' }} --</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->name }}" {{ old('branch_name') === $branch->name ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="loan_officer" class="block text-sm font-medium text-gray-700">{{ __('messages.loan_officer') }}</label>
                    <select id="loan_officer" name="loan_officer" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">-- {{ __('messages.select_loan_officer') ?? 'Select Loan Officer' }} --</option>
                        @foreach($loanOfficers as $officer)
                            <option value="{{ $officer->name }}" {{ old('loan_officer') === $officer->name ? 'selected' : '' }}>{{ $officer->name }}</option>
                        @endforeach
                    </select>
                    @error('loan_officer')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="meeting_area" class="block text-sm font-medium text-gray-700">{{ __('messages.meeting_area') }}</label>
                    <input type="text" id="meeting_area" name="meeting_area" value="{{ old('meeting_area') }}"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                    @error('meeting_area')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="bank_account" class="block text-sm font-medium text-gray-700">{{ __('messages.bank_account_optional') }}</label>
                    <input type="text" id="bank_account" name="bank_account" value="{{ old('bank_account') }}"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    @error('bank_account')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Location & Contact Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="region" class="block text-sm font-medium text-gray-700">{{ __('messages.region') }}</label>
                    <input type="text" id="region" name="region" value="{{ old('region') }}"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                    @error('region')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="ward" class="block text-sm font-medium text-gray-700">{{ __('messages.ward') }}</label>
                    <input type="text" id="ward" name="ward" value="{{ old('ward') }}"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                    @error('ward')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="village" class="block text-sm font-medium text-gray-700">{{ __('messages.village') }}</label>
                    <input type="text" id="village" name="village" value="{{ old('village') }}"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                    @error('village')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">{{ __('messages.phone') }}</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="box_number" class="block text-sm font-medium text-gray-700">{{ __('messages.box_number_optional') }}</label>
                <input type="text" id="box_number" name="box_number" value="{{ old('box_number') }}"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                @error('box_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">{{ __('messages.description') }}</label>
                <textarea id="description" name="description" rows="3"
                          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">{{ __('messages.loan_status') }}</label>
                <select id="status" name="status"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>{{ __('messages.active') }}</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>{{ __('messages.inactive') }}</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end space-x-2">
                <a href="{{ route('groups.index') }}" class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('messages.cancel') }}</a>
                <button type="submit"
                        class="inline-flex items-center px-6 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('messages.save_group') }}
                </button>
            </div>
        </form>
    </div>
    
    @if($errors->any())
        <div class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection