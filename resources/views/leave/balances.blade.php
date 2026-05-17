@extends('layouts.app')
@section('title', __('messages.leave_balances'))
@section('page-title', __('messages.leave_balances'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.leave_balances') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.leave_balances_desc') }} — {{ $year }}</p>
        </div>
        <a href="{{ route('leave.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-lg transition">
            {{ __('messages.leave_requests') }}
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.staff_name') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.position') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.entitled_days') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.used_days') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.remaining_days') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.progress') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($balances as $bal)
                    @php
                        $pct = $bal->entitled_days > 0 ? min(100, round(($bal->used_days / $bal->entitled_days) * 100)) : 0;
                        $barColor = $pct >= 80 ? 'bg-red-500' : ($pct >= 50 ? 'bg-amber-500' : 'bg-teal-500');
                    @endphp
                    <tr class="hover:bg-teal-50/20 transition-colors">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center text-xs font-bold text-teal-700 flex-shrink-0">
                                    {{ strtoupper(substr(optional($bal->user)->name ?? 'U', 0, 2)) }}
                                </div>
                                <span class="font-medium text-gray-900">{{ optional($bal->user)->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-gray-600 text-xs">{{ optional($bal->user)->position ?? optional($bal->user)->role ?? '—' }}</td>
                        <td class="px-5 py-4 text-center font-semibold text-gray-700">{{ $bal->entitled_days }}</td>
                        <td class="px-5 py-4 text-center font-semibold text-orange-600">{{ $bal->used_days }}</td>
                        <td class="px-5 py-4 text-center font-bold {{ $bal->remaining_days <= 5 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $bal->remaining_days }}
                        </td>
                        <td class="px-5 py-4 min-w-[140px]">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-100 rounded-full h-2">
                                    <div class="{{ $barColor }} h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 w-8">{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
