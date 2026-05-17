@extends('layouts.admin')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Admin Staff</h1>
      <p class="text-sm text-gray-600">Manage administrative staff members and their roles</p>
    </div>
    <div class="flex items-center gap-3">
      <a href="{{ route('admin.staff.roles') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">
        Manage Roles
      </a>
      <a href="{{ route('admin.staff.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
        + Create Staff
      </a>
    </div>
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

  <div class="bg-white border rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full">
        <thead>
          <tr class="border-b bg-gray-50">
            <th class="text-left p-4 text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="text-left p-4 text-xs font-medium text-gray-500 uppercase">Email</th>
            <th class="text-left p-4 text-xs font-medium text-gray-500 uppercase">Phone</th>
            <th class="text-left p-4 text-xs font-medium text-gray-500 uppercase">Role</th>
            <th class="text-left p-4 text-xs font-medium text-gray-500 uppercase">Position</th>
            <th class="text-left p-4 text-xs font-medium text-gray-500 uppercase">Created</th>
            <th class="text-right p-4 text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($staff as $member)
            <tr class="hover:bg-gray-50">
              <td class="p-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-blue-600 font-semibold text-sm">{{ strtoupper(substr($member->name, 0, 2)) }}</span>
                  </div>
                  <div>
                    <div class="font-medium text-gray-900">{{ $member->name }}</div>
                  </div>
                </div>
              </td>
              <td class="p-4 text-sm text-gray-600">{{ $member->email }}</td>
              <td class="p-4 text-sm text-gray-600">{{ $member->phone ?? 'N/A' }}</td>
              <td class="p-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                  {{ $member->adminRole->name ?? 'No Role' }}
                </span>
              </td>
              <td class="p-4 text-sm text-gray-600">{{ $member->position ?? 'N/A' }}</td>
              <td class="p-4 text-sm text-gray-600">{{ $member->created_at->format('M d, Y') }}</td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <a href="{{ route('admin.staff.edit', $member) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    Edit
                  </a>
                  @if($member->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.staff.destroy', $member) }}" onsubmit="return confirm('Are you sure you want to delete this staff member?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                        Delete
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="p-8 text-center text-gray-500">
                No staff members found. <a href="{{ route('admin.staff.create') }}" class="text-blue-600 hover:underline">Create one now</a>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @if($staff->hasPages())
    <div class="mt-4">
      {{ $staff->links() }}
    </div>
  @endif
</div>
@endsection
