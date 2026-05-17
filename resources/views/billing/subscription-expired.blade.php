@php
    $user = auth()->user();
    $isAdminContext = auth()->check() && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
    $layout = $isAdminContext ? 'layouts.admin' : 'layouts.user';
@endphp
@extends($layout)

@section('title', 'Trial Expired - Choose a Plan')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Trial Expired Notice -->
    <div class="mb-8 rounded-md bg-red-50 p-6 border border-red-200">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <div class="text-sm text-red-700">
                    <p class="font-semibold text-red-800 mb-1">Kifurushi chako cha majaribio kimeisha. Tafadhali lipia kuendelea kutumia huduma zetu. &mdash; <em>Your subscription has expired. Please pay to continue using our services.</em></p>
                    <p><a href="#plans" class="font-bold underline text-red-800 hover:text-red-900">Lipia sasa / Renew Now &rarr;</a></p>
                </div>
            </div>
        </div>
    </div>

    <h1 class="text-3xl font-bold text-gray-900 mb-2">Choose Your Plan</h1>
    <p class="text-gray-600 mb-8">Select the plan that best fits your business needs and continue enjoying our services.</p>

    <!-- Plans Grid -->
    <div id="plans" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        @foreach($plans->where('price', '>', 0) as $plan)
        <div class="relative bg-white rounded-lg shadow-lg border border-gray-200 hover:shadow-xl transition-shadow duration-300">
            @if($plan->code === 'growth')
                <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-medium">Most Popular</span>
                </div>
            @endif
            
            <div class="p-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $plan->name }}</h3>
                
                <div class="mb-4">
                    <span class="text-3xl font-bold text-gray-900">TSH {{ number_format($plan->price) }}</span>
                    <span class="text-gray-600">/ {{ $plan->period }}</span>
                </div>

                @if($plan->features)
                    @php
                        $features = is_string($plan->features) ? json_decode($plan->features, true) : $plan->features;
                    @endphp
                    <ul class="space-y-2 mb-6">
                        @if(is_array($features))
                            @foreach($features as $feature)
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="h-4 w-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        @endif
                    </ul>
                @endif

                <div class="mb-4 text-sm text-gray-600">
                    <div class="flex justify-between">
                        <span>Branch Limit:</span>
                        <span class="font-medium">
                            @if($plan->branch_limit == 0)
                                Unlimited
                            @else
                                {{ $plan->branch_limit }}
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Staff Limit:</span>
                        <span class="font-medium">
                            @if($plan->staff_limit == 0)
                                Unlimited
                            @else
                                {{ $plan->staff_limit }}
                            @endif
                        </span>
                    </div>
                </div>

                <a href="{{ route('subscribe.show', $plan->code) }}" 
                   class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 text-center block">
                    Select {{ $plan->name }}
                </a>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Additional Information -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-3">Why Choose Our Platform?</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
            <div class="flex items-start">
                <svg class="h-5 w-5 text-blue-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Secure and reliable loan management system</span>
            </div>
            <div class="flex items-start">
                <svg class="h-5 w-5 text-blue-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>24/7 customer support</span>
            </div>
            <div class="flex items-start">
                <svg class="h-5 w-5 text-blue-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Regular updates and new features</span>
            </div>
            <div class="flex items-start">
                <svg class="h-5 w-5 text-blue-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Easy integration with Selcom payment gateway</span>
            </div>
        </div>
    </div>
</div>
@endsection