@extends('layouts.app')

@section('title', 'Compose Message')
@section('page-title', 'Compose Message')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Compose SMS</h1>
            <p class="mt-1 text-sm text-gray-500">Send SMS to your borrowers</p>
        </div>
        <div>
            <a href="{{ route('messages.index') }}" class="px-4 py-2 border rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">Back to Messages</a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">New SMS</h3>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('messages.send') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="recipient_type" value="multiple">

                <div>
                    <label for="sender_id" class="block text-sm font-medium text-gray-700">Sender ID</label>
                    @if(isset($senderIds) && $senderIds->count() > 0)
                        <select id="sender_id" name="sender_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">-- Select Sender ID --</option>
                            @foreach($senderIds as $sid)
                                <option value="{{ $sid->sender_id }}" {{ old('sender_id') == $sid->sender_id ? 'selected' : '' }}>
                                    {{ $sid->sender_id }}
                                </option>
                            @endforeach
                            <option value="{{ config('services.beem.sender_id', 'PHIDTECH') }}" {{ old('sender_id') == config('services.beem.sender_id', 'PHIDTECH') ? 'selected' : '' }}>
                                {{ config('services.beem.sender_id', 'PHIDTECH') }} (Default)
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Select from your approved sender IDs</p>
                    @else
                        <input id="sender_id" name="sender_id" type="text" value="{{ old('sender_id', config('services.beem.sender_id', 'PHIDTECH')) }}" maxlength="11"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Default: {{ config('services.beem.sender_id', 'PHIDTECH') }}. <a href="{{ route('messages.request-sender-id') }}" class="text-blue-600 hover:underline">Request custom sender ID</a></p>
                    @endif
                    @error('sender_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
                    <textarea id="message" name="message" rows="4" required
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Standard SMS segments apply (160 chars per segment).</p>
                </div>

                <div class="bg-gray-50 rounded-lg p-4 border">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Recipients</label>
                    <p class="text-xs text-gray-500 mb-3">Select borrowers from the list AND/OR enter phone numbers manually.</p>
                    
                    @error('recipients')
                        <div class="mb-3 p-3 bg-red-50 border border-red-200 rounded-md">
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        </div>
                    @enderror
                    
                    <!-- Select from Borrowers -->
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Select Borrower(s) from List</label>
                        @if(isset($clients) && $clients->count() > 0)
                        <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-md p-3 bg-white">
                            @foreach($clients as $client)
                                <label class="flex items-center space-x-2 py-1 hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" name="client_ids[]" value="{{ $client->id }}" 
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                           {{ in_array($client->id, old('client_ids', [])) ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-700">
                                        {{ $client->first_name }} {{ $client->last_name }} 
                                        <span class="text-gray-500">({{ $client->phone }})</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Check one or more borrowers to send SMS</p>
                        @else
                        <p class="text-sm text-gray-500 italic py-2">No borrowers available. Add borrowers first or enter phone numbers below.</p>
                        @endif
                    </div>

                    <!-- Enter Phone Numbers Manually -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-2">OR Enter Phone Number(s) Manually</label>
                        <input type="text" id="phone" name="phone" placeholder="255712345678, 0712345678"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="{{ old('phone') }}">
                        <p class="mt-1 text-xs text-gray-500">Enter phone number(s) separated by spaces or commas.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t">
                    <a href="{{ route('messages.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Send SMS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection