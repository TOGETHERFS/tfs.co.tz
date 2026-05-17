@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Edit Staff Member</h1>
      <p class="text-sm text-gray-600">Update staff member information and role</p>
    </div>
    <a href="{{ route('admin.staff.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">
      ← Back to Staff
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

  <form method="POST" action="{{ route('admin.staff.update', $staff) }}" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="bg-white border rounded-lg p-6 space-y-6">
      <h2 class="text-lg font-semibold text-gray-900">Basic Information</h2>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
          <input type="text" name="name" id="name" value="{{ old('name', $staff->name) }}" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
          <input type="email" name="email" id="email" value="{{ old('email', $staff->email) }}" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
          <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
          <input type="text" name="phone" id="phone" value="{{ old('phone', $staff->phone) }}"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
          <label for="position" class="block text-sm font-medium text-gray-700 mb-2">Position/Title</label>
          <input type="text" name="position" id="position" value="{{ old('position', $staff->position) }}" placeholder="e.g., Senior Accountant"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password (leave blank to keep current)</label>
          <input type="password" name="password" id="password"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
          <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
          <input type="password" name="password_confirmation" id="password_confirmation"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
      </div>
    </div>

    <div class="bg-white border rounded-lg p-6 space-y-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Role & Permissions</h2>
        <p class="text-sm text-gray-600">Select a role to assign predefined permissions to this staff member</p>
      </div>

      <div>
        <label for="admin_role_id" class="block text-sm font-medium text-gray-700 mb-2">Select Role *</label>
        <select name="admin_role_id" id="admin_role_id" required onchange="showRolePermissions(this.value)"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          <option value="">-- Select a Role --</option>
          @foreach($roles as $role)
            <option value="{{ $role->id }}" {{ old('admin_role_id', $staff->admin_role_id) == $role->id ? 'selected' : '' }}>
              {{ $role->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div id="role-permissions" class="hidden">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Permissions for Selected Role</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          @foreach($groupedPermissions as $module => $permissions)
            <div class="border rounded-lg p-4">
              <h4 class="text-sm font-semibold text-gray-700 mb-2 capitalize">{{ ucfirst($module) }}</h4>
              <ul class="space-y-1" data-module="{{ $module }}">
                @foreach($permissions as $permission)
                  <li class="text-sm text-gray-600 permission-item" data-permission-id="{{ $permission->id }}" style="display: none;">
                    <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                    {{ $permission->name }}
                  </li>
                @endforeach
              </ul>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="flex items-center justify-end gap-3">
      <a href="{{ route('admin.staff.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">
        Cancel
      </a>
      <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
        Update Staff Member
      </button>
    </div>
  </form>
</div>

<script>
  const rolePermissions = @json($roles->mapWithKeys(function($role) {
    return [$role->id => $role->permissions->pluck('id')->toArray()];
  }));

  function showRolePermissions(roleId) {
    const container = document.getElementById('role-permissions');
    const allPermissionItems = document.querySelectorAll('.permission-item');
    
    // Hide all permission items first
    allPermissionItems.forEach(item => item.style.display = 'none');
    
    if (roleId && rolePermissions[roleId]) {
      const permissions = rolePermissions[roleId];
      
      // Show permissions for selected role
      permissions.forEach(permId => {
        const item = document.querySelector(`.permission-item[data-permission-id="${permId}"]`);
        if (item) {
          item.style.display = 'block';
        }
      });
      
      container.classList.remove('hidden');
    } else {
      container.classList.add('hidden');
    }
  }

  // Show permissions if role is pre-selected
  document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('admin_role_id');
    if (roleSelect.value) {
      showRolePermissions(roleSelect.value);
    }
  });
</script>
@endsection
