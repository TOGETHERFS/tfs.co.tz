@extends('layouts.app')

@section('title', 'Edit Sender ID Request')
@section('page-title', 'Edit Sender ID Request')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Edit Sender ID Request</h1>
        <p class="mt-1 text-sm text-gray-500">Update your sender ID application details</p>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-md bg-red-50 border border-red-200">
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($senderIdRequest->status === 'rejected' && $senderIdRequest->admin_notes)
        <div class="p-4 rounded-md bg-red-50 border border-red-200">
            <h4 class="text-sm font-medium text-red-800">Rejection Reason:</h4>
            <p class="mt-1 text-sm text-red-700">{{ $senderIdRequest->admin_notes }}</p>
        </div>
    @endif

    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Edit Sender ID Application</h3>
        </div>
        <form action="{{ route('messages.update-sender-id', $senderIdRequest->id) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Sender ID <span class="text-red-500">*</span>
                </label>
                <input type="text" name="sender_id" value="{{ old('sender_id', $senderIdRequest->sender_id) }}" maxlength="11"
                       placeholder="e.g., MYCOMPANY"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 uppercase">
                <p class="mt-1 text-xs text-gray-500">Maximum 11 characters, letters and numbers only. This will appear as the sender name on SMS.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Company/Business Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="company_name" value="{{ old('company_name', $senderIdRequest->company_name) }}"
                       placeholder="Your registered business name"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Purpose <span class="text-red-500">*</span>
                </label>
                <textarea name="purpose" rows="3" placeholder="Describe how you will use this Sender ID..."
                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('purpose', $senderIdRequest->purpose) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">e.g., Loan notifications, payment reminders, customer communication</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Sample Message <span class="text-red-500">*</span>
                </label>
                <textarea name="sample_message" rows="3" maxlength="320"
                          placeholder="Provide an example of the messages you will send..."
                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('sample_message', $senderIdRequest->sample_message) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">This helps verify your intended use case</p>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                <h4 class="text-sm font-medium text-yellow-800">Note:</h4>
                <p class="mt-1 text-sm text-yellow-700">After updating, your request will be resubmitted for review.</p>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('messages.sender-ids') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Update Request
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
