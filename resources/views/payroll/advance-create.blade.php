@extends('layouts.app')
@section('title', __('messages.apply_salary_advance'))
@section('page-title', __('messages.apply_salary_advance'))

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('payroll.advances') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-semibold text-gray-900">{{ __('messages.apply_salary_advance') }}</h1>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="text-sm text-red-700 space-y-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-amber-700">{{ __('messages.advance_info_note') }}</p>
    </div>

    <form method="POST" action="{{ route('payroll.advance.store') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.advance_amount') }} (TZS) *</label>
            <input type="number" name="amount" value="{{ old('amount') }}" min="1" step="0.01" required
                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-amber-500 focus:border-amber-500"
                   placeholder="0.00">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.reason') }}</label>
            <textarea name="reason" rows="4"
                      class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-amber-500 focus:border-amber-500"
                      placeholder="{{ __('messages.advance_reason_placeholder') }}">{{ old('reason') }}</textarea>
        </div>
        <div style="margin-top:16px;">
            <button type="submit" style="display:block;width:100%;padding:14px;background:#f59e0b;color:#fff;font-weight:700;font-size:15px;border:none;border-radius:8px;cursor:pointer;margin-bottom:10px;">
                {{ __('messages.submit_request') }}
            </button>
            <a href="{{ route('payroll.advances') }}" style="display:block;width:100%;padding:14px;background:#f3f4f6;color:#374151;font-weight:600;font-size:14px;border-radius:8px;text-align:center;text-decoration:none;">
                {{ __('messages.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
