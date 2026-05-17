@extends('layouts.admin')

@section('content')
<div class="px-6 py-6">
    <h2 class="text-2xl font-semibold text-gray-900 mb-4">Payment Gateway (Selcom)</h2>
    <p class="text-sm text-gray-600 mb-6">Configure Selcom API credentials used for subscription purchases.</p>

    <div class="bg-white shadow-sm border border-gray-200 rounded-lg p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <div>
                    <label for="selcom_merchant_id" class="block text-sm font-medium text-gray-700">Merchant ID</label>
                    <input type="text" id="selcom_merchant_id" name="selcom_merchant_id" value="{{ old('selcom_merchant_id', $settings['selcom_merchant_id']) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" required>
                    <p class="mt-1 text-xs text-gray-500">Provided by Selcom (e.g., MID/merchant code).</p>
                </div>

                <div>
                    <label for="selcom_api_key" class="block text-sm font-medium text-gray-700">API Key</label>
                    <input type="text" id="selcom_api_key" name="selcom_api_key" value="{{ old('selcom_api_key', $settings['selcom_api_key']) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" required>
                </div>

                <div>
                    <label for="selcom_api_secret" class="block text-sm font-medium text-gray-700">API Secret</label>
                    <input type="password" id="selcom_api_secret" name="selcom_api_secret" value="{{ old('selcom_api_secret', $settings['selcom_api_secret']) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" required>
                </div>

                <div>
                    <label for="selcom_base_url" class="block text-sm font-medium text-gray-700">Base URL</label>
                    <input type="url" id="selcom_base_url" name="selcom_base_url" value="{{ old('selcom_base_url', $settings['selcom_base_url']) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" required>
                    <p class="mt-1 text-xs text-gray-500">Example: https://api.selcom.net</p>
                </div>

                <div>
                    <label for="selcom_webhook_secret" class="block text-sm font-medium text-gray-700">Webhook Secret (optional)</label>
                    <input type="text" id="selcom_webhook_secret" name="selcom_webhook_secret" value="{{ old('selcom_webhook_secret', $settings['selcom_webhook_secret']) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Save Credentials</button>
                <span class="text-xs text-gray-500">Only administrators can edit these settings.</span>
            </div>
        </form>
    </div>

    <div class="mt-8 max-w-2xl text-xs text-gray-500">
        <p>Note: Credentials are stored in cache for now. For production, persist them in a secure settings table or encrypted storage and load them for billing flows.</p>
    </div>
</div>
@endsection