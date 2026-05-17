@extends('layouts.admin')

@section('title', 'Tenant SMS Balances')
@section('page-title', 'Tenant SMS Balances')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Tenant SMS Balances</h1>
        <p class="mt-1 text-sm text-gray-500">View and manage SMS balances for all tenants</p>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-md bg-green-50 border border-green-200">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-md bg-red-50 border border-red-200">
            <p class="text-sm text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Balance List -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">All Tenants</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Purchased</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Used</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($balances as $balance)
                                <tr>
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-gray-900">{{ $balance->tenant->name ?? 'Unknown' }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ $balance->tenant_id }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold {{ $balance->balance > 0 ? 'text-green-600' : 'text-gray-500' }}">
                                        {{ number_format($balance->balance) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-500">{{ number_format($balance->total_purchased) }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-500">{{ number_format($balance->total_used) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick="openCreditModal({{ $balance->tenant_id }}, '{{ $balance->tenant->name ?? '' }}')" 
                                                class="text-green-600 hover:text-green-800 text-sm mr-2">Credit</button>
                                        <button onclick="openDebitModal({{ $balance->tenant_id }}, '{{ $balance->tenant->name ?? '' }}', {{ $balance->balance }})" 
                                                class="text-red-600 hover:text-red-800 text-sm">Debit</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                        No tenant balances found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($balances->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $balances->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="space-y-6">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Credit</h3>
                <form action="{{ route('admin.sms.credit-tenant') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Tenant</label>
                        <select name="tenant_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Select --</option>
                            @foreach($balances as $bal)
                                <option value="{{ $bal->tenant_id }}">{{ $bal->tenant->name ?? 'Tenant #' . $bal->tenant_id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SMS Amount</label>
                        <input type="number" name="amount" required min="1" placeholder="e.g., 100"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                        <textarea name="reason" required rows="2" placeholder="Reason for credit..."
                                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                        Credit SMS
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Credit Modal -->
<div id="creditModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <form action="{{ route('admin.sms.credit-tenant') }}" method="POST">
            @csrf
            <input type="hidden" name="tenant_id" id="creditTenantId">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Credit SMS to <span id="creditTenantName"></span></h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">SMS Amount</label>
                <input type="number" name="amount" required min="1" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                <textarea name="reason" required rows="2" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeCreditModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">Credit</button>
            </div>
        </form>
    </div>
</div>

<!-- Debit Modal -->
<div id="debitModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <form action="{{ route('admin.sms.debit-tenant') }}" method="POST">
            @csrf
            <input type="hidden" name="tenant_id" id="debitTenantId">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Debit SMS from <span id="debitTenantName"></span></h3>
            <p class="text-sm text-gray-500 mb-4">Current balance: <span id="debitCurrentBalance" class="font-semibold"></span> SMS</p>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">SMS Amount</label>
                <input type="number" name="amount" required min="1" id="debitAmount" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                <textarea name="reason" required rows="2" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDebitModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">Debit</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCreditModal(tenantId, tenantName) {
    document.getElementById('creditTenantId').value = tenantId;
    document.getElementById('creditTenantName').textContent = tenantName;
    document.getElementById('creditModal').classList.remove('hidden');
}

function closeCreditModal() {
    document.getElementById('creditModal').classList.add('hidden');
}

function openDebitModal(tenantId, tenantName, balance) {
    document.getElementById('debitTenantId').value = tenantId;
    document.getElementById('debitTenantName').textContent = tenantName;
    document.getElementById('debitCurrentBalance').textContent = balance.toLocaleString();
    document.getElementById('debitAmount').max = balance;
    document.getElementById('debitModal').classList.remove('hidden');
}

function closeDebitModal() {
    document.getElementById('debitModal').classList.add('hidden');
}
</script>
@endpush
@endsection
