@extends('settings.layout')

@section('settings_content')
<div class="p-6">
  <h1 class="text-2xl font-semibold mb-4">{{ __('messages.staffs') }}</h1>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div>
      <h2 class="text-lg font-medium mb-3">{{ __('messages.create_staff') }}</h2>
      <form method="POST" action="{{ route('settings.users.store') }}" class="space-y-3">
        @csrf
        <div>
          <label class="block text-sm font-medium">{{ __('messages.staff_name') }}</label>
          <input type="text" name="name" class="mt-1 border rounded w-full p-2" required />
        </div>
        <div>
          <label class="block text-sm font-medium">{{ __('messages.staff_email') }}</label>
          <input type="email" name="email" class="mt-1 border rounded w-full p-2" required />
        </div>
        <div>
          <label class="block text-sm font-medium">{{ __('messages.staff_password') }}</label>
          <input type="password" name="password" class="mt-1 border rounded w-full p-2" required />
        </div>
        <div>
          <label class="block text-sm font-medium">{{ __('messages.staff_role') }}</label>
          @php($groupedRoles = collect($roles)->groupBy('category'))
          <select name="role_code" class="mt-1 border rounded w-full p-2" required onchange="if(!this.form.position.value){this.form.position.value=this.value}">
            <option value="">{{ __('messages.select_role') }}</option>
            @foreach($groupedRoles as $category => $items)
              <optgroup label="{{ $category ?? 'Uncategorized' }}">
                @foreach($items as $role)
                  <option value="{{ $role['code'] }}">{{ $role['name'] }}</option>
                @endforeach
              </optgroup>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium">{{ __('messages.staff_position') }}</label>
          <input type="text" name="position" class="mt-1 border rounded w-full p-2" placeholder="e.g., Senior" />
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">{{ __('messages.create_staff') }}</button>
      </form>
    </div>

    <div>
      <h2 class="text-lg font-medium mb-3">{{ __('messages.staff_list') }}</h2>
      <div class="overflow-x-auto bg-white border rounded">
        <table class="min-w-full">
          <thead>
            <tr class="border-b bg-gray-50">
              <th class="text-left p-2">{{ __('messages.staff_name') }}</th>
              <th class="text-left p-2">{{ __('messages.staff_email') }}</th>
              <th class="text-left p-2">{{ __('messages.staff_role') }}</th>
              <th class="text-left p-2">{{ __('messages.staff_category') }}</th>
              <th class="text-left p-2">{{ __('messages.staff_position') }}</th>
              <th class="text-left p-2">{{ __('messages.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse($staffs as $staff)
              <tr class="border-b">
                <td class="p-2">{{ $staff->name }}</td>
                <td class="p-2">{{ $staff->email }}</td>
                <td class="p-2">{{ collect($roles)->firstWhere('code', $staff->role)['name'] ?? $staff->role }}</td>
                <td class="p-2">{{ collect($roles)->firstWhere('code', $staff->role)['category'] ?? 'Uncategorized' }}</td>
                <td class="p-2">{{ $staff->position ?? '-' }}</td>
                <td class="p-2 space-x-2">
                  <form method="POST" action="{{ route('settings.users.update', $staff) }}" class="inline-flex items-center space-x-2">
                    @csrf
                    @method('PUT')
                    @php($groupedRoles = collect($roles)->groupBy('category'))
                    <select name="role_code" class="border rounded p-1 text-sm">
                      @foreach($groupedRoles as $category => $items)
                        <optgroup label="{{ $category ?? 'Uncategorized' }}">
                          @foreach($items as $role)
                            <option value="{{ $role['code'] }}" {{ ($staff->position === $role['code'] || $staff->role === $role['code']) ? 'selected' : '' }}>{{ $role['name'] }}</option>
                          @endforeach
                        </optgroup>
                      @endforeach
                    </select>
                    <input type="text" name="position" value="{{ $staff->position }}" placeholder="Position" class="border rounded p-1 text-sm" />
                    <button type="submit" class="px-2 py-1 bg-green-600 text-white rounded text-sm">Update</button>
                  </form>
                  <form method="POST" action="{{ route('settings.users.delete', $staff) }}" onsubmit="return confirm('Delete this staff?');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-2 py-1 bg-red-600 text-white rounded text-sm">Delete</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="p-3 text-gray-500">{{ __('messages.no_staff_yet') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <p class="mt-6 text-sm text-gray-600">{{ __('messages.staff_tip') }}</p>
</div>
@endsection