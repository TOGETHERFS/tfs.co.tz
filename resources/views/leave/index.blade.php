@extends('layouts.app')
@section('title', __('messages.leave_requests'))
@section('page-title', __('messages.leave_requests'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.leave_requests') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.leave_requests_admin_desc') }}</p>
        </div>
        <a href="{{ route('leave.balances') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            {{ __('messages.leave_balances') }}
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Status Filter --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap gap-2">
            @foreach(['all','pending','approved','rejected'] as $s)
            <a href="{{ route('leave.index', ['status' => $s]) }}"
               class="px-4 py-2 text-sm font-semibold rounded-lg transition
                      {{ $status === $s ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ __('messages.status_' . $s) }}
                @if($s === 'pending')
                <span class="ml-1 text-xs {{ $status === $s ? 'bg-white/20 text-white' : 'bg-yellow-100 text-yellow-700' }} px-1.5 py-0.5 rounded-full">
                    {{ $leaves->where('status','pending')->count() }}
                </span>
                @endif
            </a>
            @endforeach
        </div>
    </div>

    @if($leaves->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 py-16 text-center">
        <svg class="mx-auto w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p class="text-gray-500 text-sm">{{ __('messages.no_leave_requests') }}</p>
    </div>
    @else
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.staff_name') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.leave_start_date') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.leave_end_date') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.days') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.reason') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.status') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($leaves as $leave)
                    <tr class="hover:bg-teal-50/20 transition-colors">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center text-xs font-bold text-teal-700 flex-shrink-0">
                                    {{ strtoupper(substr($leave->user->name ?? 'U', 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $leave->user->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ $leave->user->position ?? $leave->user->role }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-gray-700">{{ $leave->start_date->format('d M Y') }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $leave->end_date->format('d M Y') }}</td>
                        <td class="px-5 py-4 text-center font-bold text-teal-700">{{ $leave->days }}</td>
                        <td class="px-5 py-4 text-gray-600 text-xs max-w-xs truncate">{{ $leave->reason ?? '—' }}</td>
                        <td class="px-5 py-4 text-center">
                            @php $colors=['pending'=>'bg-yellow-100 text-yellow-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700']; @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$leave->status] ?? '' }}">
                                {{ __('messages.status_' . $leave->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            @if($leave->status === 'pending')
                            <div x-data="{ open: false }" class="relative flex justify-center">
                                <button @click="open = !open"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                                    {{ __('messages.review') }} ▾
                                </button>
                                <div x-show="open" @click.outside="open=false" x-transition
                                     class="absolute right-0 z-20 mt-8 w-72 bg-white rounded-xl shadow-lg border border-gray-200 p-4">
                                    <form method="POST" action="{{ route('leave.review', $leave) }}" class="space-y-3">
                                        @csrf @method('PATCH')
                                        <p class="text-xs font-semibold text-gray-600">
                                            {{ $leave->user->name }} — {{ $leave->days }} {{ __('messages.days') }}
                                            ({{ $leave->start_date->format('d M') }} – {{ $leave->end_date->format('d M Y') }})
                                        </p>
                                        <div>
                                            <textarea name="review_note" rows="2"
                                                      class="w-full rounded-lg border-gray-300 text-xs shadow-sm"
                                                      placeholder="{{ __('messages.optional_note') }}"></textarea>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="submit" name="action" value="approved"
                                                    class="flex-1 py-2 text-xs font-semibold bg-green-600 hover:bg-green-700 text-white rounded-lg">
                                                {{ __('messages.approve') }}
                                            </button>
                                            <button type="submit" name="action" value="rejected"
                                                    class="flex-1 py-2 text-xs font-semibold bg-red-600 hover:bg-red-700 text-white rounded-lg">
                                                {{ __('messages.reject') }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @else
                            <span class="text-xs text-gray-400 block text-center">{{ __('messages.reviewed') }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
