@extends('layouts.user')

@section('content')
<div class="max-w-2xl mx-auto py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Edit Branch</h1>
            <p class="text-sm text-gray-600">Update branch information</p>
        </div>
        <a href="{{ route('user.branches.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
            Back to Branches
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

    <form method="POST" action="{{ route('user.branches.update', $branch) }}" class="bg-white shadow rounded-lg p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Branch Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $branch->name) }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2" required>
            </div>

            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Branch Code</label>
                <input type="text" name="code" id="code" value="{{ old('code', $branch->code) }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="region" class="block text-sm font-medium text-gray-700 mb-1">Region <span class="text-red-500">*</span></label>
                @php
                    $regions = ['Arusha','Dar es Salaam','Dodoma','Geita','Iringa','Kagera','Katavi','Kigoma','Kilimanjaro','Lindi','Manyara','Mara','Mbeya','Morogoro','Mtwara','Mwanza','Njombe','Pemba North','Pemba South','Pwani','Rukwa','Ruvuma','Shinyanga','Simiyu','Singida','Songwe','Tabora','Tanga','Unguja North','Unguja South','Unguja Urban West'];
                @endphp
                <select name="region" id="region" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2" required>
                    <option value="">Select Region</option>
                    @foreach($regions as $region)
                        <option value="{{ $region }}" {{ old('region', $branch->region) == $region ? 'selected' : '' }}>{{ $region }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="district" class="block text-sm font-medium text-gray-700 mb-1">District</label>
                <input type="text" name="district" id="district" value="{{ old('district', $branch->district) }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2">
            </div>
        </div>

        <div>
            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
            <textarea name="address" id="address" rows="3" 
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2">{{ old('address', $branch->address) }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $branch->phone) }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email', $branch->email) }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2">
            </div>
        </div>

        <div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $branch->is_active) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                <span class="text-sm font-medium text-gray-700">Active Branch</span>
            </label>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('user.branches.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
                Update Branch
            </button>
        </div>
    </form>
</div>
@endsection
