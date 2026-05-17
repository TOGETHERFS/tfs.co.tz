@extends('settings.layout')

@section('settings_content')
<div class="p-6 max-w-3xl">
  <h1 class="text-2xl font-semibold mb-4">Account Settings</h1>
  <p class="text-gray-600 mb-4">Manage your personal account details and preferences.</p>
  <div class="space-y-4">
    <a href="{{ route('profile.edit') }}" class="inline-block px-4 py-2 bg-blue-600 text-white rounded">Edit Profile</a>
    <p class="text-sm text-gray-500">Profile edit provides name, email, password and related options.</p>
  </div>
</div>
@endsection