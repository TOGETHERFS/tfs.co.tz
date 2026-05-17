@extends('layouts.app')

@section('title', __('messages.dashboard'))
@section('page-title', __('messages.dashboard'))

@section('content')
<div x-data="dashboard()" x-init="init()" class="space-y-6">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
<!-- Total Borrowers -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
<p class="text-sm font-medium text-gray-500">{{ __('messages.total_borrowers') }}</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="stats.total_clients || '0'"></p>
                        <p class="text-sm text-green-600" x-show="stats.new_clients_this_month > 0">
                            +<span x-text="stats.new_clients_this_month"></span> this month
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Loans -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500">{{ __('messages.active_loans') }}</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="stats.active_loans || '0'"></p>
                        <p class="text-sm text-gray-600">
                            <span x-text="formatCurrency(stats.total_outstanding || 0)"></span> outstanding
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Portfolio at Risk -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500">{{ __('messages.portfolio_at_risk') }}</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="(stats.portfolio_at_risk || 0).toFixed(1) + '%'"></p>
                        <p class="text-sm text-gray-600">
                            <span x-text="formatCurrency(stats.overdue_amount || 0)"></span> overdue
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collection Rate -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500">{{ __('messages.collection_rate') }}</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="(stats.collection_rate || 0).toFixed(1) + '%'"></p>
                        <p class="text-sm text-gray-600">
                            <span x-text="formatCurrency(stats.collected_this_month || 0)"></span> this month
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Monthly Trends Chart -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('messages.monthly_trends') }}</h3>
                <div class="h-64">
                    <canvas id="monthlyTrendsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Loan Status Distribution -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('messages.loan_status_distribution') }}</h3>
                <div class="h-64">
                    <canvas id="loanStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities and Alerts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Loans -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('messages.recent_loans') }}</h3>
                    <a href="{{ route('loans.index') }}" class="text-sm text-primary-600 hover:text-primary-500">{{ __('messages.view_all') }}</a>
                </div>
                <div class="space-y-3">
                    <template x-for="loan in recentLoans" :key="loan.id">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900" x-text="loan.client_name"></p>
                                <p class="text-xs text-gray-500" x-text="loan.loan_number"></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900" x-text="formatCurrency(loan.principal_amount)"></p>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                      :class="{
                                          'bg-yellow-100 text-yellow-800': loan.status === 'pending',
                                          'bg-green-100 text-green-800': loan.status === 'approved',
                                          'bg-blue-100 text-blue-800': loan.status === 'disbursed',
                                          'bg-red-100 text-red-800': loan.status === 'rejected'
                                      }"
                                      x-text="loan.status.charAt(0).toUpperCase() + loan.status.slice(1)">
                                </span>
                            </div>
                        </div>
                    </template>
                    <div x-show="recentLoans.length === 0" class="text-center py-6 text-gray-500">
                        {{ __('messages.no_recent_loans') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts and Notifications -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('messages.alerts') }}</h3>
                <div class="space-y-3">
                    <template x-for="alert in alerts" :key="alert.id">
                        <div class="flex items-start space-x-3 p-3 rounded-lg"
                             :class="{
                                 'bg-red-50 border border-red-200': alert.type === 'overdue',
                                 'bg-yellow-50 border border-yellow-200': alert.type === 'due_today',
                                 'bg-blue-50 border border-blue-200': alert.type === 'upcoming'
                             }">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5"
                                     :class="{
                                         'text-red-500': alert.type === 'overdue',
                                         'text-yellow-500': alert.type === 'due_today',
                                         'text-blue-500': alert.type === 'upcoming'
                                     }"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium" 
                                   :class="{
                                       'text-red-800': alert.type === 'overdue',
                                       'text-yellow-800': alert.type === 'due_today',
                                       'text-blue-800': alert.type === 'upcoming'
                                   }"
                                   x-text="alert.message"></p>
                                <p class="text-xs mt-1"
                                   :class="{
                                       'text-red-600': alert.type === 'overdue',
                                       'text-yellow-600': alert.type === 'due_today',
                                       'text-blue-600': alert.type === 'upcoming'
                                   }"
                                   x-text="alert.description"></p>
                            </div>
                        </div>
                    </template>
                    <div x-show="alerts.length === 0" class="text-center py-6 text-gray-500">
                        {{ __('messages.no_alerts') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Online Staff Members -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Online Staff</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <span class="h-2 w-2 bg-green-400 rounded-full mr-1"></span>
                    {{ $online_users->count() }} online
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Active</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($online_users as $user)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="h-2 w-2 bg-green-400 rounded-full mr-2"></span>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $user['name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $user['email'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($user['role']) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $user['time_spent'] }} ago</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $user['ip_address'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No staff currently online</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function dashboard() {
    return {
        stats: {},
        recentLoans: [],
        alerts: [],
        monthlyTrendsChart: null,
        loanStatusChart: null,

        init() {
            this.loadDashboardData();
        },

        async loadDashboardData() {
            try {
                // Load statistics
                const statsResponse = await fetch('/api/dashboard/stats');
                this.stats = await statsResponse.json();

                // Load recent activities
                const activitiesResponse = await fetch('/api/dashboard/recent-activities');
                const activities = await activitiesResponse.json();
                this.recentLoans = activities.loans || [];

                // Load alerts
                const alertsResponse = await fetch('/api/dashboard/alerts');
                this.alerts = await alertsResponse.json();

                // Load chart data
                const chartResponse = await fetch('/api/dashboard/chart-data');
                const chartData = await chartResponse.json();
                
                this.initCharts(chartData);
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        },

        initCharts(data) {
            // Monthly Trends Chart
            const monthlyCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
            this.monthlyTrendsChart = new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: data.monthly_trends?.labels || [],
                    datasets: [
                        {
                            label: 'Disbursements',
                            data: data.monthly_trends?.disbursements || [],
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'Collections',
                            data: data.monthly_trends?.collections || [],
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('en-TZ', {
                                        style: 'currency',
                                        currency: 'TZS',
                                        minimumFractionDigits: 0
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            });

            // Loan Status Chart
            const statusCtx = document.getElementById('loanStatusChart').getContext('2d');
            this.loanStatusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: data.loan_status?.labels || [],
                    datasets: [{
                        data: data.loan_status?.data || [],
                        backgroundColor: [
                            'rgb(59, 130, 246)',
                            'rgb(16, 185, 129)',
                            'rgb(245, 158, 11)',
                            'rgb(239, 68, 68)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('en-TZ', {
                style: 'currency',
                currency: 'TZS',
                minimumFractionDigits: 0
            }).format(amount);
        }
    }
}
</script>
@endpush
@endsection