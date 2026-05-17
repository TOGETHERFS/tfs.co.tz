@php
    $isSuperAdmin = auth()->check() && method_exists(auth()->user(), 'isSuperAdmin') && auth()->user()->isSuperAdmin();
@endphp
<div class="flex flex-col h-full bg-white">
    <!-- Logo -->
    <div class="flex items-center justify-between h-16 px-4 bg-primary-600 flex-shrink-0">
        <img src="{{ asset('logo.jpeg') }}" alt="PhidTech LMS" class="h-10 w-auto">
        <!-- Close button for mobile -->
        <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-md text-white hover:bg-primary-700">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto">
        <div class="px-4 py-6 space-y-1">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" 
           class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('dashboard') ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
            </svg>
            {{ __('messages.dashboard') }}
        </a>

        <!-- Borrowers Section -->
        <div class="pt-2">
            <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                {{ __('messages.borrowers') }}
            </div>
            <div class="ml-4 space-y-1">
                <a href="{{ route('clients.index') }}" 
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('clients.index') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.all_borrowers') }}
                </a>
                <a href="{{ route('clients.create') }}" 
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('clients.create') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.add_borrower') }}
                </a>
                <a href="{{ route('clients.import') }}" 
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('clients.import') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.import_borrowers') }}
                </a>
                <a href="{{ route('groups.index') }}"
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('groups.index') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.view_groups') }}
                </a>
                <a href="{{ route('groups.create') }}"
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('groups.create') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.add_group') }}
                </a>
            </div>
        </div>

        <!-- Loans Section -->
        <div class="pt-2">
            <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
                {{ __('messages.loans') }}
            </div>
            <div class="ml-4 space-y-1">
                <a href="{{ route('loans.index') }}" 
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('loans.index') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.all_loans') }}
                </a>
                <a href="{{ route('loans.create') }}" 
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('loans.create') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.new_loan') }}
                </a>
                <a href="{{ route('loan-products.index') }}" 
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('loan-products.*') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.loan_products') }}
                </a>
            </div>
        </div>

        <!-- Repayments Section -->
        <div class="pt-2">
            <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path>
                </svg>
                {{ __('messages.repayments') }}
            </div>
            <div class="ml-4 space-y-1">
                <a href="{{ route('repayments.index') }}" 
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('repayments.index') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.all_repayments') }}
                </a>
                <a href="{{ route('repayments.create') }}" 
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('repayments.create') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.record_payment_btn') }}
                </a>
            </div>
        </div>

        <!-- Reports -->
        <div class="pt-2">
            <a href="{{ route('reports.index') }}" 
               class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('reports.*') ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                {{ __('messages.reports') }}
            </a>
        </div>

        @can('accounts.view')
        <!-- Accounting Section -->
        <div class="pt-2">
            <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                {{ __('messages.accounting') }}
            </div>
            <div class="ml-4 space-y-1">
                {{-- Chart of Accounts with sub-links --}}
                <div>
                    <a href="{{ route('accounting.chart-of-accounts.index') }}"
                       class="block px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('accounting.chart-of-accounts.*') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        {{ __('messages.chart_of_accounts') }}
                    </a>
                    <div class="ml-3 mt-0.5 space-y-0.5">
                        <a href="{{ route('accounting.chart-of-accounts.index', ['type' => 'asset']) }}"
                           class="block px-3 py-1.5 text-xs rounded-lg {{ request()->routeIs('accounting.chart-of-accounts.*') && request()->get('type') === 'asset' ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:bg-gray-50' }}">
                            {{ __('messages.acc_assets') }}
                        </a>
                        <a href="{{ route('accounting.chart-of-accounts.index', ['type' => 'liability']) }}"
                           class="block px-3 py-1.5 text-xs rounded-lg {{ request()->routeIs('accounting.chart-of-accounts.*') && request()->get('type') === 'liability' ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:bg-gray-50' }}">
                            {{ __('messages.acc_liabilities') }}
                        </a>
                        <a href="{{ route('accounting.chart-of-accounts.index', ['type' => 'equity']) }}"
                           class="block px-3 py-1.5 text-xs rounded-lg {{ request()->routeIs('accounting.chart-of-accounts.*') && request()->get('type') === 'equity' ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:bg-gray-50' }}">
                            {{ __('messages.acc_equity') }}
                        </a>
                        <a href="{{ route('accounting.chart-of-accounts.index', ['type' => 'income']) }}"
                           class="block px-3 py-1.5 text-xs rounded-lg {{ request()->routeIs('accounting.chart-of-accounts.*') && request()->get('type') === 'income' ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:bg-gray-50' }}">
                            {{ __('messages.acc_revenue') }}
                        </a>
                        <a href="{{ route('accounting.chart-of-accounts.index', ['type' => 'expense']) }}"
                           class="block px-3 py-1.5 text-xs rounded-lg {{ request()->routeIs('accounting.chart-of-accounts.*') && request()->get('type') === 'expense' ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:bg-gray-50' }}">
                            {{ __('messages.acc_expenses') }}
                        </a>
                    </div>
                </div>
                <a href="{{ route('accounting.journal-entries.index') }}"
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('accounting.journal-entries.*') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.journal_entries') }}
                </a>
                <a href="{{ route('accounting.expenses.index') }}"
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('accounting.expenses.*') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.acc_expenses') }}
                </a>
                <a href="{{ route('accounting.reports.income-statement') }}"
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('accounting.reports.income-statement') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.acc_profit_loss') }}
                </a>
                <a href="{{ route('accounting.reports.balance-sheet') }}"
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('accounting.reports.balance-sheet') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.balance_sheet') }}
                </a>
                <a href="{{ route('accounting.reports.cash-flow') }}"
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('accounting.reports.cash-flow') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.acc_cash_flow') }}
                </a>
            </div>
        </div>
        @endcan

        <!-- Staff & Roles Section -->
        <div class="pt-2">
            <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                {{ __('messages.staff_and_roles') }}
            </div>
            <div class="ml-4 space-y-1">
                <a href="{{ route('user.staff') }}" 
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('user.staff*') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.all_staff') }}
                </a>
                <a href="{{ route('user.roles.index') }}" 
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('user.roles*') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.roles') }}
                </a>
                <a href="{{ route('user.branches.index') }}" 
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('user.branches*') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.branches') }}
                </a>
            </div>
        </div>

        @can('manage-billing')
        <!-- Billing Section -->
        <div class="pt-2">
            <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                {{ __('messages.billing') }}
            </div>
            <div class="ml-4 space-y-1">
                <a href="{{ route('billing.index') }}" 
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('billing.index') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.overview') }}
                </a>
                <a href="{{ route('billing.subscription') }}" 
                   class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('billing.subscription') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ __('messages.subscription') }}
                </a>
                @if($isSuperAdmin)
                    <a href="{{ route('billing.plans') }}" 
                       class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('billing.plans') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        {{ __('messages.subscription_plans') }}
                    </a>
                    @if (Route::has('billing.invoices'))
                    <a href="{{ route('billing.invoices') }}" 
                       class="block px-3 py-2 text-sm rounded-lg {{ request()->routeIs('billing.invoices*') ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        {{ __('messages.invoices') }}
                    </a>
                    @endif
                @endif
            </div>
        </div>
        @endcan

        @can('manage-tenant')
        <!-- Settings -->
        <div class="pt-2">
            <a href="{{ route('tenant.settings') }}" 
               class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('tenant.settings') ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                {{ __('messages.settings') }}
            </a>
        </div>
        @endcan
    </div>
    </nav>

    <!-- User info -->
    @auth
    <div class="p-4 border-t border-gray-200 flex-shrink-0">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-white">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </span>
                </div>
            </div>
            <div class="ml-3 min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-900 truncate">
                    {{ auth()->user()->name }}
                </p>
                <p class="text-xs text-gray-500 truncate">
                    {{ auth()->user()->email }}
                </p>
            </div>
        </div>
    </div>
    @else
    <div class="p-4 border-t border-gray-200 flex-shrink-0">
        <a href="{{ route('login') }}" class="text-sm text-primary-600 hover:underline">{{ __('messages.sign_in') }}</a>
    </div>
    @endauth
</div>
