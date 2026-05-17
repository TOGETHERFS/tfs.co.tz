@extends('layouts.public')

@section('title', 'Pricing Plans - PHIDLMS')
@section('description', 'Choose the perfect plan for your microfinance institution. Transparent pricing with no hidden fees. Scale as you grow.')

@section('content')
<!-- Hero Section -->
<section class="gradient-bg relative overflow-hidden">
    <div class="absolute inset-0 bg-black opacity-10"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
        <div class="text-center">
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                Simple, <span class="text-yellow-300">Transparent</span> Pricing
            </h1>
            <p class="text-xl md:text-2xl text-blue-100 mb-8 max-w-3xl mx-auto">
                Choose the plan that fits your institution's size. Scale up as you grow with no hidden fees.
            </p>
            <p class="text-lg text-blue-200 max-w-2xl mx-auto">
                <strong>Swahili:</strong> Bei rahisi na uwazi. Chagua mpango unaofaa ukubwa wa taasisi yako. Kuboresha kadri unavyokua bila ada za siri.
            </p>
        </div>
    </div>
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="white"/>
        </svg>
    </div>
</section>

<!-- Pricing Cards -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            @foreach($plans as $plan)
                @php $code = strtolower($plan->code); $popular = ($code === 'growth'); @endphp
                <div class="bg-white rounded-2xl shadow-lg border-2 {{ $popular ? 'border-blue-500 relative' : 'border-gray-200' }} p-8 card-hover">
                    @if($popular)
                        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                            <span class="bg-blue-500 text-white px-6 py-2 rounded-full text-sm font-semibold">Most Popular</span>
                        </div>
                    @endif

                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
                        <p class="text-gray-600 mb-4">{{ $plan->description ?? '' }}</p>
                        <div class="text-4xl font-bold text-gray-900 mb-2">
                            TZS {{ number_format((int) $plan->price) }}
                            <span class="text-lg font-normal text-gray-600">/month</span>
                        </div>
                        <p class="text-sm text-gray-500">
                            Up to {{ $plan->staff_limit ?? 1 }} staff member{{ ($plan->staff_limit ?? 1) > 1 ? 's' : '' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            +TZS {{ number_format($plan->price_per_staff ?? 0) }}/month per extra staff
                        </p>
                    </div>

                    <ul class="space-y-4 mb-8">
                        @php $features = is_array($plan->features) ? $plan->features : (empty($plan->features) ? [] : (array) json_decode($plan->features, true)); @endphp
                        @foreach($features as $feature)
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="text-center">
                        @guest
                            <div class="flex flex-col sm:flex-row gap-3">
                                <a href="{{ route('tenant.register') }}?plan={{ $code }}" 
                                   class="w-full inline-flex justify-center items-center px-6 py-3 rounded-lg text-lg font-semibold transition-colors {{ $popular ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                                    Create Account
                                </a>
                                <a href="{{ route('login') }}?redirect={{ urlencode(route('subscribe.show', ['plan' => $plan->code])) }}"
                                   class="w-full inline-flex justify-center items-center px-6 py-3 rounded-lg text-lg font-semibold transition-colors {{ $popular ? 'bg-blue-50 text-blue-700 hover:bg-blue-100' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                                    Sign In
                                </a>
                            </div>
                        @else
                            <a href="{{ route('checkout.show') }}?plan={{ $code }}"
                               class="w-full inline-flex justify-center items-center px-6 py-3 rounded-lg text-lg font-semibold transition-colors {{ $popular ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                                Choose Plan
                            </a>
                        @endguest
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection