@extends('layouts.app')

@section('title', 'SMS Balance Debug')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">SMS Balance Debug Information</h1>
    
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Current Session</h2>
        <dl class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Tenant ID</dt>
                <dd class="text-lg font-semibold">{{ session('tenant_id') ?? 'Not Set' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">User ID</dt>
                <dd class="text-lg font-semibold">{{ auth()->id() ?? 'Not Logged In' }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">SMS Balance Object</h2>
        @if(isset($balance))
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Balance</dt>
                    <dd class="text-2xl font-bold text-green-600">{{ number_format($balance->balance ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Total Purchased</dt>
                    <dd class="text-lg font-semibold">{{ number_format($balance->total_purchased ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Total Used</dt>
                    <dd class="text-lg font-semibold">{{ number_format($balance->total_used ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Total Failed</dt>
                    <dd class="text-lg font-semibold">{{ number_format($balance->total_failed ?? 0) }}</dd>
                </div>
            </dl>
            
            <div class="mt-4 p-4 bg-gray-50 rounded">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Raw Data:</h3>
                <pre class="text-xs">{{ json_encode($balance->toArray(), JSON_PRETTY_PRINT) }}</pre>
            </div>
        @else
            <p class="text-red-600">Balance object not found!</p>
        @endif
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold mb-4">All SMS Balances in Database</h2>
        @if(isset($allBalances) && $allBalances->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tenant ID</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tenant Name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Purchased</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Used</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($allBalances as $bal)
                    <tr class="{{ $bal->tenant_id == session('tenant_id') ? 'bg-blue-50' : '' }}">
                        <td class="px-4 py-2">{{ $bal->tenant_id }}</td>
                        <td class="px-4 py-2">{{ $bal->tenant->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2 font-bold">{{ number_format($bal->balance) }}</td>
                        <td class="px-4 py-2">{{ number_format($bal->total_purchased) }}</td>
                        <td class="px-4 py-2">{{ number_format($bal->total_used) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-gray-500">No SMS balances found in database.</p>
        @endif
    </div>

    <div class="mt-6">
        <a href="{{ route('messages.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            Back to Messages
        </a>
    </div>
</div>
@endsection
