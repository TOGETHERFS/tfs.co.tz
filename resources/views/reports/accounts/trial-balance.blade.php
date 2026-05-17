@extends('layouts.app')

@section('title', 'Trial Balance')
@section('page-title', 'Trial Balance')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Trial Balance</h1>
            <p class="mt-1 text-sm text-gray-500">Debits and credits summary by account</p>
        </div>
        <div>
            <a href="{{ route('reports.accounts.index') }}" class="px-4 py-2 border rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">Back to Accounts</a>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <p class="text-gray-600">This is a placeholder for the Trial Balance report. Filters and export options can be added here.</p>
    </div>
</div>
@endsection