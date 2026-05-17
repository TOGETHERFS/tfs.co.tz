@extends('layouts.admin')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">{{ $tenant->name }}</h1>
      <p class="text-sm text-gray-600">Tenant Details</p>
    </div>
    <div class="flex items-center space-x-3">
      <a href="{{ route('admin.tenants.index') }}" class="text-gray-600 hover:text-gray-900">
        ← Back to Tenants
      </a>
      @if($tenant->is_active)
        <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}" class="inline">
          @csrf
          @method('PATCH')
          <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700" onclick="return confirm('Are you sure you want to suspend this tenant?')">
            Suspend Tenant
          </button>
        </form>
      @else
        <form method="POST" action="{{ route('admin.tenants.activate', $tenant) }}" class="inline">
          @csrf
          @method('PATCH')
          <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700" onclick="return confirm('Are you sure you want to activate this tenant?')">
            Activate Tenant
          </button>
        </form>
      @endif
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Tenant Information -->
    <div class="lg:col-span-2 space-y-6">
      <div class="bg-white border rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Tenant Information</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <dt class="text-sm font-medium text-gray-500">Name</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->name }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Slug</dt>
            <dd class="mt-1 text-sm text-gray-900 font-mono bg-gray-100 px-2 py-1 rounded">{{ $tenant->slug }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Contact Email</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->contact_email ?? 'N/A' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Status</dt>
            <dd class="mt-1">
              @if($tenant->is_active)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  Active
                </span>
              @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                  Suspended
                </span>
              @endif
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Created</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->created_at->format('M d, Y \a\t g:i A') }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->updated_at->format('M d, Y \a\t g:i A') }}</dd>
          </div>
        </dl>
      </div>

      <!-- Users -->
      <div class="bg-white border rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Users ({{ $tenant->users->count() }})</h2>
        @if($tenant->users->count() > 0)
          <div class="overflow-x-auto">
            <table class="min-w-full">
              <thead>
                <tr class="border-b">
                  <th class="text-left py-2">Name</th>
                  <th class="text-left py-2">Email</th>
                  <th class="text-left py-2">Role</th>
                  <th class="text-left py-2">Joined</th>
                </tr>
              </thead>
              <tbody>
                @foreach($tenant->users as $user)
                  <tr class="border-b">
                    <td class="py-2">{{ $user->name }}</td>
                    <td class="py-2">{{ $user->email }}</td>
                    <td class="py-2">
                      <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ $user->role ?? 'N/A' }}</span>
                    </td>
                    <td class="py-2 text-sm text-gray-600">{{ $user->created_at->format('M d, Y') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-gray-500">No users found for this tenant.</p>
        @endif
      </div>
    </div>

    <!-- Statistics -->
    <div class="space-y-6">
      <div class="bg-white border rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Statistics</h2>
        <dl class="space-y-4">
          <div>
            <dt class="text-sm font-medium text-gray-500">Total Users</dt>
            <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $tenant->users->count() }}</dd>
          </div>
          @if(isset($tenant->clients))
            <div>
              <dt class="text-sm font-medium text-gray-500">Total Clients</dt>
              <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $tenant->clients->count() }}</dd>
            </div>
          @endif
          @if(isset($tenant->loans))
            <div>
              <dt class="text-sm font-medium text-gray-500">Total Loans</dt>
              <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $tenant->loans->count() }}</dd>
            </div>
          @endif
        </dl>
      </div>

      @if($tenant->trial_ends_at)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <h3 class="text-sm font-medium text-yellow-800">Trial Information</h3>
          <p class="mt-1 text-sm text-yellow-700">
            Trial ends: {{ \Carbon\Carbon::parse($tenant->trial_ends_at)->format('M d, Y \a\t g:i A') }}
          </p>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection