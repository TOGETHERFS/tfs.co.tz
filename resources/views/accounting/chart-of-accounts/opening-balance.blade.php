@extends('layouts.user')

@section('title', 'Record Opening Balance / Initial Capital')

@section('content')
<div class="py-6">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="flex items-center gap-4 mb-6">
            <a href="{{ route('accounting.chart-of-accounts.index') }}"
               class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Record Opening Balance / Initial Capital</h1>
                <p class="text-sm text-gray-500 mt-1">Record the initial capital or cash available before operations started</p>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg">
            <p class="text-green-800 text-sm font-medium">{{ session('success') }}</p>
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
            <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Info box --}}
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-blue-700">
                    <p class="font-semibold mb-1">What this does</p>
                    <p>This creates a journal entry that:</p>
                    <ul class="list-disc list-inside mt-1 space-y-0.5">
                        <li><strong>Debits</strong> your Cash/Bank account (money comes in)</li>
                        <li><strong>Credits</strong> your Capital/Equity account (owner's contribution)</li>
                    </ul>
                    <p class="mt-2">This corrects the negative Cash at Bank balance caused by disbursements recorded without a starting capital entry.</p>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-base font-semibold text-gray-900" id="form-title">Opening Balance Entry</h2>
            </div>
            <form action="{{ route('accounting.chart-of-accounts.opening-balance.store') }}" method="POST" class="p-6 space-y-5">
                @csrf

                {{-- Entry Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Entry Type <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-start gap-3 p-3 border-2 rounded-lg cursor-pointer entry-type-card border-blue-500 bg-blue-50" id="card-opening_balance">
                            <input type="radio" name="balance_entry_type" value="opening_balance" class="mt-0.5" {{ old('balance_entry_type','opening_balance') == 'opening_balance' ? 'checked' : '' }} required>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Opening Balance</p>
                                <p class="text-xs text-gray-500 mt-0.5">Cash/assets already in the business before you started recording</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 p-3 border-2 rounded-lg cursor-pointer entry-type-card border-gray-200 bg-white" id="card-capital_injection">
                            <input type="radio" name="balance_entry_type" value="capital_injection" class="mt-0.5" {{ old('balance_entry_type') == 'capital_injection' ? 'checked' : '' }}>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Capital Injected</p>
                                <p class="text-xs text-gray-500 mt-0.5">New funds injected by owner/shareholders into the business</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Amount --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Amount (TZS) <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           name="amount"
                           step="0.01"
                           min="0.01"
                           value="{{ old('amount') }}"
                           placeholder="e.g. 5000000"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('amount') border-red-300 @enderror"
                           required>
                    @error('amount')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Enter the total initial capital / cash amount available</p>
                </div>

                {{-- Cash/Bank Account --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Cash / Bank Account (Debit) <span class="text-red-500">*</span>
                    </label>
                    <select name="cash_account_id"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('cash_account_id') border-red-300 @enderror"
                            required>
                        <option value="">-- Select cash or bank account --</option>
                        @foreach($cashAccounts as $account)
                        <option value="{{ $account->id }}" {{ old('cash_account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->account_code }} — {{ $account->account_name }}
                            @if($account->is_bank_account) (Bank) @else (Cash) @endif
                        </option>
                        @endforeach
                    </select>
                    @error('cash_account_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Usually <strong>1120 – Cash at Bank</strong> or <strong>1110 – Cash in Hand</strong></p>
                </div>

                {{-- Capital/Equity Account --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Capital / Equity Account (Credit) <span class="text-red-500">*</span>
                    </label>
                    <select name="equity_account_id"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('equity_account_id') border-red-300 @enderror"
                            required>
                        <option value="">-- Select capital or equity account --</option>
                        @foreach($capitalAccounts as $account)
                        <option value="{{ $account->id }}" {{ old('equity_account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->account_code }} — {{ $account->account_name }}
                            <span class="text-gray-400">({{ ucfirst($account->account_type) }})</span>
                        </option>
                        @endforeach
                    </select>
                    @error('equity_account_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Usually <strong>3100 – Paid-Up Capital</strong> or <strong>3110 – Share Capital</strong></p>
                </div>

                {{-- Date --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Entry Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date"
                           name="entry_date"
                           value="{{ old('entry_date', now()->toDateString()) }}"
                           max="{{ now()->toDateString() }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('entry_date') border-red-300 @enderror"
                           required>
                    @error('entry_date')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input type="text"
                           name="description"
                           id="description-input"
                           value="{{ old('description', 'Opening balance - initial funds on hand') }}"
                           placeholder="Opening balance - initial funds on hand"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                {{-- Preview --}}
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Journal Entry Preview</p>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500 uppercase">
                                <th class="text-left pb-2">Account</th>
                                <th class="text-right pb-2">Debit</th>
                                <th class="text-right pb-2">Credit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <td class="py-1.5 text-gray-700">Cash / Bank Account <span class="text-xs text-gray-400">(your selection)</span></td>
                                <td class="py-1.5 text-right text-green-700 font-medium">TZS <span id="preview-amount">—</span></td>
                                <td class="py-1.5 text-right text-gray-400">—</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 text-gray-700">Capital / Equity Account <span class="text-xs text-gray-400">(your selection)</span></td>
                                <td class="py-1.5 text-right text-gray-400">—</td>
                                <td class="py-1.5 text-right text-green-700 font-medium">TZS <span id="preview-amount-2">—</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('accounting.chart-of-accounts.index') }}"
                       class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 shadow-sm">
                        Record Opening Balance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const amountInput = document.querySelector('input[name="amount"]');
    const preview1 = document.getElementById('preview-amount');
    const preview2 = document.getElementById('preview-amount-2');
    const descInput = document.getElementById('description-input');
    const formTitle = document.getElementById('form-title');
    const radios = document.querySelectorAll('input[name="balance_entry_type"]');

    const descriptions = {
        'opening_balance': 'Opening balance - initial funds on hand',
        'capital_injection': 'Capital injected by owner / shareholder'
    };
    const titles = {
        'opening_balance': 'Opening Balance Entry',
        'capital_injection': 'Capital Injected Entry'
    };

    function updateType(val) {
        document.querySelectorAll('.entry-type-card').forEach(card => {
            card.classList.remove('border-blue-500','bg-blue-50');
            card.classList.add('border-gray-200','bg-white');
        });
        const active = document.getElementById('card-' + val);
        if (active) {
            active.classList.remove('border-gray-200','bg-white');
            active.classList.add('border-blue-500','bg-blue-50');
        }
        if (!descInput.dataset.userEdited) {
            descInput.value = descriptions[val] || '';
        }
        formTitle.textContent = titles[val] || 'Entry';
    }

    radios.forEach(r => r.addEventListener('change', () => updateType(r.value)));
    descInput.addEventListener('input', () => { descInput.dataset.userEdited = '1'; });

    // Init
    const checked = document.querySelector('input[name="balance_entry_type"]:checked');
    if (checked) updateType(checked.value);

    amountInput.addEventListener('input', function () {
        const val = parseFloat(this.value);
        if (!isNaN(val) && val > 0) {
            const formatted = new Intl.NumberFormat('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);
            preview1.textContent = formatted;
            preview2.textContent = formatted;
        } else {
            preview1.textContent = '—';
            preview2.textContent = '—';
        }
    });
</script>
@endpush
@endsection
