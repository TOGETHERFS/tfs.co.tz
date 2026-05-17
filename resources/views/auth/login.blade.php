@extends('layouts.public')

@section('title', __('messages.sign_in'))

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="text-center text-3xl font-extrabold text-gray-900">
                {{ __('messages.sign_in_to_account') }}
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                {{ __('messages.welcome_back') }}
            </p>
        </div>

        {{-- Display all errors prominently --}}
        @if ($errors->any())
            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">
                            {{ __('messages.submission_errors') }}
                        </h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (session('status'))
            <div class="rounded-md bg-green-50 p-4 border border-green-200">
                <p class="text-sm text-green-800">{{ session('status') }}</p>
            </div>
        @endif

        <form class="mt-8 space-y-6" action="{{ route('login') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        {{ __('messages.email_address') }}
                    </label>
                    <div class="mt-1">
                        <input id="email" 
                               name="email" 
                               type="email" 
                               autocomplete="email" 
                               required 
                               value="{{ old('email') }}"
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm @error('email') border-red-300 @enderror" 
                               placeholder="{{ __('messages.enter_email') }}">
                    </div>
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        {{ __('messages.password') }}
                    </label>
                    <div class="mt-1 relative">
                        <input id="password" 
                               name="password" 
                               type="password" 
                               autocomplete="current-password" 
                               required 
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm @error('password') border-red-300 @enderror" 
                               placeholder="{{ __('messages.enter_password') }}">
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Submit button directly below the password field -->
            <div class="mt-4 flex justify-center">
                <button type="submit"
                    class="inline-block px-4 py-2 text-sm font-medium rounded-md focus:outline-none transition duration-150 ease-in-out"
                    style="background-color: #007bff; color: #ffffff;">
                    {{ __('messages.sign_in') }}
                </button>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember-me" 
                           name="remember" 
                           type="checkbox" 
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                        {{ __('messages.remember_me') }}
                    </label>
                </div>

                <div class="text-sm">
                    <a href="{{ route('password.request') }}" 
                       class="font-medium text-primary-600 hover:text-primary-500">
                        {{ __('messages.forgot_password') }}
                    </a>
                </div>
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    {{ __('messages.no_account') }}
                    <a href="{{ route('tenant.register') }}" 
                       class="font-medium text-primary-600 hover:text-primary-500">
                        {{ __('messages.register_organization') }}
                    </a>
                </p>
            </div>
        </form>

    </div>

    <!-- Terms and Conditions Link -->
    <div class="mt-4 text-center" x-data="{ showTerms: false }">
        <button @click="showTerms = true" class="text-sm text-blue-600 hover:text-blue-700 font-medium underline">
            {{ __('messages.terms_and_conditions') }}
        </button>

        <!-- Terms and Conditions Modal -->
        <div x-show="showTerms" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto p-6 z-10">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">{{ __('messages.terms_title') }}</h3>
                        <button @click="showTerms = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="text-sm text-gray-700 space-y-3 text-left">
                        <p><strong>1. Acceptance of Terms:</strong> {{ __('messages.terms_acceptance') }}.</p>
                        <p><strong>2. Account Responsibility:</strong> {{ __('messages.account_responsibility') }}.</p>
                        <p><strong>3. Permitted Use:</strong> {{ __('messages.permitted_use') }}.</p>
                        <p><strong>4. Data Privacy:</strong> {{ __('messages.data_privacy') }}.</p>
                        <p><strong>5. Service Availability:</strong> {{ __('messages.service_availability') }}.</p>
                        <p><strong>6. Subscription & Payments:</strong> {{ __('messages.subscription_payments') }}.</p>
                        <p><strong>7. Intellectual Property:</strong> {{ __('messages.intellectual_property') }}.</p>
                        <p><strong>8. Limitation of Liability:</strong> {{ __('messages.limitation_liability') }}.</p>
                        <p><strong>9. Termination:</strong> {{ __('messages.termination') }}.</p>
                        <p><strong>10. Modifications:</strong> {{ __('messages.modifications') }}.</p>
                        <p class="mt-4 pt-3 border-t border-gray-200 text-center text-gray-600">
                            {{ __('messages.contact_us') }}
                            <a href="mailto:info@phidtech.com" class="text-blue-600 hover:text-blue-700 font-medium">info@phidtech.com</a>
                        </p>
                    </div>
                    <div class="mt-4 text-center">
                        <button @click="showTerms = false" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                            {{ __('messages.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection