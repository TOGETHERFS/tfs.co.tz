@php
    $user = auth()->user();
    $isAdminContext = auth()->check() && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
    $layout = $isAdminContext ? 'layouts.admin' : 'layouts.user';
@endphp
@extends($layout)

@section('title', 'Subscription Plans')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-semibold mb-4">Subscription Plans</h1>

    @if($subscription && $subscription->plan)
    <div class="p-4 bg-white shadow rounded mb-6">
        <div class="font-medium text-gray-700">Current Plan: {{ $subscription->plan->name }}</div>
        <div class="text-gray-500">TSH {{ number_format($subscription->plan->price) }} / {{ $subscription->plan->period }}</div>
        <div class="text-gray-500">Valid until: {{ optional($subscription->current_period_end)->format('Y-m-d') }}</div>
        <div class="text-gray-500">Staff: {{ $staff_count ?? 1 }} / {{ $subscription->plan->staff_limit ?? 'Unlimited' }}</div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($plans as $plan)
        <div class="p-4 bg-white shadow rounded">
            <div class="font-medium text-gray-700">{{ $plan->name }}</div>
            <div class="text-gray-500 mb-2">TSH {{ number_format($plan->price) }} / {{ $plan->period }}</div>
            <div class="text-gray-500 mb-1">Up to {{ $plan->staff_limit }} staff member{{ $plan->staff_limit > 1 ? 's' : '' }}</div>
            <div class="text-xs text-gray-400 mb-2">+TZS {{ number_format($plan->price_per_staff ?? 0) }}/month per extra staff</div>
            @can('manage-billing')
                <form action="{{ route('billing.subscription.upgrade') }}" method="POST">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                    <button class="px-3 py-2 bg-indigo-600 text-white rounded">Switch to this plan</button>
                </form>
            @else
                <p class="text-xs text-gray-500">Contact admin to switch plans.</p>
            @endcan
        </div>
        @endforeach
    </div>
</div>
@endsection