@php
    $user = auth()->user();
    $isAdminContext = auth()->check() && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
    $layout = $isAdminContext ? 'layouts.admin' : 'layouts.user';
    $tenant = session('tenant') ?? (auth()->user()->tenant ?? null);
    $tenantPhone = $tenant->phone ?? '';
@endphp
@extends($layout)

@section('title', 'Confirm Plan')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8" x-data="subscribePayment()">
    <div class="bg-white shadow rounded p-6">
        <h1 class="text-2xl font-semibold mb-4">Confirm Plan</h1>

        <div class="mb-4">
            <div class="text-lg font-semibold">{{ $plan->name }}</div>
            <div class="text-xl font-bold">TZS {{ number_format((int) $plan->price) }} <span class="text-sm text-gray-500">/ month</span></div>
        </div>

        <!-- Subscription Configuration -->
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <!-- Staff Count -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Number of Staff</label>
                    <div class="flex items-center gap-2">
                        <input type="number" x-model="staffCount" min="1" max="100" 
                            class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center text-lg font-semibold focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @input="calculateTotal()">
                        <span class="text-sm text-gray-600">staff</span>
                    </div>
                </div>
                <!-- Branch Count -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Number of Branches</label>
                    <div class="flex items-center gap-2">
                        <input type="number" x-model="branchCount" min="1" max="50" 
                            class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center text-lg font-semibold focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @input="calculateTotal()">
                        <span class="text-sm text-gray-600">branch(es)</span>
                    </div>
                </div>
                <!-- Months -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Number of Months</label>
                    <div class="flex items-center gap-2">
                        <input type="number" x-model="months" min="1" max="12" 
                            class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center text-lg font-semibold focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @input="calculateTotal()">
                        <span class="text-sm text-gray-600">month(s)</span>
                    </div>
                </div>
            </div>
            
            <!-- Price Breakdown -->
            <div class="p-3 bg-white rounded-lg border border-blue-100">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Plan Price (includes {{ $plan->staff_limit ?? 1 }} staff, {{ $plan->branch_limit ?? 1 }} branch):</span>
                    <span class="font-medium">TZS {{ number_format((int) $plan->price) }}/month</span>
                </div>
                <div class="flex justify-between items-center mt-1" x-show="extraStaff > 0">
                    <span class="text-gray-600">Extra Staff (<span x-text="extraStaff"></span> × TZS {{ number_format($plan->price_per_staff ?? 0) }}):</span>
                    <span class="font-medium" x-text="'TZS ' + extraStaffCostFormatted + '/month'"></span>
                </div>
                <div class="flex justify-between items-center mt-1" x-show="extraBranches > 0">
                    <span class="text-gray-600">Extra Branches (<span x-text="extraBranches"></span> × TZS {{ number_format($plan->price_per_branch ?? 0) }}):</span>
                    <span class="font-medium" x-text="'TZS ' + extraBranchCostFormatted + '/month'"></span>
                </div>
                <div class="flex justify-between items-center mt-1">
                    <span class="text-gray-600">Monthly Total:</span>
                    <span class="font-medium" x-text="'TZS ' + monthlyTotalFormatted"></span>
                </div>
                <div class="flex justify-between items-center mt-1">
                    <span class="text-gray-600">Duration:</span>
                    <span class="font-medium" x-text="months + ' month' + (months > 1 ? 's' : '')"></span>
                </div>
                <div class="border-t border-gray-200 mt-2 pt-2 flex justify-between items-center">
                    <span class="text-lg font-semibold text-gray-800">Total Amount:</span>
                    <span class="text-xl font-bold text-green-600" x-text="'TZS ' + totalFormatted"></span>
                </div>
            </div>
        </div>

        <ul class="text-sm text-gray-700 space-y-2 mb-6">
            <li>
                @if(is_null($plan->branch_limit))
                    Unlimited Branches
                @else
                    {{ $plan->branch_limit }} Branch{{ $plan->branch_limit > 1 ? 'es' : '' }}
                @endif
                • 
                @if(is_null($plan->staff_limit))
                    Unlimited Staff
                @else
                    {{ $plan->staff_limit }} Staff
                @endif
            </li>
            @php $features = is_array($plan->features) ? $plan->features : (empty($plan->features) ? [] : (array) json_decode($plan->features, true)); @endphp
            @foreach($features as $f)
                <li>{{ $f }}</li>
            @endforeach
        </ul>

        <div class="space-y-4">
            <!-- Payment Status Messages -->
            <div x-show="paymentStatus === 'pending'" class="border-2 border-yellow-200 rounded-lg p-4 bg-yellow-50">
                <div class="flex items-center">
                    <svg class="animate-spin h-5 w-5 text-yellow-600 mr-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <div>
                        <span class="text-yellow-800 font-medium">Processing payment...</span>
                        <p class="text-yellow-700 text-sm">Check your phone for USSD prompt to authorize payment.</p>
                    </div>
                </div>
            </div>

            <div x-show="paymentStatus === 'success'" class="border-2 border-green-200 rounded-lg p-4 bg-green-50">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-green-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <div>
                        <span class="text-green-800 font-medium">Payment successful!</span>
                        <p class="text-green-700 text-sm">Your subscription has been activated. Redirecting...</p>
                    </div>
                </div>
            </div>

            <div x-show="paymentStatus === 'failed'" class="border-2 border-red-200 rounded-lg p-4 bg-red-50">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-red-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <div>
                        <span class="text-red-800 font-medium" x-text="errorMessage"></span>
                    </div>
                </div>
            </div>

            <!-- Selcom Checkout Redirect - Primary Option -->
            <div class="border-2 border-green-200 rounded-lg p-6 bg-green-50" x-show="paymentStatus !== 'pending' && paymentStatus !== 'success'">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center text-sm font-semibold mr-3">1</div>
                    <h3 class="text-lg font-semibold text-green-800">Pay via Selcom Checkout</h3>
                </div>
                
                <p class="text-green-700 mb-4">You will be redirected to Selcom's secure payment page to complete your payment for <strong>{{ $plan->name }}</strong> plan.</p>
                
                <form action="{{ route('subscribe.redirect', $plan->code) }}" method="POST">
                    @csrf
                    <input type="hidden" name="months" x-bind:value="months">
                    <input type="hidden" name="staff_count" x-bind:value="staffCount">
                    <input type="hidden" name="branch_count" x-bind:value="branchCount">
                    <button type="submit" class="w-full inline-flex items-center justify-center rounded-lg bg-green-600 text-white px-6 py-3 text-lg font-medium hover:bg-green-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span x-text="'Pay TZS ' + totalFormatted + ' - Go to Payment Page'"></span>
                    </button>
                </form>
                
                <p class="text-sm text-green-600 mt-3 text-center">Secure payment powered by Selcom</p>
            </div>

            <!-- Payment Instructions -->
            <div class="border-2 border-gray-200 rounded-lg p-6 bg-gray-50">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 bg-gray-400 text-white rounded-full flex items-center justify-center text-sm font-semibold mr-3">2</div>
                    <h3 class="text-lg font-semibold text-gray-700">How Mobile Payment Works</h3>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div class="text-sm text-gray-700">
                            <p class="font-medium mb-2">What happens when you click Pay:</p>
                            <ul class="space-y-1">
                                <li>1. You'll receive a USSD prompt on your phone</li>
                                <li>2. Enter your mobile money PIN to authorize</li>
                                <li>3. Payment is confirmed automatically</li>
                                <li>4. Your subscription activates immediately</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('error'))
                <div class="border-2 border-red-200 rounded-lg p-4 bg-red-50">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-red-700 font-medium">{{ session('error') }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function subscribePayment() {
    return {
        phoneNumber: '',
        phoneSource: '{{ $tenantPhone ? "existing" : "manual" }}',
        existingPhone: '{{ $tenantPhone }}',
        isProcessing: false,
        paymentStatus: null,
        errorMessage: '',
        orderId: null,
        pollInterval: null,
        months: 1,
        staffCount: {{ $plan->staff_limit ?? 1 }},
        branchCount: {{ $plan->branch_limit ?? 1 }},
        basePrice: {{ (int) $plan->price }},
        includedStaff: {{ $plan->staff_limit ?? 1 }},
        includedBranches: {{ $plan->branch_limit ?? 1 }},
        extraStaffPrice: {{ (int) ($plan->price_per_staff ?? 0) }},
        extraBranchPrice: {{ (int) ($plan->price_per_branch ?? 0) }},
        extraStaff: 0,
        extraBranches: 0,
        extraStaffCost: 0,
        extraBranchCost: 0,
        extraStaffCostFormatted: '0',
        extraBranchCostFormatted: '0',
        monthlyTotal: {{ (int) $plan->price }},
        monthlyTotalFormatted: '{{ number_format((int) $plan->price) }}',
        total: {{ (int) $plan->price }},
        totalFormatted: '{{ number_format((int) $plan->price) }}',

        calculateTotal() {
            const m = Math.max(1, Math.min(12, parseInt(this.months) || 1));
            const s = Math.max(1, Math.min(100, parseInt(this.staffCount) || 1));
            const b = Math.max(1, Math.min(50, parseInt(this.branchCount) || 1));
            this.months = m;
            this.staffCount = s;
            this.branchCount = b;
            
            // Calculate extra staff beyond plan's included staff
            this.extraStaff = Math.max(0, s - this.includedStaff);
            this.extraStaffCost = this.extraStaff * this.extraStaffPrice;
            this.extraStaffCostFormatted = this.extraStaffCost.toLocaleString();
            
            // Calculate extra branches beyond plan's included branches
            this.extraBranches = Math.max(0, b - this.includedBranches);
            this.extraBranchCost = this.extraBranches * this.extraBranchPrice;
            this.extraBranchCostFormatted = this.extraBranchCost.toLocaleString();
            
            // Monthly total = base price + extra staff cost + extra branch cost
            this.monthlyTotal = this.basePrice + this.extraStaffCost + this.extraBranchCost;
            this.monthlyTotalFormatted = this.monthlyTotal.toLocaleString();
            
            // Total = monthly total * months
            this.total = this.monthlyTotal * m;
            this.totalFormatted = this.total.toLocaleString();
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
                const response = await fetch('{{ route("subscribe.wallet-payment") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        plan_id: '{{ $plan->id }}',
                        phone_number: phone
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.orderId = data.order_id;
                    this.paymentStatus = 'pending';
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
                        setTimeout(() => window.location.href = '{{ route("billing.subscription") }}', 2000);
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