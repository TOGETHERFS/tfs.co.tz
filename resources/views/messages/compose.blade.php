@extends('layouts.app')

@section('title', __('messages.compose_message'))
@section('page-title', __('messages.compose_message'))

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.compose_message') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.sms_notifications_desc') }}</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-500">{{ __('messages.sms_balance') }}</p>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($balance->balance ?? 0) }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-md bg-green-50 border border-green-200">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-md bg-red-50 border border-red-200">
            <p class="text-sm text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    <form action="{{ route('messages.send') }}" method="POST" x-data="composeMessage()" class="space-y-6">
        @csrf

        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('messages.recipients') }}</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.send_to') }}</label>
                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="recipient_type" value="single" x-model="recipientType" class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">{{ __('messages.single_number') }}</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="recipient_type" value="multiple" x-model="recipientType" class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">{{ __('messages.selected_borrowers') }}</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="recipient_type" value="all" x-model="recipientType" class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">{{ __('messages.all_borrowers') }}</span>
                        </label>
                    </div>
                </div>

                <!-- Single Number Input -->
                <div x-show="recipientType === 'single'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.phone_number_label') }}</label>
                    <input type="text" name="phone" placeholder="e.g., 0712345678 or 255712345678"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">{{ __('messages.phone_hint') }}</p>
                </div>

                <!-- Multiple Selection -->
                <div x-show="recipientType === 'multiple'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.select_borrowers') }}</label>
                    <div class="border border-gray-300 rounded-md max-h-64 overflow-y-auto">
                        <div class="p-2 border-b border-gray-200 bg-gray-50">
                            <input type="text" x-model="searchQuery" placeholder="{{ __('messages.search_borrowers') }}"
                                   class="w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach($clients as $client)
                                <label class="flex items-center px-4 py-2 hover:bg-gray-50 cursor-pointer"
                                       x-show="!searchQuery || '{{ strtolower($client->first_name . ' ' . $client->last_name . ' ' . $client->phone) }}'.includes(searchQuery.toLowerCase())">
                                    <input type="checkbox" name="client_ids[]" value="{{ $client->id }}"
                                           class="text-blue-600 rounded focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-900">{{ $client->first_name }} {{ $client->last_name }}</span>
                                    <span class="ml-2 text-sm text-gray-500">{{ $client->phone }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Select multiple borrowers to send bulk SMS</p>
                </div>

                <!-- All Borrowers Warning -->
                <div x-show="recipientType === 'all'" x-cloak class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                This will send SMS to <strong>all {{ $clients->count() }} borrowers</strong> with valid phone numbers.
                                Make sure you have enough SMS balance.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Message</h3>
            </div>
            <div class="p-6 space-y-4">
                @if($senderIds->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sender ID</label>
                    <select name="sender_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @foreach($senderIds as $sid)
                            <option value="{{ $sid->sender_id }}" {{ $sid->is_default ? 'selected' : '' }}>
                                {{ $sid->sender_id }} {{ $sid->is_default ? '(Default)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @else
                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                    <p class="text-sm text-red-700">No approved Sender ID available. <a href="{{ route('messages.sender-ids') }}" class="font-medium underline">Request one</a></p>
                </div>
                @endif

                @if($templates->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Use Template (Optional)</label>
                    <select x-model="selectedTemplate" @change="applyTemplate()" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select a template --</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->content }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea name="message" rows="5" x-model="message" maxlength="800"
                              placeholder="Type your message here..."
                              class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    <div class="mt-1 flex justify-between text-xs text-gray-500">
                        <span>Characters: <span x-text="message.length"></span>/800</span>
                        <span>SMS count: <span x-text="Math.ceil(message.length / 160) || 0"></span></span>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-md p-3">
                    <p class="text-xs font-medium text-gray-700 mb-1">Available Variables:</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="insertVariable('{borrower_name}')" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-100">{borrower_name}</button>
                        <button type="button" @click="insertVariable('{loan_balance}')" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-100">{loan_balance}</button>
                        <button type="button" @click="insertVariable('{due_date}')" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-100">{due_date}</button>
                        <button type="button" @click="insertVariable('{due_amount}')" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-100">{due_amount}</button>
                        <button type="button" @click="insertVariable('{loan_number}')" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-100">{loan_number}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('messages.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                    :disabled="!message || {{ $senderIds->isEmpty() ? 'true' : 'false' }}">
                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
                Send Message
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function composeMessage() {
    return {
        recipientType: 'single',
        searchQuery: '',
        message: '',
        selectedTemplate: '',
        
        applyTemplate() {
            if (this.selectedTemplate) {
                this.message = this.selectedTemplate;
            }
        },
        
        insertVariable(variable) {
            this.message += variable;
        }
    }
}
</script>
@endpush
@endsection
