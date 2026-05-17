@extends('layouts.user')

@section('title', 'Create Journal Entry')

@section('content')
<div class="py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('accounting.journal-entries.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                ← Back to Journal Entries
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">Create Journal Entry</h1>
        </div>

        @if($errors->any())
        <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
            <ul class="list-disc list-inside text-red-700">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6">
            <form action="{{ route('accounting.journal-entries.store') }}" method="POST" x-data="journalEntry()">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="entry_date" class="block text-sm font-medium text-gray-700">Entry Date *</label>
                        <input type="date" name="entry_date" id="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description *</label>
                        <input type="text" name="description" id="description" value="{{ old('description') }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Enter journal entry description">
                    </div>
                </div>

                <!-- Journal Lines -->
                <div class="border rounded-lg p-4 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Entry Lines</h3>
                    
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                <th class="pb-2 pr-2">Account</th>
                                <th class="pb-2 pr-2">Description</th>
                                <th class="pb-2 pr-2 text-right w-32">Debit</th>
                                <th class="pb-2 pr-2 text-right w-32">Credit</th>
                                <th class="pb-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(line, index) in lines" :key="index">
                                <tr class="border-t">
                                    <td class="py-2 pr-2">
                                        <select :name="'lines[' + index + '][account_id]'" required x-model="line.account_id"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                            <option value="">Select Account</option>
                                            @foreach($accounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="text" :name="'lines[' + index + '][description]'" x-model="line.description"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                            placeholder="Line description">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" step="0.01" min="0" :name="'lines[' + index + '][debit_amount]'" 
                                            x-model.number="line.debit_amount" @input="updateCredit(index)"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm text-right">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" step="0.01" min="0" :name="'lines[' + index + '][credit_amount]'" 
                                            x-model.number="line.credit_amount" @input="updateDebit(index)"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm text-right">
                                    </td>
                                    <td class="py-2">
                                        <button type="button" @click="removeLine(index)" x-show="lines.length > 2"
                                            class="text-red-500 hover:text-red-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 font-medium">
                                <td colspan="2" class="py-2 text-right">Totals:</td>
                                <td class="py-2 pr-2 text-right" x-text="totalDebit.toFixed(2)"></td>
                                <td class="py-2 pr-2 text-right" x-text="totalCredit.toFixed(2)"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="py-2 text-right">Difference:</td>
                                <td colspan="2" class="py-2 text-center" :class="{'text-red-600': !isBalanced, 'text-green-600': isBalanced}">
                                    <span x-text="difference.toFixed(2)"></span>
                                    <span x-show="isBalanced" class="ml-2 text-green-600">✓ Balanced</span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                    <button type="button" @click="addLine()"
                        class="mt-4 inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Line
                    </button>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="{{ route('accounting.journal-entries.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" :disabled="!isBalanced"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Submit for Approval
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function journalEntry() {
    return {
        lines: [
            { account_id: '', description: '', debit_amount: 0, credit_amount: 0 },
            { account_id: '', description: '', debit_amount: 0, credit_amount: 0 }
        ],
        get totalDebit() {
            return this.lines.reduce((sum, line) => sum + (parseFloat(line.debit_amount) || 0), 0);
        },
        get totalCredit() {
            return this.lines.reduce((sum, line) => sum + (parseFloat(line.credit_amount) || 0), 0);
        },
        get difference() {
            return Math.abs(this.totalDebit - this.totalCredit);
        },
        get isBalanced() {
            return this.difference < 0.01 && this.totalDebit > 0;
        },
        addLine() {
            this.lines.push({ account_id: '', description: '', debit_amount: 0, credit_amount: 0 });
        },
        removeLine(index) {
            if (this.lines.length > 2) {
                this.lines.splice(index, 1);
            }
        },
        updateCredit(index) {
            if (this.lines[index].debit_amount > 0) {
                this.lines[index].credit_amount = 0;
            }
        },
        updateDebit(index) {
            if (this.lines[index].credit_amount > 0) {
                this.lines[index].debit_amount = 0;
            }
        }
    }
}
</script>
@endsection
