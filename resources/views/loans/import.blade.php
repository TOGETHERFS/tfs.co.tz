@extends('layouts.app')

@section('title', __('messages.import_loans'))
@section('page-title', __('messages.import_loans'))

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.import_loans') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.import_loans_desc') }}</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            <p class="text-sm">{{ session('success') }}</p>
        </div>
    @endif

    @if (session('import_errors') && is_array(session('import_errors')) && count(session('import_errors')))
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded">
            <p class="text-sm font-medium">{{ __('messages.import_warnings') }}</p>
            <ul class="list-disc pl-5 text-sm mt-2">
                @foreach (session('import_errors') as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="p-6">
            <form action="{{ route('loans.import.process') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.upload_file') }}</label>
                    <input type="file" name="file" id="file" required accept=".xlsx,.xls,.csv"
                           class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500">
                    <p class="mt-2 text-xs text-gray-500">{{ __('messages.accepted_formats') }}</p>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-800">{{ __('messages.download_template') }}</p>
                            <p class="text-xs text-blue-600 mt-1">{{ __('messages.download_template_desc') }}</p>
                        </div>
                        <a href="{{ route('loans.import.template') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            {{ __('messages.download_template') }}
                        </a>
                    </div>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                    <p class="text-sm font-medium text-gray-700">{{ __('messages.expected_columns') }}</p>
                    <ul class="mt-2 text-sm text-gray-600 list-disc pl-5">
                        <li><strong>client_id_number</strong> or <strong>client_phone</strong> ({{ __('messages.one_is_required') }})</li>
                        <li><strong>product_name</strong> or <strong>product_id</strong> ({{ __('messages.loan_product') }})</li>
                        <li><strong>principal</strong> - {{ __('messages.loan_amount') }}</li>
                        <li><strong>term</strong> - {{ __('messages.number_of_months') }}</li>
                        <li><strong>first_payment_date</strong> - {{ __('messages.excel_serial_or_date') }}</li>
                        <li><strong>loan_number</strong> ({{ __('messages.optional') }}; {{ __('messages.if_provided_updates_existing') }})</li>
                        <li><strong>status</strong> ({{ __('messages.optional') }}; {{ __('messages.status_options') }})</li>
                        <li><strong>notes</strong> ({{ __('messages.optional') }})</li>
                    </ul>
                </div>

                <div class="flex items-center space-x-4 mt-6 pt-4 border-t border-gray-200">
                    <button type="submit"
                            class="inline-flex items-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        {{ __('messages.import_loans') }}
                    </button>
                    <a href="{{ route('loans.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('messages.back_to_loans') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection