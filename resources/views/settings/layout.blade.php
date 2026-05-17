@extends('layouts.user')

@section('content')
<div class="flex gap-6">
  <!-- Settings Sidebar -->
  <aside class="w-64 flex-shrink-0">
    <div class="bg-white border border-gray-200 rounded-md overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-200">
        <h2 class="text-sm font-semibold text-gray-700">{{ __('messages.settings') }}</h2>
        <p class="text-xs text-gray-500">{{ __('messages.manage_system_config') }}</p>
      </div>
      <nav class="p-2 space-y-1">
        <a href="{{ route('settings.index') }}" class="block px-3 py-2 rounded {{ request()->routeIs('settings.index') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('messages.overview') }}</a>
        <a href="{{ route('settings.general') }}" class="block px-3 py-2 rounded {{ request()->routeIs('settings.general') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('messages.general') }}</a>
        <a href="{{ route('settings.notifications') }}" class="block px-3 py-2 rounded {{ request()->routeIs('settings.notifications') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('messages.notifications') }}</a>
        <a href="{{ route('settings.security') }}" class="block px-3 py-2 rounded {{ request()->routeIs('settings.security') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('messages.security') }}</a>
        <a href="{{ route('settings.policies') }}" class="block px-3 py-2 rounded {{ request()->routeIs('settings.policies') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('messages.policies') }}</a>
        <a href="{{ route('settings.branches') }}" class="block px-3 py-2 rounded {{ request()->routeIs('settings.branches') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('messages.branches') }}</a>
        <a href="{{ route('settings.users') }}" class="block px-3 py-2 rounded {{ request()->routeIs('settings.users') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('messages.users') }}</a>
        <a href="{{ route('settings.staffs') }}" class="block px-3 py-2 rounded {{ request()->routeIs('settings.staffs') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('messages.staffs') }}</a>
        <a href="{{ route('settings.roles') }}" class="block px-3 py-2 rounded {{ request()->routeIs('settings.roles') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('messages.roles') }}</a>
        <a href="{{ route('settings.login-logs') }}" class="block px-3 py-2 rounded {{ request()->routeIs('settings.login-logs') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('messages.login_logs') }}</a>
        <a href="{{ route('settings.loan') }}" class="block px-3 py-2 rounded {{ request()->routeIs('settings.loan') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('messages.loan_settings') }}</a>
        <a href="{{ route('settings.backup') }}" class="block px-3 py-2 rounded {{ request()->routeIs('settings.backup') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ __('messages.backup_restore') }}</a>
      </nav>
    </div>
  </aside>

  <!-- Settings Content -->
  <section class="flex-1">
    @yield('settings_content')
  </section>
</div>
@endsection