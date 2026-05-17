@extends('layouts.user')

@section('title', 'All Staff')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">All Staff</h1>
            <p class="mt-2 text-gray-600">View and manage your organization's staff members</p>
        </div>
        <a href="{{ route('user.staff.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Staff
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
            <div class="flex items-start justify-between">
                <ul class="list-disc list-inside flex-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                @if(session('failed_staff_id'))
                    <a href="{{ route('user.staff.edit', session('failed_staff_id')) }}" 
                       class="ml-4 inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-md whitespace-nowrap">
                        Deactivate User
                    </a>
                @endif
            </div>
        </div>
    @endif

    <!-- Staff List -->
    <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Current Staff ({{ $staffs->count() }})</h2>
            </div>
            <div class="p-6">
                @if($staffs->count() > 0)
                    <div class="space-y-4">
                        @foreach($staffs as $staff)
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-700">
                                                    {{ strtoupper(substr($staff->name, 0, 2)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $staff->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $staff->email }}</div>
                                            <div class="flex items-center mt-1 flex-wrap gap-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $staff->role }}
                                                </span>
                                                @if($staff->position)
                                                    <span class="text-xs text-gray-500">{{ $staff->position }}</span>
                                                @endif
                                                @if($staff->branch)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        📍 {{ $staff->branch->name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                @if($staff->role !== 'super admin' && $staff->role !== 'superadmin')
                                    <div class="flex-shrink-0 flex items-center gap-4">
                                        <a href="{{ route('user.staff.edit', $staff) }}" 
                                           class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                            Edit
                                        </a>
                                        <a href="{{ route('user.staff.edit', $staff) }}?action=deactivate" 
                                           class="text-yellow-600 hover:text-yellow-900 text-sm font-medium">
                                            Deactivate
                                        </a>
                                        @if($staff->id !== auth()->id())
                                            <form method="POST" action="{{ route('user.staff.destroy', $staff) }}" 
                                                  onsubmit="return confirm('Are you sure you want to delete this staff member?')" 
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No staff members</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating your first staff member.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection