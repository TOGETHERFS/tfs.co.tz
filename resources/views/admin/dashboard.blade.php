@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
                        <p class="mt-1 text-sm text-gray-600">System overview and management</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            System Admin
                        </span>
                        <div class="text-sm text-gray-500">
                            Last updated: {{ now()->format('M d, Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-4 sm:px-6 lg:px-8 py-8">
        <!-- Global Overview (Top Row) with KPI toggles -->
        <div class="bg-white shadow rounded-lg mb-8" x-data="{ mode: 'today', kpis: @json($kpis) }">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Global Overview</h3>
                    <div class="inline-flex rounded-md shadow-sm" role="group">
                        <button type="button" @click="mode = 'today'" :class="mode==='today' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1 text-sm font-medium border border-gray-300 rounded-l-md">Today</button>
                        <button type="button" @click="mode = 'mtd'" :class="mode==='mtd' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1 text-sm font-medium border-t border-b border-gray-300">MTD</button>
                        <button type="button" @click="mode = 'ytd'" :class="mode==='ytd' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1 text-sm font-medium border border-gray-300 rounded-r-md">YTD</button>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Active Tenants -->
                    <div class="bg-white overflow-hidden shadow rounded-lg border">
                        <div class="p-4">
                            <dt class="text-sm font-medium text-gray-500">Active Tenants</dt>
                            <dd class="text-xl font-semibold text-gray-900" x-text="kpis[mode].active_tenants"></dd>
                        </div>
                    </div>
                    <!-- Active Borrowers -->
                    <div class="bg-white overflow-hidden shadow rounded-lg border">
                        <div class="p-4">
                            <dt class="text-sm font-medium text-gray-500">Active Borrowers</dt>
                            <dd class="text-xl font-semibold text-gray-900" x-text="kpis[mode].active_borrowers"></dd>
                        </div>
                    </div>
                    <!-- Loans Outstanding (TSHS) -->
                    <div class="bg-white overflow-hidden shadow rounded-lg border">
                        <div class="p-4">
                            <dt class="text-sm font-medium text-gray-500">Loans Outstanding (TSHS)</dt>
                            <dd class="text-xl font-semibold text-gray-900">TSHS <span x-text="new Intl.NumberFormat().format(kpis[mode].loans_outstanding)"></span></dd>
                        </div>
                    </div>
                    <!-- PAR30 / PAR60 -->
                    <div class="bg-white overflow-hidden shadow rounded-lg border">
                        <div class="p-4">
                            <dt class="text-sm font-medium text-gray-500">Portfolio at Risk (PAR30 / PAR60)</dt>
                            <dd class="text-xl font-semibold text-gray-900"><span x-text="kpis[mode].par30"></span>% / <span x-text="kpis[mode].par60"></span>%</dd>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
                    <!-- Repayments Collected -->
                    <div class="bg-white overflow-hidden shadow rounded-lg border">
                        <div class="p-4">
                            <dt class="text-sm font-medium text-gray-500">Repayments Collected</dt>
                            <dd class="text-xl font-semibold text-gray-900">TSHS <span x-text="new Intl.NumberFormat().format(kpis[mode].repayments_collected)"></span></dd>
                        </div>
                    </div>
                    <!-- Disbursements -->
                    <div class="bg-white overflow-hidden shadow rounded-lg border">
                        <div class="p-4">
                            <dt class="text-sm font-medium text-gray-500">Disbursements</dt>
                            <dd class="text-xl font-semibold text-gray-900">TSHS <span x-text="new Intl.NumberFormat().format(kpis[mode].disbursements)"></span></dd>
                        </div>
                    </div>
                    <!-- Wallet / Float Balance (SMS) -->
                    <div class="bg-white overflow-hidden shadow rounded-lg border">
                        <div class="p-4">
                            <dt class="text-sm font-medium text-gray-500">SMS Credits (Tenant Wallets)</dt>
                            <dd class="text-xl font-semibold text-gray-900" x-text="new Intl.NumberFormat().format(kpis[mode].wallet_sms_credits)"></dd>
                        </div>
                    </div>
                    <!-- Beem Live Balance -->
                    <div class="bg-white overflow-hidden shadow rounded-lg border">
                        <div class="p-4">
                            <dt class="text-sm font-medium text-gray-500">Beem Africa Live Balance</dt>
                            <dd class="text-xl font-semibold text-gray-900" x-text="new Intl.NumberFormat().format(kpis[mode].beem_live_balance)"></dd>
                        </div>
                    </div>
                    <!-- MRR & Churn -->
                    <div class="bg-white overflow-hidden shadow rounded-lg border">
                        <div class="p-4">
                            <dt class="text-sm font-medium text-gray-500">MRR & Churn</dt>
                            <dd class="text-xl font-semibold text-gray-900">TSHS <span x-text="new Intl.NumberFormat().format(kpis[mode].mrr)"></span> &middot; <span x-text="kpis[mode].churn_percent"></span>%</dd>
                        </div>
                    </div>
                </div>
                <!-- System Alerts Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
                    <div class="bg-white overflow-hidden shadow rounded-lg border">
                        <div class="p-4">
                            <dt class="text-sm font-medium text-gray-500">Failed payments</dt>
                            <dd class="text-xl font-semibold text-red-600">{{ $alerts['failed_payments'] }}</dd>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg border">
                        <div class="p-4">
                            <dt class="text-sm font-medium text-gray-500">Queues failed</dt>
                            <dd class="text-xl font-semibold text-red-600">{{ $alerts['failed_jobs'] }}</dd>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg border">
                        <div class="p-4">
                            <dt class="text-sm font-medium text-gray-500">Low SMS credits</dt>
                            <dd class="text-xl font-semibold text-orange-600">{{ $alerts['low_sms_credits'] }}</dd>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg border">
                        <div class="p-4">
                            <dt class="text-sm font-medium text-gray-500">Licenses expiring soon</dt>
                            <dd class="text-xl font-semibold text-yellow-600">{{ $alerts['licenses_expiring_soon'] }}</dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- System Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Tenants -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Tenants</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['total_tenants']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Users -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['total_users']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

<!-- Total Borrowers -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
<dt class="text-sm font-medium text-gray-500 truncate">Total Borrowers</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['total_clients']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Loan Amount -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Loan Amount</dt>
                                <dd class="text-lg font-medium text-gray-900">TSHS {{ number_format($stats['total_loan_amount'], 2) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Loans</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['active_loans']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending Loans</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['pending_loans']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-600 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 0a3 3 0 110 6H9l3 3-3-3h1.5a2.5 2.5 0 100-5H9z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Repayments</dt>
                                <dd class="text-lg font-medium text-gray-900">TSHS {{ number_format($stats['total_repayments'], 2) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Loans</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['total_loans']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Tables Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- User Role Distribution -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">User Role Distribution</h3>
                    <div class="space-y-3">
                        @foreach($user_roles as $role)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    @if($role->role === 'admin') bg-red-100 text-red-800
                                    @elseif($role->role === 'manager') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($role->role) }}
                                </span>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ $role->count }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Tenant Subscription Status -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Tenant Subscriptions</h3>
                    <div class="space-y-3">
                        @foreach($tenant_stats as $tenant)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    @if($tenant->status === 'active') bg-green-100 text-green-800
                                    @elseif($tenant->status === 'trial') bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($tenant->status) }}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ $tenant->count }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Loans -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Loans</h3>
                    <div class="flow-root">
                        <ul class="-my-5 divide-y divide-gray-200">
                            @forelse($recent_loans as $loan)
                            <li class="py-4">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $loan->client->first_name ?? 'N/A' }} {{ $loan->client->last_name ?? '' }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            TSHS {{ number_format($loan->principal, 2) }} - {{ ucfirst($loan->status) }}
                                        </p>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $loan->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </li>
                            @empty
                            <li class="py-4 text-center text-gray-500">No recent loans</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Pending Sender ID Applications -->
            <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-orange-500">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Pending Sender ID Applications</h3>
                        <a href="{{ route('admin.sender-ids.index') }}" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                    </div>
                    <div class="flow-root">
                        <ul class="-my-5 divide-y divide-gray-200">
                            @forelse($pending_sender_ids as $senderId)
                            <li class="py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900">{{ $senderId->sender_id }}</p>
                                        <p class="text-sm text-gray-500">
                                            {{ $senderId->tenant->name ?? 'Unknown Tenant' }} &middot; {{ $senderId->business_name ?? '' }}
                                        </p>
                                        <p class="text-xs text-gray-400">{{ $senderId->created_at->diffForHumans() }}</p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <form method="POST" action="{{ route('admin.sender-ids.approve', $senderId) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.sender-ids.reject', $senderId) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 text-xs font-medium text-white bg-red-600 rounded hover:bg-red-700">Reject</button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                            @empty
                            <li class="py-4 text-center text-gray-500">No pending applications</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Online Users -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Online Users</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            {{ $online_users->count() }} Active
                        </span>
                    </div>
                    <div class="flow-root">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Activity</th>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($online_users as $user)
                                    <tr>
                                        <td class="px-3 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8">
                                                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium">
                                                        {{ substr($user['name'], 0, 1) }}
                                                    </div>
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm font-medium text-gray-900">{{ $user['name'] }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $user['email'] }}</div>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $user['tenant_name'] }}</div>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                @if($user['role'] === 'superadmin' || $user['role'] === 'super_admin') bg-purple-100 text-purple-800
                                                @elseif($user['role'] === 'admin') bg-blue-100 text-blue-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ ucfirst(str_replace('_', ' ', $user['role'])) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <span class="h-2 w-2 bg-green-400 rounded-full mr-2"></span>
                                                {{ $user['time_spent'] }} ago
                                            </div>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $user['ip_address'] }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <svg class="h-12 w-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                <p>No users currently online</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subscriptions Expiring Soon (0-5 days) -->
            <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-red-500">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 flex items-center">
                            <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Subscriptions Expiring Soon
                        </h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            {{ count($expiring_soon_tenants) }} tenant(s)
                        </span>
                    </div>
                    @if(count($expiring_soon_tenants) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tenant</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Days Left</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($expiring_soon_tenants as $item)
                                <tr class="{{ $item['days_left'] <= 1 ? 'bg-red-50' : ($item['days_left'] <= 3 ? 'bg-yellow-50' : '') }}">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item['name'] }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $item['email'] ?? '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $item['type'] === 'trial' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ ucfirst($item['type']) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ $item['expires_at'] ? \Carbon\Carbon::parse($item['expires_at'])->format('M d, Y H:i') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $item['days_left'] <= 1 ? 'bg-red-100 text-red-800' : ($item['days_left'] <= 3 ? 'bg-orange-100 text-orange-800' : 'bg-yellow-100 text-yellow-800') }}">
                                            {{ $item['days_left'] <= 0 ? 'Today' : $item['days_left'].' day(s)' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                                        <a href="{{ route('admin.tenants.show', $item['id']) }}" class="text-blue-600 hover:text-blue-800 font-medium">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-center py-6 text-gray-500 text-sm">No subscriptions or trials expiring in the next 5 days.</p>
                    @endif
                </div>
            </div>

            <!-- Recent Repayments -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Repayments</h3>
                    <div class="flow-root">
                        <ul class="-my-5 divide-y divide-gray-200">
                            @forelse($recent_repayments as $repayment)
                            <li class="py-4">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $repayment->loan->client->first_name ?? 'N/A' }} {{ $repayment->loan->client->last_name ?? '' }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            TSHS {{ number_format($repayment->amount, 2) }}
                                        </p>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $repayment->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </li>
                            @empty
                            <li class="py-4 text-center text-gray-500">No recent repayments</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
