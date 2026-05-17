@extends('layouts.user')

@section('title', 'Record Expense')

@section('content')
<div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('accounting.expenses.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Expenses</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">Record Expense</h1>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form action="{{ route('accounting.expenses.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="expense_date" class="block text-sm font-medium text-gray-700">Expense Date *</label>
                        <input type="date" name="expense_date" id="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('expense_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Category *</label>
                        <div class="mt-1 flex gap-2">
                            <select name="category_id" id="category_id" required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" onclick="openAddCategoryModal()"
                                class="flex-shrink-0 px-3 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 whitespace-nowrap">
                                + Add
                            </button>
                        </div>
                        @error('category_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="account_id" class="block text-sm font-medium text-gray-700">Expense Account *</label>
                        <select name="account_id" id="account_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Account</option>
                            @foreach($expenseAccounts as $account)
                            <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->account_code }} - {{ $account->account_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('account_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="payment_account_id" class="block text-sm font-medium text-gray-700">Payment Account *</label>
                        <select name="payment_account_id" id="payment_account_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Account</option>
                            @foreach($paymentAccounts as $account)
                            <option value="{{ $account->id }}" {{ old('payment_account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->account_code }} - {{ $account->account_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('payment_account_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount *</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount" value="{{ old('amount') }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="payee" class="block text-sm font-medium text-gray-700">Payee / Vendor</label>
                        <input type="text" name="payee" id="payee" value="{{ old('payee') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
                        <select name="payment_method" id="payment_method"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Method</option>
                            @foreach($paymentMethods as $key => $label)
                            <option value="{{ $key }}" {{ old('payment_method') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="payment_reference" class="block text-sm font-medium text-gray-700">Payment Reference</label>
                        <input type="text" name="payment_reference" id="payment_reference" value="{{ old('payment_reference') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Check #, Transaction ID, etc.">
                    </div>

                    <div>
                        <label for="receipt_number" class="block text-sm font-medium text-gray-700">Receipt Number</label>
                        <input type="text" name="receipt_number" id="receipt_number" value="{{ old('receipt_number') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="branch_id" class="block text-sm font-medium text-gray-700">Branch</label>
                        <select name="branch_id" id="branch_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description *</label>
                        <textarea name="description" id="description" rows="3" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                        @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="attachment" class="block text-sm font-medium text-gray-700">Attachment (Receipt/Invoice)</label>
                        <input type="file" name="attachment" id="attachment" accept=".pdf,.jpg,.jpeg,.png"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="mt-1 text-xs text-gray-500">PDF, JPG, PNG up to 5MB</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('accounting.expenses.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Submit for Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Category Modal --}}
<div id="addCategoryModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" onclick="closeAddCategoryModal()"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6 z-10">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Add New Expense Category</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
                <input type="text" id="newCategoryName" placeholder="e.g. Staff Welfare"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    onkeydown="if(event.key==='Enter'){event.preventDefault();saveNewCategory();}">
                <p id="categoryError" class="mt-1 text-sm text-red-600 hidden"></p>
            </div>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" onclick="closeAddCategoryModal()"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="button" onclick="saveNewCategory()" id="saveCategoryBtn"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Save Category</button>
            </div>
        </div>
    </div>
</div>

<script>
function openAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.remove('hidden');
    document.getElementById('newCategoryName').value = '';
    document.getElementById('categoryError').classList.add('hidden');
    setTimeout(() => document.getElementById('newCategoryName').focus(), 100);
}

function closeAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.add('hidden');
}

function saveNewCategory() {
    const name = document.getElementById('newCategoryName').value.trim();
    const errorEl = document.getElementById('categoryError');
    const btn = document.getElementById('saveCategoryBtn');

    if (!name) {
        errorEl.textContent = 'Category name is required.';
        errorEl.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Saving...';
    errorEl.classList.add('hidden');

    fetch('{{ route("accounting.expenses.categories.quick-add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ name: name })
    })
    .then(res => res.json())
    .then(data => {
        if (data.id) {
            const select = document.getElementById('category_id');
            // Check if option already exists
            const existing = select.querySelector(`option[value="${data.id}"]`);
            if (!existing) {
                const option = new Option(data.name, data.id);
                select.appendChild(option);
            }
            select.value = data.id;
            closeAddCategoryModal();
        } else {
            errorEl.textContent = data.message || 'Failed to save category.';
            errorEl.classList.remove('hidden');
        }
    })
    .catch(() => {
        errorEl.textContent = 'An error occurred. Please try again.';
        errorEl.classList.remove('hidden');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Save Category';
    });
}
</script>
@endsection
