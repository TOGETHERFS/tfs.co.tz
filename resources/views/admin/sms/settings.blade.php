@extends('layouts.admin')

@section('title', 'SMS Provider Settings')
@section('page-title', 'SMS Provider Settings')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">SMS Provider Settings</h1>
        <p class="mt-1 text-sm text-gray-500">Configure your SMS provider API credentials</p>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-md bg-green-50 border border-green-200">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-md bg-red-50 border border-red-200">
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Provider Configuration</h3>
        </div>
        <form action="{{ route('admin.sms.update-settings') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API Key <span class="text-red-500">*</span></label>
                    <input type="text" name="api_key" value="{{ old('api_key', $settings->api_key) }}" required
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Secret Key <span class="text-red-500">*</span></label>
                    <input type="password" name="secret_key" value="{{ old('secret_key', $settings->secret_key) }}" required
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Default Sender ID</label>
                <input type="text" name="default_sender_id" value="{{ old('default_sender_id', $settings->default_sender_id) }}"
                       maxlength="11" placeholder="e.g., SMSALERT"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-xs text-gray-500">Fallback sender ID when tenant doesn't have one</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cost per SMS (TZS) <span class="text-red-500">*</span></label>
                    <input type="number" name="cost_per_sms" value="{{ old('cost_per_sms', $settings->cost_per_sms) }}" 
                           step="0.01" min="0" required
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Your cost from provider</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price per SMS (TZS) <span class="text-red-500">*</span></label>
                    <input type="number" name="selling_price_per_sms" value="{{ old('selling_price_per_sms', $settings->selling_price_per_sms) }}" 
                           step="0.01" min="0" required
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Price to charge tenants</p>
                </div>
            </div>

            @if($settings->exists)
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Current Status</h4>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Provider Balance</p>
                        <p class="font-semibold text-gray-900">{{ number_format($settings->provider_balance ?? 0) }} SMS</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Last Synced</p>
                        <p class="font-semibold text-gray-900">{{ $settings->balance_synced_at?->diffForHumans() ?? 'Never' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Profit Margin</p>
                        <p class="font-semibold text-green-600">{{ number_format($settings->profit_margin, 1) }}%</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                <div class="flex space-x-3">
                    <button type="button" onclick="syncBalance()" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200">
                        Sync Balance
                    </button>
                    <button type="button" onclick="syncSenderIds()" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200">
                        Sync Sender IDs
                    </button>
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function syncBalance() {
    fetch('{{ route("admin.sms.sync-balance") }}', { method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Balance synced: ' + data.balance + ' SMS');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to sync'));
            }
        });
}

function syncSenderIds() {
    fetch('{{ route("admin.sms.sync-sender-ids") }}', { method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Sender IDs synced successfully');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to sync'));
            }
        });
}
</script>
@endpush
@endsection
