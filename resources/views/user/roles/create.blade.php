@extends('layouts.user')

@section('title', 'Create Role')

@section('content')
<div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Create Role</h1>
            <p class="text-sm text-gray-600">Select from predefined microfinance roles or create a custom one</p>
        </div>
        <a href="{{ route('user.roles.manage') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
            Back to Roles
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

    <form method="POST" action="{{ route('user.roles.store') }}" class="space-y-6">
        @csrf

        @if(!empty($availablePredefinedRoles) && count($availablePredefinedRoles) > 0)
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Predefined Microfinance Roles</h3>
            <p class="text-sm text-gray-500 mb-4">Select a role commonly used in microfinance institutions:</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($availablePredefinedRoles as $slug => $role)
                    <label class="relative flex items-start p-4 border border-gray-200 rounded-lg hover:bg-blue-50 cursor-pointer">
                        <input type="radio" name="predefined_role" value="{{ $slug }}" 
                               class="h-4 w-4 text-blue-600 border-gray-300 mt-0.5">
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900">{{ $role['name'] }}</span>
                            <span class="block text-xs text-gray-500 mt-1">{{ $role['description'] }}</span>
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium mt-2
                                @if($role['category'] === 'management') bg-purple-100 text-purple-800
                                @elseif($role['category'] === 'operations') bg-blue-100 text-blue-800
                                @elseif($role['category'] === 'finance') bg-green-100 text-green-800
                                @elseif($role['category'] === 'compliance') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($role['category']) }}
                            </span>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>
        @else
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-sm text-green-700">All predefined microfinance roles have been created. Add custom roles below.</p>
        </div>
        @endif

        <div class="relative">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-300"></div></div>
            <div class="relative flex justify-center text-sm"><span class="px-2 bg-gray-50 text-gray-500">OR create custom role</span></div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Custom Role</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Role Name</label>
                    <input type="text" name="custom_name" value="{{ old('custom_name') }}" 
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" 
                           placeholder="e.g. Senior Loan Officer">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="custom">Custom</option>
                        <option value="management">Management</option>
                        <option value="operations">Operations</option>
                        <option value="finance">Finance</option>
                        <option value="compliance">Compliance</option>
                        <option value="support">Support</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="custom_description" rows="2" 
                          class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"
                          placeholder="Brief description of this role's responsibilities">{{ old('custom_description') }}</textarea>
            </div>
        </div>

        @isset($permissions)
        @if($permissions->count() > 0)
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Permissions (Optional)</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach($permissions as $permission)
                    <label class="flex items-center p-2 border border-gray-200 rounded hover:bg-gray-50">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-700">{{ $permission->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        @endif
        @endisset

        <div class="flex justify-end space-x-3">
            <a href="{{ route('user.roles.manage') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">Create Role</button>
        </div>
    </form>
</div>
@endsection