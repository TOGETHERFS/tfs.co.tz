@extends('layouts.app')

@section('title', 'Checkout - PHIDLMS')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Complete Your Subscription</h1>
        <p class="text-gray-600">Choose your plan and complete payment to get started</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Plan Selection -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Select Your Plan</h2>
            
            <div class="space-y-4">
                @foreach($plans as $plan)
                    <div class="border-2 rounded-lg p-4 cursor-pointer transition-all duration-200 hover:border-blue-300 {{ $selectedPlan['slug'] === $plan['slug'] ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}" 
                         onclick="selectPlan('{{ $plan['slug'] }}')">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <input type="radio" name="plan" value="{{ $plan['slug'] }}" 
                                           {{ $selectedPlan['slug'] === $plan['slug'] ? 'checked' : '' }}
                                           class="mr-3 text-blue-600">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $plan['name'] }}</h3>
                                    @if($plan['popular'])
                                        <span class="ml-2 bg-blue-500 text-white px-2 py-1 rounded-full text-xs">Popular</span>
                                    @endif
                                </div>
                                <p class="text-gray-600 text-sm mb-2">{{ $plan['description'] }}</p>
                                <p class="text-sm text-gray-500">Up to {{ $plan['max_staff'] }} staff member{{ $plan['max_staff'] > 1 ? 's' : '' }}</p>
                                <p class="text-xs text-gray-400">+TZS {{ number_format($plan['price_per_staff'] ?? 0) }}/month per extra staff</p>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gray-900">TZS {{ number_format($plan['price']) }}</div>
                                <div class="text-sm text-gray-600">per month</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Plan Comparison -->
            @if($currentPlan && $currentPlan['slug'] !== $selectedPlan['slug'])
                <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <h4 class="font-semibold text-yellow-800 mb-2">Plan Comparison</h4>
                    <div class="text-sm text-yellow-700">
                        <p><strong>Current:</strong> {{ $currentPlan['name'] }} ({{ $currentPlan['max_staff'] }} staff member{{ $currentPlan['max_staff'] > 1 ? 's' : '' }})</p>
                        <p><strong>New:</strong> {{ $selectedPlan['name'] }} ({{ $selectedPlan['max_staff'] }} staff member{{ $selectedPlan['max_staff'] > 1 ? 's' : '' }})</p>
                        @if($selectedPlan['max_staff'] > $currentPlan['max_staff'])
                            <p class="text-green-700 mt-2">✓ You'll be able to add {{ $selectedPlan['max_staff'] - $currentPlan['max_staff'] }} more staff member{{ ($selectedPlan['max_staff'] - $currentPlan['max_staff']) > 1 ? 's' : '' }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Payment Details -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Payment Details</h2>
            
            <!-- Order Summary -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-900 mb-3">Order Summary</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Plan:</span>
                        <span class="font-medium" id="summary-plan">{{ $selectedPlan['name'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Billing Period:</span>
                        <span class="font-medium">Monthly</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Staff Limit:</span>
                        <span class="font-medium" id="summary-staff">{{ $selectedPlan['max_staff'] }} staff</span>
                    </div>
                    <hr class="my-3">
                    <div class="flex justify-between text-lg font-semibold">
                        <span>Total:</span>
                        <span id="summary-total">TZS {{ number_format($selectedPlan['price']) }}</span>
                    </div>
                    <div class="text-sm text-gray-500">
                        Next billing: {{ $nextBilling->format('M j, Y') }}
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <form action="{{ route('checkout.process') }}" method="POST" id="checkout-form">
                @csrf
                <input type="hidden" name="plan_slug" value="{{ $selectedPlan['slug'] }}" id="selected-plan-input">
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                    <div class="space-y-3">
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="payment_method" value="selcom" checked class="mr-3 text-blue-600">
                            <div class="flex items-center">
                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiByeD0iOCIgZmlsbD0iIzAwN0JGRiIvPgo8cGF0aCBkPSJNMTIgMTZIMjhWMjRIMTJWMTZaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K" alt="Selcom" class="w-8 h-8 mr-3">
                                <div>
                                    <div class="font-medium">Selcom Payment</div>
                                    <div class="text-sm text-gray-500">Mobile Money, Bank Transfer, Card</div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="mb-6">
                    <label class="flex items-start">
                        <input type="checkbox" required class="mt-1 mr-3 text-blue-600">
                        <span class="text-sm text-gray-600">
                            I agree to the <a href="#" class="text-blue-600 hover:underline">Terms of Service</a> 
                            and <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a>. 
                            My subscription will automatically renew monthly unless cancelled.
                        </span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-4 px-6 rounded-lg text-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        id="checkout-btn">
                    <span class="flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Complete Payment
                    </span>
                </button>
            </form>

            <!-- Security Notice -->
            <div class="mt-4 text-center">
                <p class="text-sm text-gray-500">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Secure payment powered by Selcom
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function selectPlan(planSlug) {
    // Update radio button
    document.querySelector(`input[value="${planSlug}"]`).checked = true;
    
    // Update hidden input
    document.getElementById('selected-plan-input').value = planSlug;
    
    // Update visual selection
    document.querySelectorAll('[onclick^="selectPlan"]').forEach(el => {
        el.classList.remove('border-blue-500', 'bg-blue-50');
        el.classList.add('border-gray-200');
    });
    event.currentTarget.classList.remove('border-gray-200');
    event.currentTarget.classList.add('border-blue-500', 'bg-blue-50');
    
    // Update summary (you would need to pass plan data to JavaScript or make an AJAX call)
    updateSummary(planSlug);
}

function updateSummary(planSlug) {
    // This would typically make an AJAX call to get plan details
    // For now, we'll use the data already available on the page
    const plans = @json($plans);
    const selectedPlan = plans.find(plan => plan.slug === planSlug);
    
    if (selectedPlan) {
        document.getElementById('summary-plan').textContent = selectedPlan.name;
        document.getElementById('summary-staff').textContent = selectedPlan.max_staff + ' staff';
        document.getElementById('summary-total').textContent = 'TZS ' + selectedPlan.price.toLocaleString();
    }
}

// Form submission handling
document.getElementById('checkout-form').addEventListener('submit', function(e) {
    const btn = document.getElementById('checkout-btn');
    btn.disabled = true;
    btn.innerHTML = `
        <span class="flex items-center justify-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processing...
        </span>
    `;
});
</script>
@endsection