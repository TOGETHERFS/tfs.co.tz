@extends('layouts.admin')

@section('title', 'SMS Packages')
@section('page-title', 'SMS Packages')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">SMS Packages</h1>
            <p class="mt-1 text-sm text-gray-500">Define SMS packages for tenants to purchase</p>
        </div>
        <a href="{{ route('admin.sms.create-package') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Package
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-md bg-green-50 border border-green-200">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SMS Count</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Per SMS</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($packages as $package)
                        <tr>
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-900">{{ $package->name }}</p>
                                @if($package->description)
                                    <p class="text-sm text-gray-500">{{ Str::limit($package->description, 50) }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ number_format($package->sms_count) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $package->formatted_price }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ number_format($package->price_per_sms, 2) }} {{ $package->currency }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $package->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $package->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm space-x-2">
                                <a href="{{ route('admin.sms.edit-package', $package) }}" class="text-blue-600 hover:text-blue-800">Edit</a>
                                <form action="{{ route('admin.sms.toggle-package', $package) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="{{ $package->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                                        {{ $package->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                No packages defined yet. <a href="{{ route('admin.sms.create-package') }}" class="text-blue-600 hover:underline">Create your first package</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
