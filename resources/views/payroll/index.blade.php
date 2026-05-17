@extends('layouts.app')
@section('title', __('messages.payroll'))
@section('page-title', __('messages.payroll'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.payroll') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.payroll_desc') }}</p>
        </div>
        <a href="{{ route('payroll.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('messages.add_salary') }}
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Month Filter --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <form method="GET" action="{{ route('payroll.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.month') }}</label>
                <input type="month" name="month" value="{{ $month }}"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition">
                {{ __('messages.apply_filter') }}
            </button>
            @if($month)
            <a href="{{ route('payroll.index') }}" class="text-sm text-gray-500 hover:text-gray-700 underline">{{ __('messages.show_all') }}</a>
            @endif
        </form>
    </div>

    {{-- Salary Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-800">{{ __('messages.salary_records') }}@if($month) — {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}@endif</h3>
            <span class="text-xs text-gray-500">{{ $salaries->count() }} {{ __('messages.records') }}</span>
        </div>
        @if($salaries->isEmpty())
        <div class="py-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-gray-500 text-sm">{{ __('messages.no_salary_records') }}</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.staff_name') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.position') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.basic_salary') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.allowances') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.deductions') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.net_salary') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.status') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($salaries as $salary)
                    <tr class="hover:bg-emerald-50/30 transition-colors">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-xs font-bold text-emerald-700 flex-shrink-0">
                                    {{ strtoupper(substr($salary->user->name ?? 'U', 0, 2)) }}
                                </div>
                                <span class="font-medium text-gray-900">{{ $salary->user->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-gray-600 text-xs">{{ $salary->user->position ?? '—' }}</td>
                        <td class="px-5 py-4 text-right font-medium text-gray-800">{{ number_format($salary->basic_salary, 0) }}</td>
                        <td class="px-5 py-4 text-right text-green-700">+{{ number_format($salary->allowances, 0) }}</td>
                        <td class="px-5 py-4 text-right text-red-700">-{{ number_format($salary->deductions, 0) }}</td>
                        <td class="px-5 py-4 text-right font-bold text-emerald-700">{{ number_format($salary->net_salary, 0) }}</td>
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $salary->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ __('messages.status_' . $salary->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('payroll.slip.download', $salary) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    {{ __('messages.download') }}
                                </a>
                                <a href="{{ route('payroll.edit', $salary) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg transition">
                                    {{ __('messages.edit') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Advance Requests Panel (Admin) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-800">{{ __('messages.salary_advance_requests') }}</h3>
            <a href="{{ route('payroll.advances.admin') }}"
               class="text-xs text-emerald-600 hover:text-emerald-800 font-medium">{{ __('messages.view_all') }} →</a>
        </div>
    </div>
</div>
@endsection
