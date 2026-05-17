@extends('layouts.app')
@section('title', __('messages.my_leaves'))
@section('page-title', __('messages.my_leaves'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.my_leaves') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.my_leaves_desc') }}</p>
        </div>
        <a href="{{ route('leave.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('messages.apply_leave') }}
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Balance Summary --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-teal-50 border border-teal-200 rounded-xl p-4 text-center">
            <p class="text-xs font-semibold text-teal-600 uppercase tracking-wider">{{ __('messages.entitled_days') }}</p>
            <p class="text-3xl font-bold text-teal-700 mt-1">{{ $balance->entitled_days }}</p>
            <p class="text-xs text-teal-500 mt-0.5">{{ $year }}</p>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-center">
            <p class="text-xs font-semibold text-orange-600 uppercase tracking-wider">{{ __('messages.used_days') }}</p>
            <p class="text-3xl font-bold text-orange-700 mt-1">{{ $balance->used_days }}</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
            <p class="text-xs font-semibold text-green-600 uppercase tracking-wider">{{ __('messages.remaining_days') }}</p>
            <p class="text-3xl font-bold text-green-700 mt-1">{{ $balance->remaining_days }}</p>
        </div>
    </div>

    {{-- Leave History --}}
    @if($leaves->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 py-16 text-center">
        <svg class="mx-auto w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p class="text-gray-500 text-sm">{{ __('messages.no_leave_requests') }}</p>
        <a href="{{ route('leave.create') }}"
           class="mt-3 inline-flex items-center gap-1 text-sm text-teal-600 hover:text-teal-800 font-medium">
            {{ __('messages.apply_leave') }} →
        </a>
    </div>
    @else
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">{{ __('messages.leave_history') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.leave_start_date') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.leave_end_date') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.days') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.reason') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.status') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.review_note') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($leaves as $leave)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-700">{{ $leave->start_date->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $leave->end_date->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-center font-bold text-teal-700">{{ $leave->days }}</td>
                        <td class="px-5 py-3 text-gray-600 text-xs max-w-xs truncate">{{ $leave->reason ?? '—' }}</td>
                        <td class="px-5 py-3 text-center">
                            @php $colors=['pending'=>'bg-yellow-100 text-yellow-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700']; @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$leave->status] ?? '' }}">
                                {{ __('messages.status_' . $leave->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-500">{{ $leave->review_note ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
