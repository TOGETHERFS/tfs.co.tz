@extends('layouts.admin')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Edit User</h1>
      <p class="text-sm text-gray-600">Update user information</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50">
      Back to Users
    </a>
  </div>

  @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
      <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="bg-white border rounded p-6">
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
      @csrf
      @method('PUT')

      <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" 
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
      </div>

      <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" 
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
      </div>

      <div>
        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
        <input type="tel" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" 
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
               placeholder="e.g., 255712345678">
      </div>

      <div>
        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
        <select name="role" id="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
          <option value="user" {{ old('role', $user->role) == 'user' ? 'selected' : '' }}>User</option>
          <option value="officer" {{ old('role', $user->role) == 'officer' ? 'selected' : '' }}>Officer</option>
          <option value="manager" {{ old('role', $user->role) == 'manager' ? 'selected' : '' }}>Manager</option>
          <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
          <option value="superadmin" {{ old('role', $user->role) == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
        </select>
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700">New Password (leave blank to keep current)</label>
        <input type="password" name="password" id="password" 
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
               minlength="8">
        <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
      </div>

      <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="text-sm font-medium text-gray-700 mb-2">User Info</h3>
        <dl class="grid grid-cols-2 gap-2 text-sm">
          <dt class="text-gray-500">Tenant ID:</dt>
          <dd class="text-gray-900">{{ $user->tenant_id ?? 'N/A' }}</dd>
          <dt class="text-gray-500">Joined:</dt>
          <dd class="text-gray-900">{{ $user->created_at->format('Y-m-d H:i') }}</dd>
          <dt class="text-gray-500">Status:</dt>
          <dd>
            @if($user->is_banned)
              <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Banned</span>
            @else
              <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>
            @endif
          </dd>
        </dl>
      </div>

      <div class="flex justify-end gap-3 pt-4 border-t">
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
          Cancel
        </a>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
          Update User
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
