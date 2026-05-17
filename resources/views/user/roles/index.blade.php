@extends('layouts.user')

@section('title', 'My Roles')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">My Roles</h1>
                        <p class="mt-1 text-sm text-gray-600">View your current roles and assignment history</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('user.roles.create') }}" class="inline-flex items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Assign Role
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
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Current Roles -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Current Roles</h3>
                    
                    @if($currentRoles->count() > 0)
                        <div class="space-y-3">
                            @foreach($currentRoles as $role)
                                <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-green-400 rounded-full mr-3"></div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $role->name }}</div>
                                            @if($role->category)
                                                <div class="text-xs text-gray-500">{{ ucfirst($role->category) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex items-center p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="w-3 h-3 bg-blue-400 rounded-full mr-3"></div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ ucfirst(auth()->user()->role) }}</div>
                                <div class="text-xs text-gray-500">Default Role</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Pending Assignments -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Pending Assignments</h3>
                    
                    @if($pendingAssignments->count() > 0)
                        <div class="space-y-3">
                            @foreach($pendingAssignments as $assignment)
                                <div class="flex items-center justify-between p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-yellow-400 rounded-full mr-3"></div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $assignment->role->name }}</div>
                                            <div class="text-xs text-gray-500">Requested by {{ $assignment->requestedBy->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $assignment->created_at->format('M d, Y') }}</div>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Pending
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-gray-500 p-4 bg-gray-50 rounded-lg text-center">
                            No pending role assignments.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Approved Assignments -->
        @if($approvedAssignments->count() > 0)
            <div class="mt-8 bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Approved Assignments</h3>
                    
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested By</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($approvedAssignments as $assignment)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $assignment->role->name }}</div>
                                            @if($assignment->role->category)
                                                <div class="text-sm text-gray-500">{{ ucfirst($assignment->role->category) }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $assignment->requestedBy->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $assignment->approvedBy->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $assignment->approved_at ? $assignment->approved_at->format('M d, Y') : $assignment->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Approved
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Information Card -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">About Roles</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Roles define your permissions and access levels within the system</li>
                            <li>You can have multiple roles assigned to your account</li>
                            <li>Role assignments require approval from a super administrator</li>
                            <li>Contact your administrator if you need additional roles</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection