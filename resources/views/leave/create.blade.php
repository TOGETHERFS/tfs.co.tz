@extends('layouts.app')
@section('title', __('messages.apply_leave'))
@section('page-title', __('messages.apply_leave'))

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('leave.my') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-semibold text-gray-900">{{ __('messages.apply_leave') }}</h1>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div class="bg-teal-50 border border-teal-200 rounded-xl p-4 text-center">
            <p class="text-xs font-semibold text-teal-600 uppercase tracking-wider">{{ __('messages.entitled_days') }}</p>
            <p class="text-3xl font-bold text-teal-700 mt-1">{{ $balance->entitled_days }}</p>
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

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="text-sm text-red-700 space-y-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('leave.store') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.leave_start_date') }} *</label>
                <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" required
                       min="{{ now()->toDateString() }}"
                       onchange="calcLeaveDays()"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.leave_end_date') }} *</label>
                <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" required
                       min="{{ now()->toDateString() }}"
                       onchange="calcLeaveDays()"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500">
            </div>
        </div>

        <div class="bg-teal-50 border border-teal-200 rounded-lg p-3 flex items-center justify-between">
            <span class="text-sm font-medium text-teal-700">{{ __('messages.number_of_days') }}</span>
            <span class="text-xl font-bold text-teal-700" id="days_count">0 {{ __('messages.days') }}</span>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.reason') }}</label>
            <textarea name="reason" rows="4"
                      class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500"
                      placeholder="{{ __('messages.leave_reason_placeholder') }}">{{ old('reason') }}</textarea>
        </div>

        <div style="margin-top:16px;">
            <button type="submit" style="display:block;width:100%;padding:12px;background:#0d9488;color:#fff;font-weight:600;font-size:14px;border:none;border-radius:8px;cursor:pointer;margin-bottom:8px;">
                {{ __('messages.submit_request') }}
            </button>
            <a href="{{ route('leave.my') }}" style="display:block;width:100%;padding:12px;background:#f3f4f6;color:#374151;font-weight:600;font-size:14px;border-radius:8px;text-align:center;text-decoration:none;">
                {{ __('messages.cancel') }}
            </a>
        </div>
    </form>
</div>

<script>
function calcLeaveDays() {
    var s = document.getElementById('start_date').value;
    var e = document.getElementById('end_date').value;
    if (s && e) {
        var diff = Math.round((new Date(e) - new Date(s)) / 86400000) + 1;
        document.getElementById('days_count').textContent = (diff > 0 ? diff : 0) + ' {{ __('messages.days') }}';
    }
}
</script>
@endsection
