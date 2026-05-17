@extends('layouts.app')

@section('title', 'Edit Sender ID Application')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('sender-ids.show', $senderId) }}" class="text-blue-600 hover:underline">&larr; Back to Details</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Edit Application: {{ $senderId->sender_id }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
        <form method="POST" action="{{ route('sender-ids.update', $senderId) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label for="sender_id" class="block text-sm font-medium text-gray-700">Sender ID</label>
                <input type="text" name="sender_id" id="sender_id" value="{{ old('sender_id', $senderId->sender_id) }}" required maxlength="11"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('sender_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="purpose" class="block text-sm font-medium text-gray-700">Purpose</label>
                <textarea name="purpose" id="purpose" rows="3" required
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('purpose', $senderId->purpose) }}</textarea>
                @error('purpose')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="documents" class="block text-sm font-medium text-gray-700">Additional Documents</label>
                <input type="file" name="documents[]" id="documents" multiple
                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('documents')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('sender-ids.show', $senderId) }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Update Application
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
