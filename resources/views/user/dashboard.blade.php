@extends('layouts.user')

@section('title', __('messages.dashboard'))

@section('content')
<div class="min-h-screen bg-gray-100">


    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2 mb-0.5">
                        <span class="inline-block w-1 h-5 bg-blue-600 rounded-full"></span>
                        <h1 class="text-xl font-bold text-gray-900">{{ __('messages.user_portal') }}</h1>
                    </div>
                    <p class="text-sm text-gray-500 ml-3">{{ __('messages.welcome_back') }}, <span class="font-medium text-gray-700">{{ auth()->user()->name }}</span></p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                        {{ ucfirst(auth()->user()->role) }}
                    </span>
                    <span class="text-xs text-gray-400 hidden sm:block">{{ now()->format('l, M d Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        
        <!-- Row 1: Key Statistics -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.portfolio_overview') }}</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <!-- Total Borrowers -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 bg-blue-50 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_clients']) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ __('messages.total_borrowers') }}</p>
                </div>
                <!-- Active Loans -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 bg-green-50 rounded-lg">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['active_loans']) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ __('messages.active_loans') }}</p>
                </div>
                <!-- Portfolio Value -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 bg-purple-50 rounded-lg">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-lg font-bold text-gray-900 leading-tight">TZS {{ number_format($stats['total_loan_amount'], 0) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ __('messages.portfolio_value') }}</p>
                </div>
                <!-- Repayment Rate -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 bg-amber-50 rounded-lg">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format(($portfolio_metrics['repayment_rate'] ?? 0), 1) }}%</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ __('messages.repayment_rate') }}</p>
                </div>
                <!-- Principal Disbursed -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 bg-emerald-50 rounded-lg">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-lg font-bold text-emerald-700 leading-tight">TZS {{ number_format($stats['total_principal_disbursed'] ?? 0, 0) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ __('messages.principal_disbursed') }}</p>
                </div>
                <!-- Total Repayable -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 bg-teal-50 rounded-lg">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-lg font-bold text-teal-700 leading-tight">TZS {{ number_format($stats['total_repayable'] ?? 0, 0) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ __('messages.total_repayable') }}</p>
                </div>
            </div>
        </div>

        <!-- Row 2: Secondary Stats -->
        <div class="grid grid-cols-3 md:grid-cols-6 gap-3 mb-6">
            <div class="bg-orange-50 border border-orange-100 rounded-xl p-3 text-center">
                <p class="text-2xl font-bold text-orange-600">{{ number_format($stats['pending_loans']) }}</p>
                <p class="text-xs text-orange-500 font-medium mt-0.5">{{ __('messages.pending') }}</p>
            </div>
            <div class="bg-red-50 border border-red-100 rounded-xl p-3 text-center">
                <p class="text-base font-bold text-red-600 leading-tight">TZS {{ number_format($stats['overdue_amount'] ?? 0, 0) }}</p>
                <p class="text-xs text-red-500 font-medium mt-0.5">{{ __('messages.overdue') }} ({{ number_format($stats['overdue_loans']) }})</p>
            </div>
            <div class="bg-teal-50 border border-teal-100 rounded-xl p-3 text-center">
                <p class="text-2xl font-bold text-teal-600">{{ number_format($stats['completed_loans']) }}</p>
                <p class="text-xs text-teal-500 font-medium mt-0.5">{{ __('messages.completed') }}</p>
            </div>
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-center">
                <p class="text-base font-bold text-blue-600 leading-tight">TZS {{ number_format(($portfolio_metrics['outstanding_balance'] ?? 0), 0) }}</p>
                <p class="text-xs text-blue-500 font-medium mt-0.5">{{ __('messages.outstanding') }}</p>
            </div>
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 text-center">
                <p class="text-2xl font-bold text-indigo-600">{{ number_format(($portfolio_metrics['portfolio_at_risk'] ?? 0), 1) }}%</p>
                <p class="text-xs text-indigo-500 font-medium mt-0.5">PAR</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 text-center">
                <p class="text-2xl font-bold text-gray-700">{{ $stats['active_loans'] + $stats['pending_loans'] }}</p>
                <p class="text-xs text-gray-500 font-medium mt-0.5">{{ __('messages.total_active') }}</p>
            </div>
        </div>



        <!-- Today's Repayments -->
        @if(($today_repayments ?? collect())->count() > 0)
        <div class="mb-6">
            <div class="bg-white border border-blue-200 rounded-xl">
                <div class="flex items-center justify-between px-4 py-3 border-b border-blue-100 bg-blue-50 rounded-t-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <h4 class="font-semibold text-blue-800">Today's Repayments &mdash; {{ now()->format('d M Y') }}</h4>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                        {{ ($today_repayments ?? collect())->count() }} due
                    </span>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach(($today_repayments ?? collect()) as $schedule)
                    <div class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50">
                        <div>
                            <span class="text-sm font-medium text-gray-900">
                                {{ $schedule->loan->client->first_name ?? '' }} {{ $schedule->loan->client->last_name ?? '' }}
                            </span>
                            <span class="ml-2 text-xs text-gray-400">{{ $schedule->loan->loan_number ?? '' }}</span>
                            <span class="ml-2 text-xs text-gray-400">Inst. {{ $schedule->installment_number }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($schedule->status === 'partial')
                            <span class="inline-flex px-2 py-0.5 text-xs rounded-full bg-orange-100 text-orange-700">Partial</span>
                            @endif
                            <span class="text-sm font-semibold text-blue-700">TZS {{ number_format($schedule->total_amount - $schedule->paid_amount, 0) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="px-4 py-2 bg-gray-50 rounded-b-lg flex justify-between items-center">
                    <span class="text-xs text-gray-500">Total due today</span>
                    <span class="text-sm font-bold text-blue-700">
                        TZS {{ number_format(($today_repayments ?? collect())->sum(fn($s) => $s->total_amount - $s->paid_amount), 0) }}
                    </span>
                </div>
            </div>
        </div>
        @endif

        <!-- Row 5: Alerts (if any) -->
        @if(($upcoming_repayments ?? collect())->count() > 0 || ($overdue_loans ?? collect())->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            @if(($upcoming_repayments ?? collect())->count() > 0)
            <div class="bg-amber-50 border border-amber-200 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-amber-200 flex items-center space-x-2">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <h4 class="text-sm font-semibold text-amber-800">{{ __('messages.upcoming_repayments') }}</h4>
                </div>
                <ul class="divide-y divide-amber-100">
                    @foreach(($upcoming_repayments ?? collect())->take(5) as $schedule)
                    <li class="flex justify-between items-center px-5 py-2.5 text-sm">
                        <div>
                            <span class="font-medium text-amber-800">{{ $schedule->loan->client->first_name ?? '' }} {{ $schedule->loan->client->last_name ?? '' }}</span>
                            <span class="ml-2 text-xs text-amber-500">{{ \Carbon\Carbon::parse($schedule->due_date)->format('d M') }}</span>
                        </div>
                        <span class="text-amber-700 font-semibold text-xs">TZS {{ number_format($schedule->total_amount - $schedule->paid_amount, 0) }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if(($overdue_loans ?? collect())->count() > 0)
            <div class="bg-red-50 border border-red-200 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-red-200 flex items-center space-x-2">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <h4 class="text-sm font-semibold text-red-800">{{ __('messages.overdue_loans') }}</h4>
                </div>
                <ul class="divide-y divide-red-100">
                    @foreach(($overdue_loans ?? collect())->take(5) as $loan)
                    <li class="flex justify-between items-center px-5 py-2.5 text-sm">
                        <span class="font-medium text-red-700">{{ $loan->client->first_name ?? '' }} {{ $loan->client->last_name ?? '' }}</span>
                        <span class="text-red-600 font-semibold text-xs">TZS {{ number_format($loan->total_overdue_amount ?? 0, 0) }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        @endif

        <!-- Online Staff Section -->
        @if(($online_staff ?? collect())->count() > 0)
        <div class="mb-6">
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-1 h-4 bg-green-500 rounded-full"></div>
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('messages.staff') }}</h3>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-100">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></span>
                        {{ $online_staff->count() }} {{ __('messages.active') }}
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th scope="col" class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.staff') }}</th>
                                <th scope="col" class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.email') }}</th>
                                <th scope="col" class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.roles') }}</th>
                                <th scope="col" class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($online_staff as $staff)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                                            {{ substr($staff['name'], 0, 1) }}
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $staff['name'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600">{{ $staff['email'] }}</div>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full
                                        @if($staff['role'] === 'admin') bg-blue-50 text-blue-700
                                        @else bg-gray-100 text-gray-700
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $staff['role'])) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="h-2 w-2 bg-green-400 rounded-full mr-2"></span>
                                        <span class="text-sm text-gray-500">{{ $staff['time_spent'] }}</span>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

@if($isTrialActive ?? false)
<script>
function trialCountdown(trialEndsAt) {
    return {
        timeLeft: { days: 0, hours: 0, minutes: 0 },
        init() {
            this.updateCountdown();
            setInterval(() => this.updateCountdown(), 60000);
        },
        updateCountdown() {
            const now = new Date().getTime();
            const end = new Date(trialEndsAt).getTime();
            const distance = end - now;
            if (distance > 0) {
                this.timeLeft.days = Math.floor(distance / (1000 * 60 * 60 * 24));
                this.timeLeft.hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                this.timeLeft.minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            }
        }
    }
}
</script>
@endif
@endsection
