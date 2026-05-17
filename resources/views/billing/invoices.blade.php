@php
    $user = auth()->user();
    $isAdminContext = auth()->check() && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
    $layout = $isAdminContext ? 'layouts.admin' : 'layouts.user';
@endphp
@extends($layout)

@section('title', 'Invoices')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-semibold mb-4">Invoices</h1>

    <div class="bg-white shadow rounded">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Number</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Amount</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Due Date</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                    <tr>
                        <td class="px-4 py-2">{{ $invoice->number }}</td>
                        <td class="px-4 py-2">TSH {{ number_format($invoice->amount) }}</td>
                        <td class="px-4 py-2">{{ ucfirst($invoice->status) }}</td>
                        <td class="px-4 py-2">{{ optional($invoice->due_date)->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 flex items-center gap-2">
                            <a href="{{ route('billing.invoices.show', $invoice) }}" class="text-indigo-600">View</a>
                            @can('manage-billing')
                                <a href="{{ route('billing.invoices.edit', $invoice) }}" class="text-blue-600">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-gray-500">No invoices found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $invoices->links() }}</div>
    </div>
</div>
@endsection