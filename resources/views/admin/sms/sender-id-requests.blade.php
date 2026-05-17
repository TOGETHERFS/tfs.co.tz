@extends('layouts.admin')

@section('title', 'Sender ID Requests')
@section('page-title', 'Sender ID Requests')

@section('content')
<div class="space-y-6" x-data="{ syncing: false }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Sender ID Requests</h1>
            <p class="mt-1 text-sm text-gray-500">Review and approve tenant sender ID requests</p>
        </div>
        <button @click="syncSenderIds()" 
                :disabled="syncing"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50">
            <svg :class="{'animate-spin': syncing}" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span x-text="syncing ? 'Syncing...' : 'Sync from Beem Africa'"></span>
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-md bg-green-50 border border-green-200">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-md bg-red-50 border border-red-200">
            <p class="text-sm text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sender ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($requests as $request)
                        <tr x-data="{ expanded: false }">
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $request->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $request->tenant->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                <p class="font-mono font-semibold text-gray-900">{{ $request->sender_id }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $request->company_name }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $request->status_badge_class }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm space-x-2">
                                <button @click="expanded = !expanded" class="text-gray-600 hover:text-gray-800">
                                    <span x-text="expanded ? 'Hide' : 'View'"></span>
                                </button>
                                @if($request->status === 'pending')
                                    <form action="{{ route('admin.sms.approve-sender-id', $request) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-800">Approve</button>
                                    </form>
                                    <button type="button" onclick="rejectRequest({{ $request->id }})" class="text-red-600 hover:text-red-800">Reject</button>
                                @endif
                            </td>
                        </tr>
                        <tr x-show="expanded" x-cloak>
                            <td colspan="6" class="px-6 py-4 bg-gray-50">
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p class="font-medium text-gray-700">Purpose:</p>
                                        <p class="text-gray-600">{{ $request->purpose }}</p>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-700">Sample Message:</p>
                                        <p class="text-gray-600">{{ $request->sample_message }}</p>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-700">Requested By:</p>
                                        <p class="text-gray-600">{{ $request->requestedBy->name ?? 'N/A' }}</p>
                                    </div>
                                    @if($request->approvedBy)
                                    <div>
                                        <p class="font-medium text-gray-700">{{ $request->status === 'approved' ? 'Approved' : 'Rejected' }} By:</p>
                                        <p class="text-gray-600">{{ $request->approvedBy->name ?? 'N/A' }} on {{ $request->approved_at?->format('M d, Y H:i') }}</p>
                                    </div>
                                    @endif
                                    @if($request->admin_notes)
                                    <div class="col-span-2">
                                        <p class="font-medium text-gray-700">Admin Notes:</p>
                                        <p class="text-gray-600">{{ $request->admin_notes }}</p>
                                    </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                No sender ID requests yet
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($requests->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <form id="rejectForm" method="POST">
            @csrf
            <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Sender ID Request</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
                <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">Reject</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function rejectRequest(id) {
    document.getElementById('rejectForm').action = '/admin/sms/sender-id-requests/' + id + '/reject';
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}

async function syncSenderIds() {
    const el = document.querySelector('[x-data]');
    if (el && el.__x) {
        el.__x.$data.syncing = true;
    }
    
    try {
        const response = await fetch('{{ route("admin.sms.sync-sender-ids") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        const data = await response.json();
        if (data.success) {
            alert('Sender IDs synced successfully! Found ' + (data.sender_ids?.length || 0) + ' sender IDs from Beem Africa.');
            location.reload();
        } else {
            alert('Failed to sync sender IDs: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to sync sender IDs');
    }
    
    if (el && el.__x) {
        el.__x.$data.syncing = false;
    }
}
</script>
@endpush
@endsection
