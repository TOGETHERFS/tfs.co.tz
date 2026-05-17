@extends('layouts.admin')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">All Users</h1>
      <p class="text-sm text-gray-600">System-wide users across all tenants</p>
    </div>
    <form method="GET" class="flex items-center gap-2">
      <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or email" class="border rounded px-3 py-2 text-sm" />
      <button class="px-3 py-2 bg-blue-600 text-white rounded text-sm">Search</button>
    </form>
  </div>

  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
      {{ session('success') }}
    </div>
  @endif

  @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
      {{ session('error') }}
    </div>
  @endif

  @forelse($groupedUsers as $tenantId => $users)
    @php $tenant = $users->first()->tenant; @endphp
    <div class="bg-white border rounded overflow-hidden">
      <div class="bg-blue-50 border-b px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold text-sm">{{ $tenant ? strtoupper(substr($tenant->name, 0, 1)) : '?' }}</span>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">{{ $tenant->name ?? 'No Tenant' }}</h3>
            <p class="text-xs text-gray-500">Tenant ID: {{ $tenantId ?? 'N/A' }} &middot; {{ $users->count() }} {{ Str::plural('user', $users->count()) }}</p>
          </div>
        </div>
        <span class="text-xs font-medium px-2 py-1 rounded-full {{ $tenant && $tenant->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
          {{ $tenant->status ?? 'N/A' }}
        </span>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="border-b bg-gray-50">
              <th class="text-left p-2 text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="text-left p-2 text-xs font-medium text-gray-500 uppercase">Email</th>
              <th class="text-left p-2 text-xs font-medium text-gray-500 uppercase">Role</th>
              <th class="text-left p-2 text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="text-left p-2 text-xs font-medium text-gray-500 uppercase">Joined</th>
              <th class="text-left p-2 text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($users as $user)
              <tr class="border-b {{ $user->is_banned ? 'bg-red-50' : '' }}">
                <td class="p-2 text-sm">{{ $user->name }}</td>
                <td class="p-2 text-sm">{{ $user->email }}</td>
                <td class="p-2 text-sm">{{ ucfirst($user->role ?? 'N/A') }}</td>
                <td class="p-2">
                  @if($user->is_banned)
                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Banned</span>
                  @else
                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>
                  @endif
                </td>
                <td class="p-2 text-sm">{{ $user->created_at->format('Y-m-d H:i') }}</td>
                <td class="p-2">
                  <div class="flex items-center gap-2">
                    <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                    
                    @if($user->id !== auth()->id())
                      @if($user->is_banned)
                        <form method="POST" action="{{ route('admin.users.unban', $user) }}" class="inline">
                          @csrf
                          <button type="submit" class="text-green-600 hover:text-green-800 text-sm font-medium">Unban</button>
                        </form>
                      @else
                        <form method="POST" action="{{ route('admin.users.ban', $user) }}" class="inline" onsubmit="return confirm('Are you sure you want to ban this user?')">
                          @csrf
                          <button type="submit" class="text-orange-600 hover:text-orange-800 text-sm font-medium">Ban</button>
                        </form>
                      @endif
                      
                      <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                      </form>
                    @else
                      <span class="text-gray-400 text-xs">(You)</span>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @empty
    <div class="bg-white border rounded p-6 text-center text-gray-500">No users found.</div>
  @endforelse

</div>
@endsection