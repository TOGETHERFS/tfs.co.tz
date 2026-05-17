@extends('settings.layout')

@section('settings_content')
<div class="p-6">
  <h1 class="text-2xl font-semibold mb-4">{{ __('messages.roles') }}</h1>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div>
      <h2 class="text-lg font-medium mb-3">{{ __('messages.add_role') }}</h2>
      <form method="POST" action="{{ route('settings.roles.store') }}" class="space-y-3">
        @csrf
        <div>
          <label class="block text-sm font-medium">{{ __('messages.role_name') }}</label>
          <input type="text" name="name" class="mt-1 border rounded w-full p-2" placeholder="e.g., CEO" required />
        </div>
        <div>
          <label class="block text-sm font-medium">{{ __('messages.role_category') }}</label>
          <select name="category" class="mt-1 border rounded w-full p-2">
            <option value="">{{ __('messages.select_category') }}</option>
            <option value="Executive">Executive</option>
            <option value="Management">Management</option>
            <option value="Operations">Operations</option>
            <option value="Finance">Finance</option>
            <option value="System">System</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium">{{ __('messages.role_code') }} ({{ __('messages.optional') }})</label>
          <input type="text" name="code" class="mt-1 border rounded w-full p-2" placeholder="e.g., ceo" />
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">{{ __('messages.add_role') }}</button>
      </form>
    </div>

    <div>
      <h2 class="text-lg font-medium mb-3">{{ __('messages.existing_roles') }}</h2>
      <div class="overflow-x-auto bg-white border rounded">
        <table class="min-w-full">
          <thead>
            <tr class="border-b bg-gray-50">
              <th class="text-left p-2">{{ __('messages.role_name') }}</th>
              <th class="text-left p-2">{{ __('messages.role_category') }}</th>
              <th class="text-left p-2">{{ __('messages.role_code') }}</th>
              <th class="text-left p-2">{{ __('messages.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse($roles as $role)
              <tr class="border-b">
                <td class="p-2">{{ $role['name'] ?? '' }}</td>
                <td class="p-2">{{ $role['category'] ?? 'Uncategorized' }}</td>
                <td class="p-2">{{ $role['code'] ?? '' }}</td>
                <td class="p-2">
                  <form method="POST" action="{{ route('settings.roles.delete', $role['code']) }}" onsubmit="return confirm('Delete this role?');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded">Delete</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="p-3 text-gray-500">{{ __('messages.no_roles_yet') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <p class="mt-6 text-sm text-gray-600">{{ __('messages.roles_tip') }}</p>
</div>
@endsection