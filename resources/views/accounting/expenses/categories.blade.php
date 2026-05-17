@extends('layouts.user')

@section('title', 'Expense Categories')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <a href="{{ route('accounting.expenses.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Expenses</a>
                <h1 class="text-2xl font-bold text-gray-900 mt-2">Expense Categories</h1>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4">
            <p class="text-green-700">{{ session('success') }}</p>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
            <p class="text-red-700">{{ session('error') }}</p>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Add Category Form -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Category</h3>
                <form action="{{ route('accounting.expenses.categories.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Category Name *</label>
                            <input type="text" name="name" id="name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700">Code</label>
                            <input type="text" name="code" id="code"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Add Category
                        </button>
                    </div>
                </form>
            </div>

            <!-- Categories List -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Existing Categories</h3>
                </div>
                <ul class="divide-y divide-gray-200">
                    @forelse($categories as $category)
                    <li class="px-6 py-4 flex justify-between items-center hover:bg-gray-50">
                        <div>
                            <p class="font-medium text-gray-900">{{ $category->name }}</p>
                            @if($category->code)
                            <p class="text-xs text-gray-500">Code: {{ $category->code }}</p>
                            @endif
                        </div>
                        <form action="{{ route('accounting.expenses.categories.destroy', $category) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm" onclick="return confirm('Delete this category?')">
                                Delete
                            </button>
                        </form>
                    </li>
                    @empty
                    <li class="px-6 py-8 text-center text-gray-500">No categories yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
