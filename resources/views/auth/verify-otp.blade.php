@extends('layouts.app')
@section('title', 'Enter OTP')
@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full space-y-8">

        <div class="text-center">
            <div class="mx-auto h-20 w-20 flex items-center justify-center">
                <img src="{{ asset('logo.jpeg') }}" alt="TFS" class="h-16 w-auto">
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Enter OTP</h2>
            <p class="mt-2 text-sm text-gray-600">
                We sent a 6-digit code to <strong>{{ $phone }}</strong>. Enter it below.
            </p>
        </div>

        @if(session('status'))
            <div class="rounded-md bg-green-50 p-4 border border-green-200">
                <p class="text-sm text-green-700">{{ session('status') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                @foreach($errors->all() as $error)
                    <p class="text-sm text-red-700">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="mt-8 space-y-6" action="{{ route('password.sms.verify.submit') }}" method="POST">
            @csrf
            <input type="hidden" name="phone" value="{{ $phone }}">

            <div>
                <label for="otp" class="block text-sm font-medium text-gray-700">6-Digit OTP</label>
                <div class="mt-1">
                    <input id="otp" name="otp" type="text" inputmode="numeric" pattern="\d{6}"
                           maxlength="6" required
                           placeholder="123456"
                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm text-center text-2xl tracking-widest focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('otp') border-red-300 @enderror">
                </div>
                <p class="mt-1 text-xs text-gray-500">Valid for 10 minutes.</p>
            </div>

            <button type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Verify OTP
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('password.sms.request') }}" class="text-sm text-blue-600 hover:text-blue-500">
                Resend OTP / Use different number
            </a>
        </div>
    </div>
</div>
@endsection
