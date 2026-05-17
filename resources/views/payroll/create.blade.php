@extends('layouts.app')
@section('title', __('messages.add_salary'))
@section('page-title', __('messages.add_salary'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('payroll.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-semibold text-gray-900">{{ __('messages.add_salary') }}</h1>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="text-sm text-red-700 space-y-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('payroll.store') }}" class="space-y-6">
        @csrf

        {{-- Staff & Month --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider border-b pb-2">{{ __('messages.basic_details') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.staff_member') }} *</label>
                    <select name="user_id" required class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">— {{ __('messages.select_staff') }} —</option>
                        @foreach($staff as $s)
                        <option value="{{ $s->id }}" {{ old('user_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->name }} ({{ $s->position ?? $s->role }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.month') }} *</label>
                    <input type="month" name="month" value="{{ old('month', $month) }}" required
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.basic_salary') }} (TZS) *</label>
                    <input type="number" name="basic_salary" value="{{ old('basic_salary', 0) }}" min="0" step="0.01" required
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.payment_date') }}</label>
                    <input type="date" name="payment_date" value="{{ old('payment_date') }}"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.status') }}</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="draft">{{ __('messages.status_draft') }}</option>
                        <option value="paid">{{ __('messages.status_paid') }}</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Allowances --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            <div class="flex items-center justify-between border-b pb-2">
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ __('messages.allowances') }}</h2>
                <button type="button" onclick="addRow('allowances')"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-800">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('messages.add_row') }}
                </button>
            </div>
            <div id="allowances-container"></div>
            <p id="allowances-empty" class="text-xs text-gray-400 italic">{{ __('messages.no_allowances_added') }}</p>
        </div>

        {{-- Deductions --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            <div class="flex items-center justify-between border-b pb-2">
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ __('messages.deductions') }}</h2>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" onclick="addNamedRow('deductions','PAYE')" class="text-xs font-semibold px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-gray-600">+ PAYE</button>
                    <button type="button" onclick="addNamedRow('deductions','SDL')" class="text-xs font-semibold px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-gray-600">+ SDL</button>
                    <button type="button" onclick="addNamedRow('deductions','NSSF')" class="text-xs font-semibold px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-gray-600">+ NSSF</button>
                    <button type="button" onclick="addRow('deductions')" class="inline-flex items-center gap-1 text-xs font-semibold text-red-600 hover:text-red-800">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('messages.add_row') }}
                    </button>
                </div>
            </div>
            <div id="deductions-container"></div>
            <p id="deductions-empty" class="text-xs text-gray-400 italic">{{ __('messages.no_deductions_added') }}</p>
        </div>

        {{-- Net Salary Preview --}}
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">{{ __('messages.net_salary_preview') }}</p>
                <p class="text-xs text-emerald-600 mt-0.5">{{ __('messages.net_salary_formula') }}</p>
            </div>
            <p class="text-2xl font-bold text-emerald-700">TZS <span id="net-salary-preview">0</span></p>
        </div>

        <div style="margin-top:8px;">
            <button type="submit" style="display:block;width:100%;padding:14px;background:#059669;color:#fff;font-weight:700;font-size:15px;border:none;border-radius:8px;cursor:pointer;margin-bottom:10px;">
                {{ __('messages.save_salary') }}
            </button>
            <a href="{{ route('payroll.index') }}" style="display:block;width:100%;padding:14px;background:#f3f4f6;color:#374151;font-weight:600;font-size:14px;border-radius:8px;text-align:center;text-decoration:none;">
                {{ __('messages.cancel') }}
            </a>
        </div>
    </form>
</div>

<script>
var allowanceCount = 0, deductionCount = 0;

function addRow(type) { addNamedRow(type, ''); }

function addNamedRow(type, name) {
    var i = type === 'allowances' ? allowanceCount++ : deductionCount++;
    var prefix = type === 'allowances' ? 'allowance' : 'deduction';
    var container = document.getElementById(type + '-container');
    var empty = document.getElementById(type + '-empty');
    var row = document.createElement('div');
    row.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px;';
    row.innerHTML = '<input type="text" name="' + prefix + '_names[' + i + ']" value="' + name + '" placeholder="{{ __('messages.allowance_name') }}" style="flex:1;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;">'
        + '<input type="number" name="' + prefix + '_amounts[' + i + ']" value="0" min="0" step="0.01" style="width:120px;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;" oninput="updateNet()">'
        + '<button type="button" onclick="this.parentNode.remove();updateNet();updateEmpty(\'' + type + '\')" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:18px;">&#x2715;</button>';
    container.appendChild(row);
    if (empty) empty.style.display = 'none';
    updateNet();
}

function updateEmpty(type) {
    var container = document.getElementById(type + '-container');
    var empty = document.getElementById(type + '-empty');
    if (empty) empty.style.display = container.children.length === 0 ? 'block' : 'none';
}

function updateNet() {
    var basic = parseFloat(document.querySelector('[name=basic_salary]')?.value || 0);
    var allowances = 0, deductions = 0;
    document.querySelectorAll('[name^=allowance_amounts]').forEach(function(el){ allowances += parseFloat(el.value||0); });
    document.querySelectorAll('[name^=deduction_amounts]').forEach(function(el){ deductions += parseFloat(el.value||0); });
    var net = Math.max(0, basic + allowances - deductions);
    document.getElementById('net-salary-preview').textContent = new Intl.NumberFormat().format(net);
}

document.addEventListener('DOMContentLoaded', function() {
    var basicInput = document.querySelector('[name=basic_salary]');
    if (basicInput) basicInput.addEventListener('input', updateNet);
});
</script>
@endsection
