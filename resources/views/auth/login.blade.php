@extends('layouts.public')

@section('title', __('messages.sign_in'))

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-4 py-8"
     style="background: linear-gradient(160deg, #1a5c43 0%, #2e8b5f 40%, #52c493 80%, #a8edcc 100%);">

    <!-- Login Card -->
    <div class="w-full max-w-sm" x-data="{ showPassword: false, showTerms: false }">
        <div class="rounded-2xl shadow-2xl overflow-hidden">

            <!-- Card Header — teal gradient with logo -->
            <div class="py-10 px-8 text-center" style="background: linear-gradient(180deg, #1a5c43 0%, #237a56 100%);">
                <div class="flex items-center justify-center mx-auto mb-4 w-16 h-16 rounded-full"
                     style="background: rgba(255,255,255,0.18); backdrop-filter: blur(4px);">
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-wide">Login</h1>
                <p class="text-sm mt-1" style="color: rgba(255,255,255,0.7);">Together Financial Services</p>
            </div>

            <!-- Card Body — white form area -->
            <div class="bg-white px-8 pt-7 pb-8">

                {{-- Errors --}}
                @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>• {{ $error }}</p>
                    @endforeach
                </div>
                @endif

                @if (session('status'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
                @endif

                <form action="{{ route('login') }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- Email -->
                    <div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </span>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                   value="{{ old('email') }}"
                                   placeholder="Email"
                                   class="w-full pl-9 pr-4 py-3 border rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 transition @error('email') border-red-400 focus:ring-red-200 @else border-gray-300 focus:ring-green-200 focus:border-green-500 @enderror">
                        </div>
                    </div>

                    <!-- Phone number alternative hint -->
                    <div class="text-right -mt-2">
                        <span class="text-xs" style="color:#237a56;">Login with Phone Number</span>
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </span>
                            <input id="password" name="password"
                                   :type="showPassword ? 'text' : 'password'"
                                   autocomplete="current-password" required
                                   placeholder="Password"
                                   class="w-full pl-9 pr-10 py-3 border rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 transition @error('password') border-red-400 focus:ring-red-200 @else border-gray-300 focus:ring-green-200 focus:border-green-500 @enderror">
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Forgot password -->
                    <div class="text-right -mt-2">
                        <a href="{{ route('password.request') }}" class="text-xs text-gray-500 hover:text-gray-700">Forgot password</a>
                    </div>

                    <!-- Login button -->
                    <button type="submit"
                            class="w-full py-3 text-white font-semibold rounded-lg text-sm tracking-wide transition-opacity hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            style="background: linear-gradient(90deg, #1a5c43 0%, #2e8b5f 100%);">
                        Login
                    </button>

                    <!-- Remember me -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer">
                            <input name="remember" type="checkbox" class="rounded border-gray-300 text-green-600">
                            {{ __('messages.remember_me') }}
                        </label>
                        <a href="{{ route('tenant.register') }}" class="text-xs hover:underline" style="color:#237a56;">
                            Register Organisation
                        </a>
                    </div>
                </form>

                <!-- Terms -->
                <div class="mt-5 pt-4 border-t border-gray-100 text-center">
                    <button @click="showTerms = true" class="text-xs text-gray-400 hover:text-gray-600 underline">
                        {{ __('messages.terms_and_conditions') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Terms Modal -->
        <div x-show="showTerms"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center px-4"
             style="background: rgba(0,0,0,0.5); display:none;">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[80vh] overflow-y-auto p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-gray-900">{{ __('messages.terms_title') }}</h3>
                    <button @click="showTerms = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="text-sm text-gray-600 space-y-2 text-left">
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
                    <p class="mt-3 pt-3 border-t text-center text-gray-500">
                        Contact: <a href="mailto:info@phidtech.com" class="text-green-700 font-medium">info@phidtech.com</a>
                    </p>
                </div>
                <div class="mt-4 text-center">
                    <button @click="showTerms = false"
                            class="px-6 py-2 text-white rounded-lg text-sm font-medium"
                            style="background:#237a56;">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection