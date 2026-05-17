@php
    $user = auth()->user();
    $isAdminContext = auth()->check() && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
    $layout = $isAdminContext ? 'layouts.admin' : 'layouts.user';
    $tenant = session('tenant') ?? (auth()->user()->tenant ?? null);
    // Get phone and format it correctly (must start with 255 or 0)
    $rawPhone = $tenant->phone ?? $user->phone ?? '';
    // Format phone to start with 255 if it has valid content
    if ($rawPhone && !preg_match('/^(255|0)/', $rawPhone)) {
        // Remove any leading + or country codes that aren't 255
        $rawPhone = preg_replace('/^\+?/', '', $rawPhone);
        if (strlen($rawPhone) == 9) {
            $rawPhone = '255' . $rawPhone;
        }
    }
    $tenantPhone = $rawPhone;
@endphp
@extends($layout)

@section('title', 'Manage Subscription')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="subscriptionPayment()">
    <h1 class="text-2xl font-semibold mb-4">Manage Subscription</h1>

    @if($subscription && $subscription->plan)
    <div class="p-4 bg-white shadow rounded mb-6">
        <div class="font-medium text-gray-700">Current Plan: {{ $subscription->plan->name }}</div>
        <div class="text-gray-500">TSH {{ number_format($subscription->plan->price) }} / {{ $subscription->plan->period }}</div>
        <div class="text-gray-500">Valid until: {{ optional($subscription->current_period_end)->format('Y-m-d') }}</div>
        <div class="text-gray-500">Staff: {{ $staff_count ?? 1 }} / {{ $subscription->plan->staff_limit ?? 'Unlimited' }}</div>
    </div>
    @endif

    <h2 class="text-xl font-semibold mb-2">Available Plans</h2>
    <div class="grid gap-4 md:grid-cols-3">
        @foreach($plans as $plan)
        <div class="p-4 bg-white shadow rounded">
            <div class="font-medium text-gray-700">{{ $plan->name }}</div>
            <div class="text-gray-500 mb-2">TSH {{ number_format($plan->price) }} / {{ $plan->period }}</div>
            <div class="text-gray-500 mb-1">Up to {{ $plan->staff_limit }} staff member{{ $plan->staff_limit > 1 ? 's' : '' }}</div>
            <div class="text-xs text-gray-400 mb-2">+TZS {{ number_format($plan->price_per_staff ?? 0) }}/month per extra staff</div>
            @can('manage-billing')
                <a href="{{ route('subscribe.show', $plan->code) }}"
                    class="inline-block px-3 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition text-center">
                    Switch to this plan
                </a>
            @else
                <p class="text-xs text-gray-500">Contact admin to switch plans.</p>
            @endcan
        </div>
        @endforeach
    </div>

    @can('manage-billing')
        <div class="mt-6">
            <form action="{{ route('billing.subscription.cancel') }}" method="POST" onsubmit="return confirm('Cancel subscription? Access remains until period end.')">
                @csrf
                <button class="px-3 py-2 bg-red-600 text-white rounded">Cancel Subscription</button>
            </form>
        </div>
    @endcan

    <!-- Payment Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Pay for <span x-text="selectedPlanName"></span>
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Amount: <span class="font-semibold text-gray-900">TSH <span x-text="selectedPlanPrice.toLocaleString()"></span></span>
                                </p>
                            </div>

                            <!-- Payment Status Messages -->
                            <div x-show="paymentStatus === 'pending'" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="animate-spin h-5 w-5 text-yellow-600 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-yellow-800 text-sm">Processing payment... Check your phone for USSD prompt.</span>
                                </div>
                            </div>

                            <div x-show="paymentStatus === 'success'" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 text-green-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-green-800 text-sm">Payment successful! Refreshing...</span>
                                </div>
                            </div>

                            <div x-show="paymentStatus === 'failed'" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 text-red-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span class="text-red-800 text-sm" x-text="errorMessage"></span>
                                </div>
                            </div>

                            <!-- Phone Number Input -->
                            <div class="mt-4" x-show="paymentStatus !== 'pending' && paymentStatus !== 'success'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number (M-Pesa, Tigo Pesa, Airtel Money)</label>
                                
                                @if($tenantPhone)
                                <div class="mb-3">
                                    <label class="inline-flex items-center">
                                        <input type="radio" x-model="phoneSource" value="existing" class="form-radio text-indigo-600">
                                        <span class="ml-2 text-sm text-gray-700">Use registered: <strong>{{ $tenantPhone }}</strong></span>
                                    </label>
                                </div>
                                <div class="mb-3">
                                    <label class="inline-flex items-center">
                                        <input type="radio" x-model="phoneSource" value="manual" class="form-radio text-indigo-600">
                                        <span class="ml-2 text-sm text-gray-700">Enter different number</span>
                                    </label>
                                </div>
                                @endif

                                <div x-show="phoneSource === 'manual' || !'{{ $tenantPhone }}'">
                                    <input type="tel" x-model="phoneNumber" 
                                        placeholder="e.g., 0712345678 or 255712345678"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <p class="mt-1 text-xs text-gray-500">Enter your mobile money number to receive payment prompt</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="processPayment()" :disabled="isProcessing || paymentStatus === 'success'"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        x-show="paymentStatus !== 'pending' && paymentStatus !== 'success'">
                        <span x-show="!isProcessing">Pay Now via Mobile Money</span>
                        <span x-show="isProcessing" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                    <button type="button" @click="closeModal()" :disabled="paymentStatus === 'pending'"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                        <span x-text="paymentStatus === 'success' ? 'Close' : 'Cancel'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function subscriptionPayment() {
    return {
        showModal: false,
        selectedPlanId: null,
        selectedPlanName: '',
        selectedPlanPrice: 0,
        phoneNumber: '',
        phoneSource: '{{ $tenantPhone ? "existing" : "manual" }}',
        existingPhone: '{{ $tenantPhone }}',
        isProcessing: false,
        paymentStatus: null, // null, 'pending', 'success', 'failed'
        errorMessage: '',
        orderId: null,
        pollInterval: null,

        openPaymentModal(planId, planName, planPrice) {
            this.selectedPlanId = planId;
            this.selectedPlanName = planName;
            this.selectedPlanPrice = planPrice;
            this.paymentStatus = null;
            this.errorMessage = '';
            this.orderId = null;
            this.showModal = true;
        },

        closeModal() {
            if (this.paymentStatus === 'pending') return;
            this.showModal = false;
            this.stopPolling();
            if (this.paymentStatus === 'success') {
                window.location.reload();
            }
        },

        getPhoneNumber() {
            if (this.phoneSource === 'existing' && this.existingPhone) {
                return this.existingPhone;
            }
            return this.phoneNumber;
        },

        async processPayment() {
            const phone = this.getPhoneNumber();
            if (!phone) {
                this.errorMessage = 'Please enter a phone number';
                this.paymentStatus = 'failed';
                return;
            }

            this.isProcessing = true;
            this.paymentStatus = null;
            this.errorMessage = '';

            try {
                // Step 1: Create order and trigger wallet payment
                const response = await fetch('{{ route("subscribe.wallet-payment") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        plan_id: this.selectedPlanId,
                        phone_number: phone
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.orderId = data.order_id;
                    this.paymentStatus = 'pending';
                    // Start polling for payment status
                    this.startPolling();
                } else {
                    this.paymentStatus = 'failed';
                    this.errorMessage = data.message || 'Failed to initiate payment. Please try again.';
                }
            } catch (error) {
                this.paymentStatus = 'failed';
                this.errorMessage = 'Network error. Please check your connection and try again.';
                console.error('Payment error:', error);
            } finally {
                this.isProcessing = false;
            }
        },

        startPolling() {
            // Poll every 5 seconds for up to 2 minutes
            let attempts = 0;
            const maxAttempts = 24;

            this.pollInterval = setInterval(async () => {
                attempts++;
                
                if (attempts > maxAttempts) {
                    this.stopPolling();
                    this.paymentStatus = 'failed';
                    this.errorMessage = 'Payment timeout. Please check your phone and try again.';
                    return;
                }

                try {
                    const response = await fetch(`{{ url('/subscribe/payment-status') }}/${this.orderId}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    const data = await response.json();

                    if (data.status === 'success' || data.status === 'COMPLETED') {
                        this.stopPolling();
                        this.paymentStatus = 'success';
                        setTimeout(() => window.location.reload(), 2000);
                    } else if (data.status === 'failed' || data.status === 'FAILED') {
                        this.stopPolling();
                        this.paymentStatus = 'failed';
                        this.errorMessage = data.message || 'Payment failed. Please try again.';
                    }
                } catch (error) {
                    console.error('Status check error:', error);
                }
            }, 5000);
        },

        stopPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        }
    }
}
</script>
@endpush

<style>
[x-cloak] { display: none !important; }
</style>
@endsection