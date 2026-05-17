@extends('layouts.admin')

@section('title', 'Client Ledger')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Client Ledger</h1>
            <p class="mt-1 text-sm text-gray-500">Individual client account records</p>
        </div>
        <a href="{{ route('admin.reports.accounts.index') }}" class="text-gray-600 hover:text-gray-900">Back to Accounts</a>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <p class="text-gray-500 text-center py-8">Client Ledger report coming soon.</p>
    </div>
</div>
@endsection
