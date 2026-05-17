@extends('settings.layout')

@section('settings_content')
<div class="p-6 max-w-3xl">
  <h1 class="text-2xl font-semibold mb-4">Security Settings</h1>

  <form method="POST" action="{{ route('settings.security.update') }}" class="space-y-4">
    @csrf
    <div>
      <label class="block text-sm font-medium">Password Minimum Length</label>
      <input type="number" name="password_min_length" value="{{ $settings['password_min_length'] }}" class="mt-1 border rounded w-full p-2" />
    </div>
    <div class="grid grid-cols-2 gap-4">
      <label class="inline-flex items-center"><input type="checkbox" name="password_require_uppercase" {{ $settings['password_require_uppercase'] ? 'checked' : '' }} /> <span class="ml-2">Require Uppercase</span></label>
      <label class="inline-flex items-center"><input type="checkbox" name="password_require_lowercase" {{ $settings['password_require_lowercase'] ? 'checked' : '' }} /> <span class="ml-2">Require Lowercase</span></label>
      <label class="inline-flex items-center"><input type="checkbox" name="password_require_numbers" {{ $settings['password_require_numbers'] ? 'checked' : '' }} /> <span class="ml-2">Require Numbers</span></label>
      <label class="inline-flex items-center"><input type="checkbox" name="password_require_symbols" {{ $settings['password_require_symbols'] ? 'checked' : '' }} /> <span class="ml-2">Require Symbols</span></label>
    </div>
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium">Session Timeout (minutes)</label>
        <input type="number" name="session_timeout" value="{{ $settings['session_timeout'] }}" class="mt-1 border rounded w-full p-2" />
      </div>
      <div>
        <label class="block text-sm font-medium">Max Login Attempts</label>
        <input type="number" name="max_login_attempts" value="{{ $settings['max_login_attempts'] }}" class="mt-1 border rounded w-full p-2" />
      </div>
      <div>
        <label class="block text-sm font-medium">Lockout Duration (minutes)</label>
        <input type="number" name="lockout_duration" value="{{ $settings['lockout_duration'] }}" class="mt-1 border rounded w-full p-2" />
      </div>
      <div class="flex items-center">
        <label class="inline-flex items-center"><input type="checkbox" name="two_factor_auth" {{ $settings['two_factor_auth'] ? 'checked' : '' }} /> <span class="ml-2">Enable Two-factor Authentication</span></label>
      </div>
    </div>

    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save Changes</button>
  </form>
</div>
@endsection