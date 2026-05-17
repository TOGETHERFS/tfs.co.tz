@extends('layouts.user')

@section('title', 'Pending Role Assignments')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Pending Role Assignments</h1>
                        <p class="mt-1 text-sm text-gray-600">
                            @if(auth()->user()->hasRole('super_admin') || auth()->user()->role === 'super_admin')
                                Review and approve role assignment requests
                            @else
                                Track your role assignment requests
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('user.roles.create') }}" class="inline-flex items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                New Assignment
                            </a>
                        @endif
                        <a href="{{ route('user.dashboard') }}" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-4 sm:px-6 lg:px-8 py-8">
        @if($pendingAssignments->count() > 0)
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    @foreach($pendingAssignments as $assignment)
                        <li class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <h3 class="text-sm font-medium text-gray-900">
                                                {{ $assignment->user->name }}
                                            </h3>
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Pending
                                            </span>
                                        </div>
                                        <div class="mt-1">
                                            <p class="text-sm text-gray-600">
                                                <span class="font-medium">Role:</span> {{ $assignment->role->name }}
                                                @if($assignment->role->category)
                                                    ({{ ucfirst($assignment->role->category) }})
                                                @endif
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                <span class="font-medium">Requested by:</span> {{ $assignment->requestedBy->name }}
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                <span class="font-medium">Date:</span> {{ $assignment->created_at->format('M d, Y \a\t g:i A') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                @if(auth()->user()->hasRole('super_admin') || auth()->user()->role === 'super_admin')
                                    <div class="flex items-center space-x-2">
                                        <button onclick="showApprovalModal({{ $assignment->id }}, '{{ $assignment->user->name }}', '{{ $assignment->role->name }}')" class="inline-flex items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Approve
                                        </button>
                                        <button onclick="showRejectionModal({{ $assignment->id }}, '{{ $assignment->user->name }}', '{{ $assignment->role->name }}')" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            Reject
                                        </button>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Reason -->
                            <div class="mt-4 pl-14">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <h4 class="text-sm font-medium text-gray-900 mb-1">Reason for Assignment:</h4>
                                    <p class="text-sm text-gray-700">{{ $assignment->reason }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Pagination -->
            @if($pendingAssignments->hasPages())
                <div class="mt-6">
                    {{ $pendingAssignments->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No pending assignments</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if(auth()->user()->hasRole('super_admin') || auth()->user()->role === 'super_admin')
                        There are no role assignment requests awaiting approval.
                    @else
                        You have no pending role assignment requests.
                    @endif
                </p>
                @if(auth()->user()->isAdmin())
                    <div class="mt-6">
                        <a href="{{ route('user.roles.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create New Assignment
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

@if(auth()->user()->hasRole('super_admin') || auth()->user()->role === 'super_admin')
    <!-- Approval Modal -->
    <div id="approvalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 text-center mt-4">Approve Role Assignment</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 text-center" id="approvalText"></p>
                </div>
                <form id="approvalForm" method="POST">
                    @csrf
                    <div class="mt-4">
                        <label for="approval_reason" class="block text-sm font-medium text-gray-700">Approval Note (Optional)</label>
                        <textarea name="approval_reason" id="approval_reason" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm" placeholder="Add any notes about this approval..."></textarea>
                    </div>
                    <div class="flex items-center justify-center space-x-4 mt-6">
                        <button type="button" onclick="closeApprovalModal()" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-green-700">
                            Approve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 text-center mt-4">Reject Role Assignment</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 text-center" id="rejectionText"></p>
                </div>
                <form id="rejectionForm" method="POST">
                    @csrf
                    <div class="mt-4">
                        <label for="rejection_reason" class="block text-sm font-medium text-gray-700">Reason for Rejection <span class="text-red-500">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm" placeholder="Explain why this assignment is being rejected..." required></textarea>
                    </div>
                    <div class="flex items-center justify-center space-x-4 mt-6">
                        <button type="button" onclick="closeRejectionModal()" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700">
                            Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function showApprovalModal(assignmentId, userName, roleName) {
        document.getElementById('approvalText').textContent = `Are you sure you want to approve the assignment of "${roleName}" role to ${userName}?`;
        document.getElementById('approvalForm').action = `/user/roles/${assignmentId}/approve`;
        document.getElementById('approvalModal').classList.remove('hidden');
    }

    function closeApprovalModal() {
        document.getElementById('approvalModal').classList.add('hidden');
        document.getElementById('approval_reason').value = '';
    }

    function showRejectionModal(assignmentId, userName, roleName) {
        document.getElementById('rejectionText').textContent = `Are you sure you want to reject the assignment of "${roleName}" role to ${userName}?`;
        document.getElementById('rejectionForm').action = `/user/roles/${assignmentId}/reject`;
        document.getElementById('rejectionModal').classList.remove('hidden');
    }

    function closeRejectionModal() {
        document.getElementById('rejectionModal').classList.add('hidden');
        document.getElementById('rejection_reason').value = '';
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const approvalModal = document.getElementById('approvalModal');
        const rejectionModal = document.getElementById('rejectionModal');
        
        if (event.target === approvalModal) {
            closeApprovalModal();
        }
        if (event.target === rejectionModal) {
            closeRejectionModal();
        }
    }
    </script>
@endif
@endsection