@extends('layouts.app')

@section('title', 'Income Categories')
@section('page-title', 'Income Categories')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Income Categories</h1>
            <p class="mt-1 text-sm text-gray-500">View and manage income category structure</p>
        </div>
        <div>
            <a href="{{ route('reports.accounts.index') }}" class="px-4 py-2 border rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">Back to Accounts</a>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <p class="text-gray-600 mb-4">This is a placeholder for Income Categories. We can later wire this to Chart of Accounts and enable CRUD for categories.</p>
        <ul class="list-disc list-inside text-gray-700 space-y-1">
            <li>Interest Income</li>
            <li>Fee Income</li>
            <li>Other Operating Income</li>
        </ul>
    </div>
</div>
@endsection