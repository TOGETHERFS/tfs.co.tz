@extends('layouts.app')

@section('title', 'Assets')
@section('page-title', 'Assets')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Assets</h1>
            <p class="mt-1 text-sm text-gray-500">View assets and add new ones</p>
        </div>
        <div>
            <a href="{{ route('reports.accounts.index') }}" class="px-4 py-2 border rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">Back to Accounts</a>
        </div>
    </div>

    @if(session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-md p-3">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-3">Current Assets (Sample)</h2>
            <ul class="list-disc list-inside text-gray-700 space-y-1">
                <li>Office Furniture - 5,000</li>
                <li>Computers - 8,500</li>
                <li>Branch Vehicle - 12,000</li>
            </ul>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Add Asset</h2>
            <form method="POST" action="{{ route('reports.accounts.assets.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Asset Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="mt-1 w-full border rounded-md p-2" required>
                    @error('name')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <input type="text" name="category" value="{{ old('category') }}" class="mt-1 w-full border rounded-md p-2" required>
                    @error('category')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Amount</label>
                    <input type="number" name="amount" step="0.01" value="{{ old('amount') }}" class="mt-1 w-full border rounded-md p-2" required>
                    @error('amount')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Acquired On</label>
                    <input type="date" name="acquired_on" value="{{ old('acquired_on') }}" class="mt-1 w-full border rounded-md p-2" required>
                    @error('acquired_on')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Description (optional)</label>
                    <textarea name="description" class="mt-1 w-full border rounded-md p-2" rows="3">{{ old('description') }}</textarea>
                    @error('description')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection