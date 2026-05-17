@extends('layouts.app')

@section('title', 'Message Templates')
@section('page-title', 'Message Templates')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Message Templates</h1>
            <p class="mt-1 text-sm text-gray-500">Create and manage reusable message templates</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-md bg-green-50 border border-green-200">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Create Template Form -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">New Template</h3>
                </div>
                <form action="{{ route('messages.store-template') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Template Name</label>
                        <input type="text" name="name" required placeholder="e.g., Payment Reminder"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message Content</label>
                        <textarea name="content" rows="4" required maxlength="800"
                                  placeholder="Dear {borrower_name}, your payment of {due_amount} is due on {due_date}..."
                                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    <div class="bg-gray-50 rounded-md p-3">
                        <p class="text-xs font-medium text-gray-700 mb-2">Available Variables:</p>
                        <div class="text-xs text-gray-500 space-y-1">
                            <p><code class="bg-gray-200 px-1 rounded">{borrower_name}</code> - Full name</p>
                            <p><code class="bg-gray-200 px-1 rounded">{first_name}</code> - First name</p>
                            <p><code class="bg-gray-200 px-1 rounded">{loan_balance}</code> - Outstanding balance</p>
                            <p><code class="bg-gray-200 px-1 rounded">{due_date}</code> - Payment due date</p>
                            <p><code class="bg-gray-200 px-1 rounded">{due_amount}</code> - Amount due</p>
                            <p><code class="bg-gray-200 px-1 rounded">{loan_number}</code> - Loan reference</p>
                        </div>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                        Create Template
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Templates -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Your Templates</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($templates as $template)
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <h4 class="font-medium text-gray-900">{{ $template->name }}</h4>
                                        @if($template->is_system)
                                            <span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-800 rounded">System</span>
                                        @endif
                                        <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded">{{ $categories[$template->category] ?? $template->category }}</span>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600 whitespace-pre-wrap">{{ $template->content }}</p>
                                </div>
                                @if(!$template->is_system)
                                    <form action="{{ route('messages.delete-template', $template) }}" method="POST" 
                                          onsubmit="return confirm('Delete this template?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No templates yet</p>
                            <p class="text-xs text-gray-400">Create your first template to get started</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
