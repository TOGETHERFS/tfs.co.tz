@extends('layouts.admin')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Admin Roles</h1>
      <p class="text-sm text-gray-600">View available roles and their assigned permissions</p>
    </div>
    <a href="{{ route('admin.staff.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">
      ← Back to Staff
    </a>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($roles as $role)
      <div class="bg-white border rounded-lg p-6 space-y-4">
        <div class="flex items-start justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">{{ $role->name }}</h3>
            <p class="text-sm text-gray-600 mt-1">{{ $role->description }}</p>
          </div>
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
            {{ $role->users_count }} {{ Str::plural('staff', $role->users_count) }}
          </span>
        </div>

        <div>
          <h4 class="text-sm font-semibold text-gray-700 mb-2">Permissions</h4>
          <div class="space-y-1">
            @php
              $permissions = $role->permissions->groupBy('module');
            @endphp
            @foreach($permissions as $module => $modulePermissions)
              <div class="text-sm">
                <span class="font-medium text-gray-700 capitalize">{{ ucfirst($module) }}:</span>
                <span class="text-gray-600">{{ $modulePermissions->count() }} {{ Str::plural('permission', $modulePermissions->count()) }}</span>
              </div>
            @endforeach
          </div>
        </div>

        <div class="pt-4 border-t">
          <button onclick="showPermissions('{{ $role->id }}')" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            View All Permissions →
          </button>
        </div>
      </div>
    @endforeach
  </div>
</div>

<!-- Permission Modal -->
<div id="permission-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
    <div class="p-6 border-b sticky top-0 bg-white">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900" id="modal-role-name"></h3>
        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>
    <div class="p-6" id="modal-permissions"></div>
  </div>
</div>

<script>
  const rolesData = @json($roles->mapWithKeys(function($role) {
    return [$role->id => [
      'name' => $role->name,
      'permissions' => $role->permissions->groupBy('module')->map(function($perms) {
        return $perms->pluck('name')->toArray();
      })->toArray()
    ]];
  }));

  function showPermissions(roleId) {
    const role = rolesData[roleId];
    if (!role) return;

    document.getElementById('modal-role-name').textContent = role.name + ' Permissions';
    
    let html = '<div class="space-y-4">';
    for (const [module, permissions] of Object.entries(role.permissions)) {
      html += `
        <div class="border rounded-lg p-4">
          <h4 class="text-sm font-semibold text-gray-700 mb-2 capitalize">${module}</h4>
          <ul class="space-y-1">
            ${permissions.map(p => `
              <li class="text-sm text-gray-600">
                <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                ${p}
              </li>
            `).join('')}
          </ul>
        </div>
      `;
    }
    html += '</div>';
    
    document.getElementById('modal-permissions').innerHTML = html;
    document.getElementById('permission-modal').classList.remove('hidden');
  }

  function closeModal() {
    document.getElementById('permission-modal').classList.add('hidden');
  }

  // Close modal on outside click
  document.getElementById('permission-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
      closeModal();
    }
  });
</script>
@endsection
