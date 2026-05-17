@extends('layouts.user')

@section('content')
<div class="max-w-4xl mx-auto py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Create Staff Member</h1>
            <p class="text-sm text-gray-600">Add a new staff member with role and permissions</p>
        </div>
        <a href="{{ route('user.staff') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
            Back to Staff
        </a>
    </div>

    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('user.staff.store') }}" class="bg-white shadow rounded-lg p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2" required>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2" required>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                <input type="password" name="password" id="password" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2" 
                       minlength="8" required>
                <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-3">Roles <span class="text-red-500">*</span></label>
                <p class="text-xs text-gray-500 mb-3">Select one or more roles to assign to this staff member</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($roles as $role)
                        <label class="flex items-start p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" 
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mt-0.5"
                                   {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-700">{{ $role->name }}</span>
                                @if($role->description)
                                    <span class="block text-xs text-gray-500 mt-1">{{ $role->description }}</span>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="position" class="block text-sm font-medium text-gray-700">Position/Title</label>
                <input type="text" name="position" id="position" value="{{ old('position') }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2">
            </div>

            <div>
                <label for="branch_id" class="block text-sm font-medium text-gray-700">Branch</label>
                <select name="branch_id" id="branch_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2">
                    @if($branches->count() > 0)
                        <option value="">-- Select Branch (Optional) --</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    @else
                        <option value="">No branches available</option>
                    @endif
                </select>
            </div>
        </div>

        <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Permissions</h3>
            <p class="text-sm text-gray-500 mb-4">Select the permissions you want to assign to this staff member. These permissions determine what areas of the system they can access.</p>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($permissions as $permission)
                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                               {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-700">{{ $permission->name }}</span>
                            @if($permission->description)
                                <span class="block text-xs text-gray-500">{{ $permission->description }}</span>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('user.staff') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
                Create Staff Member
            </button>
        </div>
    </form>
</div>
@endsection
