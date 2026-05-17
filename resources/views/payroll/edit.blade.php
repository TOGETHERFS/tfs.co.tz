@extends('layouts.app')
@section('title', __('messages.edit_salary'))
@section('page-title', __('messages.edit_salary'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6"
    x-data="{ allowances: {{ json_encode($salary->allowances_breakdown ?? []) }}, deductions: {{ json_encode($salary->deductions_breakdown ?? []) }}, addAllowance() { this.allowances.push({ name: '', amount: 0 }) }, addDeduction() { this.deductions.push({ name: '', amount: 0 }) }, addCommonDeduction(n) { this.deductions.push({ name: n, amount: 0 }) } }">

    <div class="flex items-center gap-3">
        <a href="{{ route('payroll.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-semibold text-gray-900">{{ __('messages.edit_salary') }} — {{ optional($salary->user)->name }}</h1>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="text-sm text-red-700 space-y-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('payroll.update', $salary) }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider border-b pb-2">{{ __('messages.basic_details') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.staff_member') }}</label>
                    <input type="text" value="{{ optional($salary->user)->name }}" disabled
                           class="w-full rounded-lg border-gray-200 bg-gray-50 text-sm text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.month') }}</label>
                    <input type="text" value="{{ \Carbon\Carbon::createFromFormat('Y-m', $salary->month)->format('F Y') }}" disabled
                           class="w-full rounded-lg border-gray-200 bg-gray-50 text-sm text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.basic_salary') }} (TZS) *</label>
                    <input type="number" name="basic_salary" value="{{ old('basic_salary', $salary->basic_salary) }}" min="0" step="0.01" required
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.payment_date') }}</label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', optional($salary->payment_date)->toDateString()) }}"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.status') }}</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="draft" {{ $salary->status === 'draft' ? 'selected' : '' }}>{{ __('messages.status_draft') }}</option>
                        <option value="paid" {{ $salary->status === 'paid' ? 'selected' : '' }}>{{ __('messages.status_paid') }}</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Allowances --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            <div class="flex items-center justify-between border-b pb-2">
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ __('messages.allowances') }}</h2>
                <button type="button" @click="addAllowance()"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-800">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('messages.add_row') }}
                </button>
            </div>
            <template x-for="(row, i) in allowances" :key="i">
                <div class="flex gap-3 items-center">
                    <input type="text" :name="'allowance_names[' + i + ']'" x-model="row.name"
                           class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm">
                    <input type="number" :name="'allowance_amounts[' + i + ']'" x-model="row.amount" min="0" step="0.01"
                           class="w-36 rounded-lg border-gray-300 text-sm shadow-sm">
                    <button type="button" @click="allowances.splice(i,1)" class="text-red-400 hover:text-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
        </div>

        {{-- Deductions --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            <div class="flex items-center justify-between border-b pb-2">
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ __('messages.deductions') }}</h2>
                <div class="flex items-center gap-2">
                    <button type="button" @click="addCommonDeduction('PAYE')" class="text-xs font-semibold px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-gray-600">+ PAYE</button>
                    <button type="button" @click="addCommonDeduction('SDL')" class="text-xs font-semibold px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-gray-600">+ SDL</button>
                    <button type="button" @click="addCommonDeduction('NSSF')" class="text-xs font-semibold px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-gray-600">+ NSSF</button>
                    <button type="button" @click="addDeduction()" class="inline-flex items-center gap-1 text-xs font-semibold text-red-600 hover:text-red-800">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('messages.add_row') }}
                    </button>
                </div>
            </div>
            <template x-for="(row, i) in deductions" :key="i">
                <div class="flex gap-3 items-center">
                    <input type="text" :name="'deduction_names[' + i + ']'" x-model="row.name"
                           class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm">
                    <input type="number" :name="'deduction_amounts[' + i + ']'" x-model="row.amount" min="0" step="0.01"
                           class="w-36 rounded-lg border-gray-300 text-sm shadow-sm">
                    <button type="button" @click="deductions.splice(i,1)" class="text-red-400 hover:text-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 space-y-2">
            <button type="submit" class="w-full py-3 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition">{{ __('messages.save_changes') }}</button>
            <a href="{{ route('payroll.index') }}" class="block w-full text-center py-3 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">{{ __('messages.cancel') }}</a>
        </div>
    </form>
</div>

@endsection
