@extends('layouts.app')
@section('title', 'Set New Password')
@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full space-y-8">

        <div class="text-center">
            <div class="mx-auto h-20 w-20 flex items-center justify-center">
                <img src="{{ asset('logo.jpeg') }}" alt="TFS" class="h-16 w-auto">
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Set New Password</h2>
            <p class="mt-2 text-sm text-gray-600">Choose a strong password for your account.</p>
        </div>

        @if($errors->any())
            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                @foreach($errors->all() as $error)
                    <p class="text-sm text-red-700">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="mt-8 space-y-6" action="{{ route('password.sms.reset') }}" method="POST">
            @csrf
            <input type="hidden" name="phone" value="{{ $phone }}">
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                <div class="mt-1">
                    <input id="password" name="password" type="password" required minlength="8"
                           placeholder="At least 8 characters"
                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('password') border-red-300 @enderror">
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <div class="mt-1">
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                           placeholder="Repeat new password"
                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>

            <button type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Reset Password
            </button>
        </form>
    </div>
</div>
@endsection
