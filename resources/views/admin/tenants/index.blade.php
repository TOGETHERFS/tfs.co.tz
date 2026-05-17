@extends('layouts.admin')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Tenant Management</h1>
      <p class="text-sm text-gray-600">Manage all tenants across the system</p>
    </div>
  </div>

  <div class="overflow-x-auto bg-white border rounded">
    <table class="min-w-full">
      <thead>
        <tr class="border-b bg-gray-50">
          <th class="text-left p-3">Name</th>
          <th class="text-left p-3">Slug</th>
          <th class="text-left p-3">Users</th>
          <th class="text-left p-3">Status</th>
          <th class="text-left p-3">Created</th>
          <th class="text-left p-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($tenants as $tenant)
          <tr class="border-b hover:bg-gray-50">
            <td class="p-3">
              <div>
                <div class="font-medium text-gray-900">{{ $tenant->name }}</div>
                @if($tenant->contact_email)
                  <div class="text-sm text-gray-500">{{ $tenant->contact_email }}</div>
                @endif
                @if($tenant->phone)
                  <div class="text-sm text-gray-500">{{ $tenant->phone }}</div>
                @endif
              </div>
            </td>
            <td class="p-3">
              <span class="text-sm font-mono bg-gray-100 px-2 py-1 rounded">{{ $tenant->slug }}</span>
            </td>
            <td class="p-3">
              <span class="text-sm text-gray-600">{{ $tenant->users_count ?? $tenant->users->count() }} users</span>
            </td>
            <td class="p-3">
              @if($tenant->status === 'active')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  Active
                </span>
              @elseif($tenant->status === 'trial')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  Trial
                </span>
              @elseif($tenant->status === 'suspended')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                  Suspended
                </span>
              @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  {{ ucfirst($tenant->status) }}
                </span>
              @endif
            </td>
            <td class="p-3">
              <span class="text-sm text-gray-600">{{ $tenant->created_at->format('M d, Y') }}</span>
            </td>
            <td class="p-3">
              <div class="flex items-center space-x-2">
                <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-blue-600 hover:text-blue-900 text-sm">
                  View
                </a>
                <a href="{{ route('admin.tenants.edit', $tenant) }}" class="text-green-600 hover:text-green-900 text-sm">
                  Edit
                </a>
                @if($tenant->status === 'active')
                  <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-orange-600 hover:text-orange-900 text-sm" onclick="return confirm('Are you sure you want to suspend this tenant?')">
                      Suspend
                    </button>
                  </form>
                @else
                  <form method="POST" action="{{ route('admin.tenants.activate', $tenant) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-green-600 hover:text-green-900 text-sm" onclick="return confirm('Are you sure you want to activate this tenant?')">
                      Activate
                    </button>
                  </form>
                @endif
                <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}" class="inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium" onclick="return confirm('Are you sure you want to DELETE this tenant? This will permanently remove the tenant and ALL related data (users, clients, loans). This action cannot be undone!')">
                    Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="p-6 text-center text-gray-500">
              No tenants found.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($tenants->hasPages())
    <div class="mt-4">
      {{ $tenants->links() }}
    </div>
  @endif
</div>
@endsection