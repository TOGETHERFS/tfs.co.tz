@extends('layouts.app')

@section('title', 'Group: ' . $group->name)
@section('page-title', 'Group: ' . $group->name)

@section('content')
<div class="space-y-6">
    <!-- Group details -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">{{ $group->name }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $group->description }}</p>
                </div>
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $group->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($group->status) }}
                </span>
            </div>
        </div>

        <!-- Details -->
        <div class="p-6 border-t border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.branch') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $group->branch_name }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.loan_officer') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $group->loan_officer }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.meeting_area') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $group->meeting_area }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.bank_account') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $group->bank_account ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.region') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $group->region }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.ward') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $group->ward }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.village') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $group->village }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.phone_number') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $group->phone }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.box_number') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $group->box_number ?? '—' }}</div>
                </div>
            </div>
        </div>

        <!-- Members -->
        <div class="p-6">
            <h4 class="text-md font-semibold text-gray-900 mb-4">{{ __('messages.members') }} ({{ $group->clients->count() }})</h4>
            @if($group->clients->isEmpty())
                <p class="text-sm text-gray-500">{{ __('messages.no_members_yet') }}</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($group->clients as $client)
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-md bg-gray-50">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $client->first_name }} {{ $client->last_name }}</div>
                                <div class="text-xs text-gray-500">{{ $client->phone }} • {{ $client->email }}</div>
                            </div>
                            <a href="{{ route('clients.show', $client) }}" class="text-primary-600 hover:text-primary-900 text-sm">{{ __('messages.view') }}</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Back link -->
    <div class="flex justify-end">
        <a href="{{ route('groups.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
            &larr; {{ __('messages.back') }}
        </a>
    </div>
</div>
@endsection