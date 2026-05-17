@extends('layouts.app')

@section('title', __('messages.borrower_details'))
@section('page-title', __('messages.borrower_details'))

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $client->first_name }} {{ $client->last_name }}</h1>
<p class="mt-1 text-sm text-gray-500">{{ __('messages.borrower_profile_activity') }}</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('clients.index') }}" class="px-4 py-2 border rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">{{ __('messages.back_to_borrowers') }}</a>
            <a href="{{ route('clients.edit', $client) }}" class="px-4 py-2 rounded-md text-sm text-white bg-indigo-600 hover:bg-indigo-700">{{ __('messages.edit_borrower') }}</a>
            <form action="{{ route('clients.destroy', $client) }}" method="POST" onsubmit="return confirm('{{ __('messages.confirm_delete_borrower') }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 rounded-md text-sm text-white bg-red-600 hover:bg-red-700">{{ __('messages.delete') }}</button>
            </form>
        </div>
    </div>

    <!-- Client Overview -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="flex items-start space-x-4">
@if ($client->photo_path)
<img src="{{ asset('storage/'.$client->photo_path) }}" alt="Borrower Photo" class="h-24 w-24 rounded-full object-cover border" />
                @else
                    <div class="h-24 w-24 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 text-xl font-semibold">
                        {{ strtoupper(substr($client->first_name, 0, 1)) }}{{ strtoupper(substr($client->last_name, 0, 1)) }}
                    </div>
                @endif
                <div>
<div class="text-sm text-gray-500">{{ __('messages.borrower_id') }}</div>
                    <div class="text-base font-medium text-gray-900">#{{ $client->id }}</div>
                    <div class="mt-3 text-sm text-gray-500">{{ __('messages.loan_status') }}</div>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                 {{ $client->status === 'active' ? 'bg-green-100 text-green-800' : ($client->status === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                        {{ ucfirst($client->status) }}
                    </span>
                </div>
            </div>
            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.id_number') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $client->id_number }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.gender') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ ucfirst($client->gender) }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.date_of_birth') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ optional($client->date_of_birth)->format('d/m/Y') }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.phone_number') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $client->phone }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.email_address') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $client->email }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.address') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $client->address }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.branch') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $client->branch_name }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">{{ __('messages.loan_officer') }}</div>
                    <div class="text-base font-medium text-gray-900">{{ $client->loan_officer }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loans -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">{{ __('messages.loans') }}</h3>
<p class="mt-1 text-sm text-gray-500">{{ __('messages.borrower_loans_overview') }}</p>
        </div>
        <div class="p-6">
            @if($client->loans->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.loan_number') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.product') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.principal_amount') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.loan_status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.created_at') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($client->loans as $loan)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ $loan->id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ optional($loan->product)->name ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($loan->principal, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                     {{ in_array($loan->status, ['active','disbursed']) ? 'bg-green-100 text-green-800' : ($loan->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                            {{ ucfirst($loan->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ optional($loan->created_at)->format('Y-m-d') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8a9 9 0 100-18 9 9 0 000 18z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('messages.no_loans_found') }}</h3>
<p class="mt-1 text-sm text-gray-500">{{ __('messages.borrower_no_loans_yet') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection