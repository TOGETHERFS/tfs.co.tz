@extends('layouts.app')

@section('title', 'SMS Balance')
@section('page-title', 'SMS Balance')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">SMS Balance</h1>
            <p class="mt-1 text-sm text-gray-500">Your Beem Africa SMS account balance</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('messages.purchase') }}" class="px-4 py-2 rounded-md text-sm text-white bg-primary-600 hover:bg-primary-700">Purchase SMS</a>
            <a href="{{ route('messages.index') }}" class="px-4 py-2 border rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">Back to Messages</a>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Current Balance</h3>
        </div>
        <div class="p-6">
            @if(!empty($error))
                <div class="mb-4 p-3 rounded bg-red-50 text-red-700 text-sm">{{ $error }}</div>
            @else
                @php
                    $data = $balance ?? [];
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 border rounded-md bg-gray-50">
                        <p class="text-sm text-gray-500">Balance</p>
                        <p class="mt-1 text-xl font-semibold text-gray-900">{{ data_get($data, 'balance', 'N/A') }}</p>
                    </div>
                    <div class="p-4 border rounded-md bg-gray-50">
                        <p class="text-sm text-gray-500">Currency</p>
                        <p class="mt-1 text-xl font-semibold text-gray-900">{{ data_get($data, 'currency', 'TZS') }}</p>
                    </div>
                </div>

                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-700">Raw Response</h4>
                    <pre class="mt-2 p-3 bg-gray-100 rounded border text-xs overflow-x-auto">{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection