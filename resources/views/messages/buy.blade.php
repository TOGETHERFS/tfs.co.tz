@extends('layouts.app')

@section('title', __('messages.buy_sms_credits'))

@section('content')
<div class="max-w-5xl mx-auto p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.buy_sms_credits') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.buy_sms_desc') }}</p>
    </div>

    @if(session('status'))
        <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded mb-4">
            {{ session('error') }}
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

    <!-- Current Balance -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm">{{ __('messages.current_sms_balance') }}</p>
                <p class="text-3xl font-bold">{{ number_format($tenant->sms_credits ?? 0) }} SMS</p>
            </div>
            <div class="bg-white/20 rounded-full p-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- SMS Packages -->
    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('messages.choose_package') }}</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        @forelse($packages as $index => $package)
        <div class="relative bg-white rounded-lg shadow-sm border-2 {{ $index === 1 ? 'border-blue-500' : 'border-gray-200' }} p-6 hover:shadow-md transition">
            @if($index === 1)
                <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                    <span class="bg-blue-500 text-white text-xs font-semibold px-3 py-1 rounded-full">{{ __('messages.most_popular') }}</span>
                </div>
            @endif
            <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-900">{{ $package->name }}</h3>
                @if($package->description)
                    <p class="text-xs text-gray-500 mt-1">{{ $package->description }}</p>
                @endif
                <div class="mt-4">
                    <span class="text-4xl font-bold text-gray-900">{{ number_format($package->sms_count) }}</span>
                    <span class="text-gray-500"> SMS</span>
                </div>
                <p class="mt-2 text-2xl font-semibold text-blue-600">TZS {{ number_format($package->price) }}</p>
                <p class="text-sm text-gray-500">TZS {{ number_format($package->price_per_sms, 2) }}/SMS</p>
                <form method="POST" action="{{ route('messages.buy.checkout') }}" class="mt-6">
                    @csrf
                    <input type="hidden" name="quantity" value="{{ $package->sms_count }}">
                    <input type="hidden" name="amount" value="{{ $package->price }}">
                    <input type="hidden" name="package_name" value="{{ $package->name }}">
                    <button type="submit" class="w-full px-4 py-2 {{ $index === 1 ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-800 hover:bg-gray-900' }} text-white rounded-lg font-medium transition">
                        Buy Now
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-8 text-gray-500">
            No SMS packages available. Please contact support.
        </div>
        @endforelse
    </div>

    <!-- Custom Amount -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Custom Amount</h3>
        <form method="POST" action="{{ route('messages.buy.checkout') }}" class="flex flex-col md:flex-row gap-4 items-end">
            @csrf
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity (SMS)</label>
                <input type="number" name="quantity" min="50" step="10" value="{{ old('quantity', 200) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required
                       id="customQuantity" onchange="updateCustomPrice()">
                <p class="text-xs text-gray-500 mt-1">Minimum 50 SMS. Price: TZS {{ number_format($unitPrice) }}/SMS</p>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount</label>
                <div class="flex items-center">
                    <span class="text-gray-500 mr-2">TZS</span>
                    <input type="text" id="customAmount" name="amount" readonly
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-50 font-semibold" value="{{ number_format($unitPrice * 200) }}">
                </div>
            </div>
            <input type="hidden" name="package_name" value="Custom">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition whitespace-nowrap">
                Pay Now
            </button>
        </form>
    </div>

    <!-- Purchase History -->
    @if(isset($purchases) && $purchases->count() > 0)
    <div class="mt-8 bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Recent Purchases</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Package</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SMS</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($purchases as $purchase)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $purchase->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $purchase->notes ?? 'Standard' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ number_format($purchase->quantity) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">TZS {{ number_format($purchase->total_amount) }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                {{ $purchase->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $purchase->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $purchase->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ ucfirst($purchase->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<script>
const unitPrice = {{ $unitPrice }};
function updateCustomPrice() {
    const qty = parseInt(document.getElementById('customQuantity').value) || 0;
    const total = qty * unitPrice;
    document.getElementById('customAmount').value = total.toLocaleString();
}
</script>
@endsection