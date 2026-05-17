@extends('settings.layout')

@section('settings_content')
<div class="p-6">
  <h1 class="text-2xl font-semibold mb-4">Login Logs</h1>
  <p class="text-gray-600 mb-4">Showing recent authentication-related activity.</p>

  <div class="overflow-x-auto bg-white border rounded">
    <table class="min-w-full">
      <thead>
        <tr class="border-b bg-gray-50">
          <th class="text-left p-2">Time</th>
          <th class="text-left p-2">User</th>
          <th class="text-left p-2">Description</th>
          <th class="text-left p-2">Properties</th>
        </tr>
      </thead>
      <tbody>
        @forelse($logs as $log)
          <tr class="border-b">
            <td class="p-2">{{ $log->created_at }}</td>
            <td class="p-2">{{ $log->causer_id ?? 'N/A' }}</td>
            <td class="p-2">{{ $log->description ?? '' }}</td>
            <td class="p-2 text-xs text-gray-600">{{ is_string($log->properties ?? null) ? $log->properties : json_encode($log->properties) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="p-3 text-gray-500">No login-related logs found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection