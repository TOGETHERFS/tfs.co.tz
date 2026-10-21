@extends('layouts.app')
@section('title', 'Reset Password via SMS')
@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full space-y-8">

        <div class="text-center">
            <div class="mx-auto h-20 w-20 flex items-center justify-center">
                <img src="{{ asset('logo.jpeg') }}" alt="TFS" class="h-16 w-auto">
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Reset via SMS</h2>
            <p class="mt-2 text-sm text-gray-600">Enter your registered phone number. We'll send a 6-digit OTP.</p>
        </div>

        @if(session('error'))
            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                @foreach($errors->all() as $error)
                    <p class="text-sm text-red-700">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="mt-8 space-y-6" action="{{ route('password.sms.send') }}" method="POST">
            @csrf
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <div class="mt-1">
                    <input id="phone" name="phone" type="tel" required
                           value="{{ old('phone') }}"
                           placeholder="e.g. 0712345678"
                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('phone') border-red-300 @enderror">
                </div>
                <p class="mt-1 text-xs text-gray-500">Use the phone number registered on your account.</p>
            </div>

            <button type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Send OTP
            </button>
        </form>

        <div class="mt-4 text-center space-y-2">
            <a href="{{ route('password.request') }}" class="block text-sm text-blue-600 hover:text-blue-500">Reset via Email instead</a>
            <a href="{{ route('login') }}" class="block text-sm text-gray-500 hover:text-gray-400">← Back to login</a>
        </div>
    </div>
</div>
@endsection
