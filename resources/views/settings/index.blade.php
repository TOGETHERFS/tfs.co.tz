@extends('settings.layout')

@section('settings_content')
<div class="p-6">
  <h1 class="text-2xl font-semibold mb-4">Settings</h1>
  <p class="text-gray-600 mb-6">Manage account, roles, branches, staffs, logs, security and policies.</p>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <a href="{{ route('settings.general') }}" class="block border rounded p-4 hover:bg-gray-50">
      <div class="font-medium">General</div>
      <div class="text-sm text-gray-500">Organization info, formatting and branding</div>
    </a>
    <a href="{{ route('settings.account') }}" class="block border rounded p-4 hover:bg-gray-50">
      <div class="font-medium">Account</div>
      <div class="text-sm text-gray-500">Your profile and preferences</div>
    </a>
    <a href="{{ route('settings.roles') }}" class="block border rounded p-4 hover:bg-gray-50">
      <div class="font-medium">Roles</div>
      <div class="text-sm text-gray-500">Manage user roles and permissions</div>
    </a>
    <a href="{{ route('settings.branches') }}" class="block border rounded p-4 hover:bg-gray-50">
      <div class="font-medium">Branches</div>
      <div class="text-sm text-gray-500">Manage organization branches</div>
    </a>
    <a href="{{ route('settings.staffs') }}" class="block border rounded p-4 hover:bg-gray-50">
      <div class="font-medium">Staffs</div>
      <div class="text-sm text-gray-500">Manage staff members</div>
    </a>
    <a href="{{ route('settings.login-logs') }}" class="block border rounded p-4 hover:bg-gray-50">
      <div class="font-medium">Login Logs</div>
      <div class="text-sm text-gray-500">Review recent authentication activity</div>
    </a>
    <a href="{{ route('settings.security') }}" class="block border rounded p-4 hover:bg-gray-50">
      <div class="font-medium">Security</div>
      <div class="text-sm text-gray-500">Password and session policies</div>
    </a>
    <a href="{{ route('settings.notifications') }}" class="block border rounded p-4 hover:bg-gray-50">
      <div class="font-medium">Notifications</div>
      <div class="text-sm text-gray-500">Email and SMS alerts configuration</div>
    </a>
    <a href="{{ route('settings.policies') }}" class="block border rounded p-4 hover:bg-gray-50">
      <div class="font-medium">Policies</div>
      <div class="text-sm text-gray-500">Privacy and Terms of Service</div>
    </a>
    <a href="{{ route('settings.backup') }}" class="block border rounded p-4 hover:bg-gray-50">
      <div class="font-medium">Backup & Restore</div>
      <div class="text-sm text-gray-500">Configure backups and perform restores</div>
    </a>
  </div>
</div>
@endsection