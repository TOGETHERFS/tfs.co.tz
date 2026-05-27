@extends('layouts.user')

@section('title', __('messages.dashboard'))

@section('content')
<div class="min-h-screen bg-gray-100">


    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.user_portal') }}</h1>
                    <p class="text-sm text-gray-600">{{ __('messages.welcome_back') }}, {{ auth()->user()->name }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                        {{ ucfirst(auth()->user()->role) }}
                    </span>
                    <span class="text-sm text-gray-500">{{ now()->format('M d, Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        
        <!-- Row 1: Key Statistics -->
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('messages.portfolio_overview') }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <!-- Total Borrowers -->
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">{{ __('messages.total_borrowers') }}</p>
                            <p class="text-xl font-bold text-gray-900">{{ number_format($stats['total_clients']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Active Loans -->
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">{{ __('messages.active_loans') }}</p>
                            <p class="text-xl font-bold text-gray-900">{{ number_format($stats['active_loans']) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Portfolio Value -->
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">{{ __('messages.portfolio_value') }}</p>
                            <p class="text-xl font-bold text-gray-900">TZS {{ number_format($stats['total_loan_amount'], 0) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Repayment Rate -->
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">{{ __('messages.repayment_rate') }}</p>
                            <p class="text-xl font-bold text-gray-900">{{ number_format(($portfolio_metrics['repayment_rate'] ?? 0), 1) }}%</p>
                        </div>
                    </div>
                </div>
                <!-- Principal Disbursed -->
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-emerald-100 rounded-lg">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">{{ __('messages.principal_disbursed') }}</p>
                            <p class="text-xl font-bold text-emerald-700">TZS {{ number_format($stats['total_principal_disbursed'] ?? 0, 0) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Total Repayable -->
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-teal-100 rounded-lg">
                            <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">{{ __('messages.total_repayable') }}</p>
                            <p class="text-xl font-bold text-teal-700">TZS {{ number_format($stats['total_repayable'] ?? 0, 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Secondary Stats -->
        <div class="grid grid-cols-3 md:grid-cols-6 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-3 text-center">
                <p class="text-2xl font-bold text-orange-600">{{ number_format($stats['pending_loans']) }}</p>
                <p class="text-xs text-gray-500">{{ __('messages.pending') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-3 text-center">
                <p class="text-lg font-bold text-red-600">TZS {{ number_format($stats['overdue_amount'] ?? 0, 0) }}</p>
                <p class="text-xs text-gray-500">{{ __('messages.overdue') }} ({{ number_format($stats['overdue_loans']) }} loans)</p>
            </div>
            <div class="bg-white rounded-lg shadow p-3 text-center">
                <p class="text-2xl font-bold text-teal-600">{{ number_format($stats['completed_loans']) }}</p>
                <p class="text-xs text-gray-500">{{ __('messages.completed') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-3 text-center">
                <p class="text-2xl font-bold text-blue-600">TZS {{ number_format(($portfolio_metrics['outstanding_balance'] ?? 0), 0) }}</p>
                <p class="text-xs text-gray-500">{{ __('messages.outstanding') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-3 text-center">
                <p class="text-2xl font-bold text-indigo-600">{{ number_format(($portfolio_metrics['portfolio_at_risk'] ?? 0), 1) }}%</p>
                <p class="text-xs text-gray-500">PAR</p>
            </div>
            <div class="bg-white rounded-lg shadow p-3 text-center">
                <p class="text-2xl font-bold text-gray-600">{{ $stats['active_loans'] + $stats['pending_loans'] }}</p>
                <p class="text-xs text-gray-500">{{ __('messages.total_active') }}</p>
            </div>
        </div>

        <!-- Row 3: Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <!-- Column 1: Your Role -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h3 class="font-semibold text-gray-900">{{ __('messages.your_role') }}</h3>
                </div>
                <div class="p-4 space-y-3">
                    @if(auth()->user()->roles && auth()->user()->roles->count() > 0)
                        @foreach(auth()->user()->roles->take(3) as $role)
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                <span class="text-sm font-medium">{{ $role->name }}</span>
                            </div>
                            <span class="text-xs text-green-600">{{ __('messages.active') }}</span>
                        </div>
                        @endforeach
                    @else
                        <div class="flex items-center justify-between p-2 bg-blue-50 rounded">
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                                <span class="text-sm font-medium">{{ ucfirst(auth()->user()->role) }}</span>
                            </div>
                            <span class="text-xs text-blue-600">{{ __('messages.active') }}</span>
                        </div>
                    @endif
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('user.roles.manage') }}" class="block w-full text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
                        {{ __('messages.manage_roles') }}
                    </a>
                    @endif
                </div>
            </div>

            <!-- Column 3: Quick Actions -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h3 class="font-semibold text-gray-900">{{ __('messages.quick_actions') }}</h3>
                </div>
                <div class="p-4 space-y-2">
                    <a href="{{ route('clients.create') }}" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100">
                        <span class="w-8 h-8 bg-blue-500 text-white rounded flex items-center justify-center mr-3">+</span>
                        <span class="text-sm font-medium text-blue-700">{{ __('messages.add_borrower') }}</span>
                    </a>
                    <a href="{{ route('loans.create') }}" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100">
                        <span class="w-8 h-8 bg-green-500 text-white rounded flex items-center justify-center mr-3">+</span>
                        <span class="text-sm font-medium text-green-700">{{ __('messages.new_loan') }}</span>
                    </a>
                    <a href="{{ route('repayments.create') }}" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100">
                        <span class="w-8 h-8 bg-purple-500 text-white rounded flex items-center justify-center mr-3">$</span>
                        <span class="text-sm font-medium text-purple-700">{{ __('messages.record_payment') }}</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Row 4: Recent Activities -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Recent Borrowers -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ __('messages.recent_borrowers') }}</h3>
                    <a href="{{ route('clients.index') }}" class="text-xs text-blue-600 hover:underline">{{ __('messages.view_all') }}</a>
                </div>
                <div class="p-4">
                    <ul class="space-y-3">
                        @forelse($recent_clients->take(4) as $client)
                        <li class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-sm font-medium text-gray-600">
                                    {{ substr($client->first_name, 0, 1) }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $client->first_name }} {{ $client->last_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $client->phone ?? __('messages.no_data') }}</p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-400">{{ $client->created_at->diffForHumans() }}</span>
                        </li>
                        @empty
                        <li class="text-center text-gray-500 py-4">{{ __('messages.no_borrowers_found') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <!-- Recent Loans -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ __('messages.recent_loans') }}</h3>
                    <a href="{{ route('loans.index') }}" class="text-xs text-blue-600 hover:underline">{{ __('messages.view_all') }}</a>
                </div>
                <div class="p-4">
                    <ul class="space-y-3">
                        @forelse($recent_loans->take(4) as $loan)
                        <li class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $loan->client->first_name ?? 'N/A' }} {{ $loan->client->last_name ?? '' }}</p>
                                <p class="text-xs text-gray-500">TZS {{ number_format($loan->principal, 0) }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full {{ $loan->status === 'active' ? 'bg-green-100 text-green-700' : ($loan->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') }}">
                                {{ __('messages.status_' . $loan->status) }}
                            </span>
                        </li>
                        @empty
                        <li class="text-center text-gray-500 py-4">{{ __('messages.no_loans_found') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <!-- Recent Repayments -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ __('messages.recent_repayments') }}</h3>
                    <a href="{{ route('repayments.index') }}" class="text-xs text-blue-600 hover:underline">{{ __('messages.view_all') }}</a>
                </div>
                <div class="p-4">
                    <ul class="space-y-3">
                        @forelse($recent_repayments->take(4) as $repayment)
                        <li class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $repayment->loan->client->first_name ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-500">TZS {{ number_format($repayment->amount, 0) }}</p>
                            </div>
                            <span class="text-xs text-gray-400">{{ $repayment->created_at->diffForHumans() }}</span>
                        </li>
                        @empty
                        <li class="text-center text-gray-500 py-4">{{ __('messages.no_repayments_found') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- Today's Repayments -->
        @if(($today_repayments ?? collect())->count() > 0)
        <div class="mb-6">
            <div class="bg-white border border-blue-200 rounded-lg shadow-sm">
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
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="font-semibold text-yellow-800 mb-2">{{ __('messages.upcoming_repayments') }}</h4>
                <ul class="space-y-2">
                    @foreach(($upcoming_repayments ?? collect())->take(5) as $schedule)
                    <li class="flex justify-between text-sm">
                        <div>
                            <span class="text-yellow-700">{{ $schedule->loan->client->first_name ?? '' }} {{ $schedule->loan->client->last_name ?? '' }}</span>
                            <span class="ml-1 text-xs text-yellow-500">{{ \Carbon\Carbon::parse($schedule->due_date)->format('d M') }}</span>
                        </div>
                        <span class="text-yellow-600 font-medium">TZS {{ number_format($schedule->total_amount - $schedule->paid_amount, 0) }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if(($overdue_loans ?? collect())->count() > 0)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h4 class="font-semibold text-red-800 mb-2">{{ __('messages.overdue_loans') }}</h4>
                <ul class="space-y-2">
                    @foreach(($overdue_loans ?? collect())->take(5) as $loan)
                    <li class="flex justify-between text-sm">
                        <span class="text-red-700">{{ $loan->client->first_name ?? '' }} {{ $loan->client->last_name ?? '' }}</span>
                        <span class="text-red-600 font-medium">TZS {{ number_format($loan->total_overdue_amount ?? 0, 0) }}</span>
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
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ __('messages.staff') }}</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ $online_staff->count() }} {{ __('messages.active') }}
                    </span>
                </div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.staff') }}</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.email') }}</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.roles') }}</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('messages.status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($online_staff as $staff)
                                <tr>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8">
                                                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium">
                                                    {{ substr($staff['name'], 0, 1) }}
                                                </div>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900">{{ $staff['name'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $staff['email'] }}</div>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($staff['role'] === 'admin') bg-blue-100 text-blue-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst(str_replace('_', ' ', $staff['role'])) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <span class="h-2 w-2 bg-green-400 rounded-full mr-2"></span>
                                            {{ $staff['time_spent'] }}
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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
