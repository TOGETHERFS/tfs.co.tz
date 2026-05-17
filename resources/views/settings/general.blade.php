@extends('settings.layout')

@section('settings_content')
<div class="p-6 max-w-5xl">
  <h1 class="text-2xl font-semibold mb-4">General Settings</h1>
  <p class="text-gray-600 mb-6">Update organization details, formatting, and branding.</p>

  @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
      {{ session('success') }}
    </div>
  @endif

  @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
      <ul class="list-disc ml-5">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form action="{{ route('settings.general.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
        <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}"
               class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary-500" required>
      </div>
      <div>
        <label for="company_email" class="block text-sm font-medium text-gray-700 mb-2">Company Email</label>
        <input type="email" id="company_email" name="company_email" value="{{ old('company_email', $settings['company_email'] ?? '') }}"
               class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary-500">
      </div>

      <div>
        <label for="company_phone" class="block text-sm font-medium text-gray-700 mb-2">Company Phone</label>
        <input type="text" id="company_phone" name="company_phone" value="{{ old('company_phone', $settings['company_phone'] ?? '') }}"
               class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary-500">
      </div>
      <div>
        <label for="company_website" class="block text-sm font-medium text-gray-700 mb-2">Company Website</label>
        <input type="url" id="company_website" name="company_website" value="{{ old('company_website', $settings['company_website'] ?? '') }}"
               class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary-500">
      </div>

      <div class="md:col-span-2">
        <label for="company_address" class="block text-sm font-medium text-gray-700 mb-2">Company Address</label>
        <textarea id="company_address" name="company_address" rows="3" class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary-500">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
      </div>

      <div>
        <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
        <select id="timezone" name="timezone" class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary-500">
          @php($tz = old('timezone', $settings['timezone'] ?? 'UTC'))
          @foreach([ 'UTC','Africa/Nairobi','Africa/Lagos','Africa/Accra','Africa/Johannesburg','Europe/London','Europe/Berlin','Asia/Dubai','Asia/Kolkata'] as $zone)
            <option value="{{ $zone }}" {{ $tz === $zone ? 'selected' : '' }}>{{ $zone }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="date_format" class="block text-sm font-medium text-gray-700 mb-2">Date Format</label>
        @php($df = old('date_format', $settings['date_format'] ?? 'Y-m-d'))
        <select id="date_format" name="date_format" class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary-500">
          @foreach(['Y-m-d','d/m/Y','m/d/Y'] as $fmt)
            <option value="{{ $fmt }}" {{ $df === $fmt ? 'selected' : '' }}>{{ $fmt }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
        @php($cur = old('currency', $settings['currency'] ?? 'TZS'))
        <select id="currency" name="currency" class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary-500">
          @foreach(['TZS','USD','KES','NGN','GHS','ZAR','EUR'] as $c)
            <option value="{{ $c }}" {{ $cur === $c ? 'selected' : '' }}>{{ $c }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="currency_symbol" class="block text-sm font-medium text-gray-700 mb-2">Currency Symbol</label>
        <input type="text" id="currency_symbol" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? 'TSHS') }}"
               class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary-500">
      </div>

      <div class="md:col-span-2">
        <label for="company_logo" class="block text-sm font-medium text-gray-700 mb-2">Company Logo</label>
        <input type="file" id="company_logo" name="company_logo" accept="image/*" class="block w-full text-sm text-gray-700">
        @if(!empty($settings['company_logo']))
          <div class="mt-2">
            <img src="{{ Storage::url($settings['company_logo']) }}" alt="Company Logo" class="h-12 object-contain border rounded-md p-2 bg-white">
          </div>
        @endif
      </div>
    </div>

    <div class="pt-4">
      <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Changes</button>
    </div>
  </form>
</div>
@endsection