@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto p-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-semibold">Messages Control</h1>
            <p class="text-sm text-gray-600">Enable or disable messaging per tenant, and view SMS credits.</p>
        </div>
        <form method="GET" class="flex items-center space-x-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search tenants..." class="border rounded px-3 py-2 text-sm">
            <button type="submit" class="px-3 py-2 bg-gray-800 text-white rounded text-sm">Search</button>
        </form>
    </div>

    @if(session('status'))
        <div class="mb-4 bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white shadow rounded">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tenant</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Messaging</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">SMS Credits</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($tenants as $tenant)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $tenant->name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $tenant->slug }}</td>
                        <td class="px-4 py-2 text-sm">
                            <span class="px-2 py-1 rounded text-white {{ $tenant->status === 'active' ? 'bg-green-600' : 'bg-yellow-600' }}">{{ ucfirst($tenant->status) }}</span>
                        </td>
                        <td class="px-4 py-2 text-sm">
                            <span class="px-2 py-1 rounded text-white {{ $tenant->messaging_enabled ? 'bg-green-600' : 'bg-red-600' }}">{{ $tenant->messaging_enabled ? 'Enabled' : 'Disabled' }}</span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ number_format((int) $tenant->sms_credits) }}</td>
                        <td class="px-4 py-2 text-sm">
                            <form method="POST" action="{{ route('admin.messages.toggle', $tenant) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-3 py-1 rounded text-white {{ $tenant->messaging_enabled ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }}">
                                    {{ $tenant->messaging_enabled ? 'Disable' : 'Enable' }} Messaging
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">No tenants found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-4 py-3">{{ $tenants->withQueryString()->links() }}</div>
    </div>
    <div class="mt-4 text-xs text-gray-500">Note: Disabling messaging prevents non-admin users from sending SMS. Admins can still test sends.</div>
</div>
@endsection