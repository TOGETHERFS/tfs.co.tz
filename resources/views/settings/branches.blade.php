@extends('settings.layout')

@section('settings_content')
<div class="p-6">
  <h1 class="text-2xl font-semibold mb-4">{{ __('messages.branches') }}</h1>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div>
      <h2 class="text-lg font-medium mb-3">{{ __('messages.add_branch') }}</h2>
      <form method="POST" action="{{ route('settings.branches.store') }}" class="space-y-3">
        @csrf
        <div>
          <label class="block text-sm font-medium">{{ __('messages.branch_name') }}</label>
          <input type="text" name="name" class="mt-1 border rounded w-full p-2" required />
        </div>
        <div>
          <label class="block text-sm font-medium">{{ __('messages.branch_code') }}</label>
          <input type="text" name="code" class="mt-1 border rounded w-full p-2" />
        </div>
        <div>
          <label class="block text-sm font-medium">{{ __('messages.branch_address') }}</label>
          <input type="text" name="address" class="mt-1 border rounded w-full p-2" />
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">{{ __('messages.add_branch') }}</button>
      </form>
    </div>

    <div>
      <h2 class="text-lg font-medium mb-3">{{ __('messages.existing_branches') }}</h2>
      <div class="overflow-x-auto bg-white border rounded">
        <table class="min-w-full">
          <thead>
            <tr class="border-b bg-gray-50">
              <th class="text-left p-2">{{ __('messages.branch_name') }}</th>
              <th class="text-left p-2">{{ __('messages.branch_code') }}</th>
              <th class="text-left p-2">{{ __('messages.branch_address') }}</th>
              <th class="text-left p-2">{{ __('messages.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse($branches as $branch)
              <tr class="border-b">
                <td class="p-2">{{ $branch['name'] ?? '' }}</td>
                <td class="p-2">{{ $branch['code'] ?? '' }}</td>
                <td class="p-2">{{ $branch['address'] ?? '' }}</td>
                <td class="p-2">
                  <form method="POST" action="{{ route('settings.branches.delete', $branch['id']) }}" onsubmit="return confirm('Delete this branch?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded">Delete</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="p-3 text-gray-500">{{ __('messages.no_branches_yet') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection