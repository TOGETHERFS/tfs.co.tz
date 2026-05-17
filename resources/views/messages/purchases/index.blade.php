@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">SMS Purchase Requests</h1>

    @if(session('status'))
        <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white shadow rounded">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Requested By</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit Price (TZS)</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total (TZS)</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($purchases as $purchase)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ optional($purchase->user)->email ?? 'User #'.$purchase->user_id }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($purchase->quantity) }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($purchase->unit_price) }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($purchase->total_amount) }}</td>
                        <td class="px-4 py-2 text-sm">
                            <span class="px-2 py-1 rounded text-white {{ $purchase->status === 'pending' ? 'bg-yellow-500' : ($purchase->status === 'approved' ? 'bg-green-600' : 'bg-red-600') }}">
                                {{ ucfirst($purchase->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $purchase->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-2 text-sm">
                            @if($purchase->status === 'pending')
                                <div class="flex space-x-2">
                                    <form method="POST" action="{{ route('messages.purchases.approve', $purchase->id) }}">
                                        @csrf
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('messages.purchases.reject', $purchase->id) }}">
                                        @csrf
                                        <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">Reject</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-gray-400">No actions</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">No purchase requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">{{ $purchases->links() }}</div>
    </div>
</div>
@endsection