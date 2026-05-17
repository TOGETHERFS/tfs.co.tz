@extends('layouts.app')
@section('title', __('messages.my_salary_slips'))
@section('page-title', __('messages.my_salary_slips'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.my_salary_slips') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.my_salary_slips_desc') }}</p>
        </div>
        <a href="{{ route('payroll.advance.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            {{ __('messages.apply_salary_advance') }}
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    @if($salaries->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 py-16 text-center">
        <svg class="mx-auto w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-gray-500 text-sm">{{ __('messages.no_salary_records') }}</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($salaries as $salary)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition">
            <div class="bg-emerald-600 px-5 py-4">
                <p class="text-xs font-semibold text-emerald-100 uppercase tracking-wider">{{ __('messages.salary_slip') }}</p>
                <p class="text-xl font-bold text-white mt-1">{{ \Carbon\Carbon::createFromFormat('Y-m', $salary->month)->format('F Y') }}</p>
            </div>
            <div class="p-5 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">{{ __('messages.basic_salary') }}</span>
                    <span class="font-medium text-gray-800">TZS {{ number_format($salary->basic_salary, 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">{{ __('messages.allowances') }}</span>
                    <span class="text-green-600">+ TZS {{ number_format($salary->allowances, 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">{{ __('messages.deductions') }}</span>
                    <span class="text-red-600">- TZS {{ number_format($salary->deductions, 0) }}</span>
                </div>
                <div class="flex justify-between text-sm pt-2 border-t border-gray-100 font-bold">
                    <span class="text-gray-700">{{ __('messages.net_salary') }}</span>
                    <span class="text-emerald-700 text-base">TZS {{ number_format($salary->net_salary, 0) }}</span>
                </div>
                @if($salary->payment_date)
                <p class="text-xs text-gray-400">{{ __('messages.payment_date') }}: {{ $salary->payment_date->format('d M Y') }}</p>
                @endif
                <div class="pt-2">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $salary->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ __('messages.status_' . $salary->status) }}
                    </span>
                </div>
            </div>
            <div class="px-5 pb-5">
                <a href="{{ route('payroll.slip.download', $salary) }}"
                   class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    {{ __('messages.download') }}
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
