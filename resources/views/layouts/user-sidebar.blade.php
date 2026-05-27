@php
    // Safe route helper — returns '#' instead of throwing if route not defined
    if (!function_exists('safeRoute')) {
        function safeRoute(string $name, $params = []): string {
            try {
                return route($name, $params);
            } catch (\Throwable $e) {
                return '#';
            }
        }
    }

    $initialDropdown = null;
    if (request()->routeIs('clients.*') || request()->routeIs('groups.*')) {
        $initialDropdown = 'clients';
    } elseif (request()->routeIs('loans.*') || request()->routeIs('loan-products.*')) {
        $initialDropdown = 'loans';
    } elseif (request()->routeIs('repayments.*') || request()->routeIs('repayments.history')) {
        $initialDropdown = 'repayments';
    } elseif (request()->routeIs('billing.*')) {
        $initialDropdown = 'billing';
    } elseif (request()->routeIs('reports.*')) {
        $initialDropdown = 'reports';
    } elseif (request()->routeIs('accounting.*')) {
        $initialDropdown = 'accounting';
    } elseif (request()->routeIs('messages.*')) {
        $initialDropdown = 'messages';
    } elseif (request()->routeIs('user.staff*') || request()->routeIs('user.roles*') || request()->routeIs('user.branches*')) {
        $initialDropdown = 'staff_roles';
    }
@endphp
<nav class="h-full flex flex-col" x-data="{ activeDropdown: '{{ $initialDropdown }}' }">
    <!-- Scrollable Navigation Content -->
    <div class="flex-1 overflow-y-auto">
        <div class="px-4 py-6 space-y-2">
        
        <!-- Dashboard - Blue Theme -->
        <a href="{{ safeRoute('user.dashboard') }}" 
           class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('user.dashboard') ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-md' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700' }}">
            <div class="p-1.5 rounded-lg {{ request()->routeIs('user.dashboard') ? 'bg-white/20' : 'bg-blue-100 text-blue-600' }} mr-3">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                </svg>
            </div>
            {{ __('messages.dashboard') }}
        </a>

        <!-- Borrowers - Indigo Theme -->
        <div class="space-y-1">
            <button type="button" @click="activeDropdown = activeDropdown === 'clients' ? null : 'clients'" 
                    class="group w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ request()->routeIs('clients.*') || request()->routeIs('groups.*') ? 'bg-gradient-to-r from-indigo-500 to-indigo-600 text-white shadow-md' : 'text-gray-700 hover:bg-indigo-50' }}">
                <div class="flex items-center">
                    <div class="p-1.5 rounded-lg {{ request()->routeIs('clients.*') || request()->routeIs('groups.*') ? 'bg-white/20' : 'bg-indigo-100 text-indigo-600' }} mr-3">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    {{ __('messages.borrowers') }}
                </div>
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': activeDropdown === 'clients' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div x-show="activeDropdown === 'clients'" x-transition class="ml-8 space-y-1 border-l-2 border-indigo-200 pl-4">
                <a href="{{ safeRoute('clients.index') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('clients.index') ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-gray-600 hover:text-indigo-600 hover:bg-indigo-50' }}">{{ __('messages.all_borrowers') }}</a>
                <a href="{{ safeRoute('clients.create') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('clients.create') ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-gray-600 hover:text-indigo-600 hover:bg-indigo-50' }}">{{ __('messages.add_new_borrower') }}</a>
                <a href="{{ safeRoute('clients.import') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('clients.import') ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-gray-600 hover:text-indigo-600 hover:bg-indigo-50' }}">{{ __('messages.import_borrowers') }}</a>
                <a href="{{ safeRoute('groups.index') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('groups.index') ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-gray-600 hover:text-indigo-600 hover:bg-indigo-50' }}">{{ __('messages.view_groups') }}</a>
                <a href="{{ safeRoute('groups.create') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('groups.create') ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-gray-600 hover:text-indigo-600 hover:bg-indigo-50' }}">{{ __('messages.add_group') }}</a>
            </div>
        </div>

        <!-- Loans - Green Theme -->
        <div class="space-y-1">
            <button type="button" @click="activeDropdown = activeDropdown === 'loans' ? null : 'loans'" 
                    class="group w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ request()->routeIs('loans.*') || request()->routeIs('loan-products.*') ? 'bg-gradient-to-r from-green-600 to-green-700 text-white shadow-md' : 'text-gray-700 hover:bg-green-50' }}">
                <div class="flex items-center">
                    <div class="p-1.5 rounded-lg {{ request()->routeIs('loans.*') || request()->routeIs('loan-products.*') ? 'bg-white bg-opacity-20' : 'bg-green-100 text-green-600' }} mr-3">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    {{ __('messages.loans') }}
                </div>
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': activeDropdown === 'loans' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div x-show="activeDropdown === 'loans'" x-transition class="ml-8 space-y-1 border-l-2 border-green-200 pl-4">
                <a href="{{ safeRoute('loans.index') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('loans.index') ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-600 hover:text-green-600 hover:bg-green-50' }}">{{ __('messages.all_loans') }}</a>
                <a href="{{ safeRoute('loans.create') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('loans.create') ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-600 hover:text-green-600 hover:bg-green-50' }}">{{ __('messages.new_loan_application') }}</a>
                <a href="{{ safeRoute('loan-products.index') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('loan-products.*') ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-600 hover:text-green-600 hover:bg-green-50' }}">{{ __('messages.loan_products') }}</a>
                <a href="{{ safeRoute('loans.import') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('loans.import') ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-600 hover:text-green-600 hover:bg-green-50' }}">{{ __('messages.import_loans') }}</a>
            </div>
        </div>

        <!-- Repayments - Orange/Amber Theme -->
        <div class="space-y-1">
            <button type="button" @click="activeDropdown = activeDropdown === 'repayments' ? null : 'repayments'" 
                    class="group w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ request()->routeIs('repayments.*') ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-md' : 'text-gray-700 hover:bg-amber-50' }}">
                <div class="flex items-center">
                    <div class="p-1.5 rounded-lg {{ request()->routeIs('repayments.*') ? 'bg-white/20' : 'bg-amber-100 text-amber-600' }} mr-3">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    {{ __('messages.repayments') }}
                </div>
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': activeDropdown === 'repayments' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div x-show="activeDropdown === 'repayments'" x-transition class="ml-8 space-y-1 border-l-2 border-amber-200 pl-4">
                <a href="{{ safeRoute('repayments.index') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('repayments.index') ? 'bg-amber-100 text-amber-700 font-medium' : 'text-gray-600 hover:text-amber-600 hover:bg-amber-50' }}">{{ __('messages.all_repayments') }}</a>
                <a href="{{ safeRoute('repayments.create') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('repayments.create') ? 'bg-amber-100 text-amber-700 font-medium' : 'text-gray-600 hover:text-amber-600 hover:bg-amber-50' }}">{{ __('messages.record_repayment') }}</a>
                @if(Route::has('repayments.history'))
                <a href="{{ safeRoute('repayments.history') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('repayments.history') ? 'bg-amber-100 text-amber-700 font-medium' : 'text-gray-600 hover:text-amber-600 hover:bg-amber-50' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ __('messages.repayment_history') }}
                </a>
                @endif
            </div>
        </div>


        <!-- Reports - Cyan/Teal Theme -->
        @php $reportsActive = request()->routeIs('reports.*'); @endphp
        <div class="space-y-1">
            <button type="button" @click="activeDropdown = activeDropdown === 'reports' ? null : 'reports'"
                    class="group w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ $reportsActive ? 'bg-gradient-to-r from-cyan-500 to-teal-600 text-white shadow-md' : 'text-gray-800 hover:bg-cyan-50 hover:text-cyan-700' }}">
                <div class="flex items-center">
                    <div class="p-1.5 rounded-lg {{ $reportsActive ? 'bg-white bg-opacity-25' : 'bg-cyan-100 text-cyan-600' }} mr-3 flex-shrink-0">
                        <svg class="h-5 w-5 {{ $reportsActive ? 'text-white' : 'text-cyan-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span class="font-semibold">{{ __('messages.reports') }}</span>
                </div>
                <svg class="h-4 w-4 flex-shrink-0 transition-transform" :class="{ 'rotate-90': activeDropdown === 'reports' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div x-show="activeDropdown === 'reports'" x-transition class="ml-8 space-y-1 border-l-2 border-cyan-200 pl-4">
                @if(Route::has('reports.index'))
                <a href="{{ safeRoute('reports.index') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('reports.index') ? 'bg-cyan-100 text-cyan-700 font-medium' : 'text-gray-600 hover:text-cyan-600 hover:bg-cyan-50' }}">{{ __('messages.overview') }}</a>
                @endif
                @if(Route::has('reports.loan-portfolio'))
                <a href="{{ safeRoute('reports.loan-portfolio') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('reports.loan-portfolio') ? 'bg-cyan-100 text-cyan-700 font-medium' : 'text-gray-600 hover:text-cyan-600 hover:bg-cyan-50' }}">{{ __('messages.loan_portfolio') }}</a>
                @endif
                @if(Route::has('reports.arrears-aging'))
                <a href="{{ safeRoute('reports.arrears-aging') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('reports.arrears-aging') ? 'bg-cyan-100 text-cyan-700 font-medium' : 'text-gray-600 hover:text-cyan-600 hover:bg-cyan-50' }}">{{ __('messages.arrears_aging') }}</a>
                @endif
                @if(Route::has('reports.profit-loss'))
                <a href="{{ safeRoute('reports.profit-loss') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('reports.profit-loss') ? 'bg-cyan-100 text-cyan-700 font-medium' : 'text-gray-600 hover:text-cyan-600 hover:bg-cyan-50' }}">{{ __('messages.profit_loss') }}</a>
                @endif
                @if(Route::has('reports.collections'))
                <a href="{{ safeRoute('reports.collections') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('reports.collections') ? 'bg-cyan-100 text-cyan-700 font-medium' : 'text-gray-600 hover:text-cyan-600 hover:bg-cyan-50' }}">{{ __('messages.collections') }}</a>
                @endif
            </div>
        </div>

        <!-- Accounts - Violet/Purple Theme -->
        <div class="space-y-1">
            <button type="button" @click="activeDropdown = activeDropdown === 'accounting' ? null : 'accounting'"
                    class="group w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ request()->routeIs('accounting.*') ? 'bg-gradient-to-r from-violet-500 to-purple-500 text-white shadow-md' : 'text-gray-700 hover:bg-violet-50' }}">
                <div class="flex items-center">
                    <div class="p-1.5 rounded-lg {{ request()->routeIs('accounting.*') ? 'bg-white/20' : 'bg-violet-100 text-violet-600' }} mr-3">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    {{ __('messages.accounting') }}
                </div>
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': activeDropdown === 'accounting' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div x-show="activeDropdown === 'accounting'" x-transition class="ml-8 space-y-1 border-l-2 border-violet-200 pl-4">

                {{-- Chart of Accounts --}}
                <a href="{{ safeRoute('accounting.chart-of-accounts.index') }}"
                   class="block px-3 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('accounting.chart-of-accounts.*') ? 'bg-violet-100 text-violet-700 font-medium' : 'text-gray-600 hover:text-violet-600 hover:bg-violet-50' }}">
                    {{ __('messages.chart_of_accounts') }}
                </a>
                {{-- COA sub-links --}}
                <div class="ml-3 space-y-0.5 border-l border-violet-100 pl-3">
                    <a href="{{ safeRoute('accounting.chart-of-accounts.index', ['type' => 'asset']) }}"
                       class="flex items-center gap-1.5 px-2 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('accounting.chart-of-accounts.*') && request()->get('type') === 'asset' ? 'text-violet-700 font-semibold bg-violet-50' : 'text-gray-500 hover:text-violet-600 hover:bg-violet-50' }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 flex-shrink-0"></span>
                        {{ __('messages.acc_assets') }}
                    </a>
                    <a href="{{ safeRoute('accounting.chart-of-accounts.index', ['type' => 'liability']) }}"
                       class="flex items-center gap-1.5 px-2 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('accounting.chart-of-accounts.*') && request()->get('type') === 'liability' ? 'text-violet-700 font-semibold bg-violet-50' : 'text-gray-500 hover:text-violet-600 hover:bg-violet-50' }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span>
                        {{ __('messages.acc_liabilities') }}
                    </a>
                    <a href="{{ safeRoute('accounting.chart-of-accounts.index', ['type' => 'equity']) }}"
                       class="flex items-center gap-1.5 px-2 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('accounting.chart-of-accounts.*') && request()->get('type') === 'equity' ? 'text-violet-700 font-semibold bg-violet-50' : 'text-gray-500 hover:text-violet-600 hover:bg-violet-50' }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400 flex-shrink-0"></span>
                        {{ __('messages.acc_equity') }}
                    </a>
                    <a href="{{ safeRoute('accounting.chart-of-accounts.index', ['type' => 'income']) }}"
                       class="flex items-center gap-1.5 px-2 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('accounting.chart-of-accounts.*') && request()->get('type') === 'income' ? 'text-violet-700 font-semibold bg-violet-50' : 'text-gray-500 hover:text-violet-600 hover:bg-violet-50' }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-400 flex-shrink-0"></span>
                        {{ __('messages.acc_revenue') }}
                    </a>
                    <a href="{{ safeRoute('accounting.chart-of-accounts.index', ['type' => 'expense']) }}"
                       class="flex items-center gap-1.5 px-2 py-1.5 text-xs rounded-md transition-colors {{ request()->routeIs('accounting.chart-of-accounts.*') && request()->get('type') === 'expense' ? 'text-violet-700 font-semibold bg-violet-50' : 'text-gray-500 hover:text-violet-600 hover:bg-violet-50' }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-orange-400 flex-shrink-0"></span>
                        {{ __('messages.acc_expenses') }}
                    </a>
                </div>

                {{-- Journal Entries --}}
                <a href="{{ safeRoute('accounting.journal-entries.index') }}"
                   class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('accounting.journal-entries.*') ? 'bg-violet-100 text-violet-700 font-medium' : 'text-gray-600 hover:text-violet-600 hover:bg-violet-50' }}">
                    {{ __('messages.journal_entries') }}
                </a>

                {{-- Expenses --}}
                <a href="{{ safeRoute('accounting.expenses.index') }}"
                   class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('accounting.expenses.*') ? 'bg-violet-100 text-violet-700 font-medium' : 'text-gray-600 hover:text-violet-600 hover:bg-violet-50' }}">
                    {{ __('messages.acc_expenses') }}
                </a>

                {{-- Profit & Loss --}}
                <a href="{{ safeRoute('accounting.reports.income-statement') }}"
                   class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('accounting.reports.income-statement') ? 'bg-violet-100 text-violet-700 font-medium' : 'text-gray-600 hover:text-violet-600 hover:bg-violet-50' }}">
                    {{ __('messages.acc_profit_loss') }}
                </a>

                {{-- Balance Sheet --}}
                <a href="{{ safeRoute('accounting.reports.balance-sheet') }}"
                   class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('accounting.reports.balance-sheet') ? 'bg-violet-100 text-violet-700 font-medium' : 'text-gray-600 hover:text-violet-600 hover:bg-violet-50' }}">
                    {{ __('messages.balance_sheet') }}
                </a>

                {{-- Cash Flow --}}
                <a href="{{ safeRoute('accounting.reports.cash-flow') }}"
                   class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('accounting.reports.cash-flow') ? 'bg-violet-100 text-violet-700 font-medium' : 'text-gray-600 hover:text-violet-600 hover:bg-violet-50' }}">
                    {{ __('messages.acc_cash_flow') }}
                </a>

                {{-- Books of Accounts --}}
                <a href="{{ safeRoute('accounting.reports.books-of-accounts') }}"
                   class="flex items-center gap-1.5 px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('accounting.reports.books-of-accounts') ? 'bg-violet-100 text-violet-700 font-medium' : 'text-gray-600 hover:text-violet-600 hover:bg-violet-50' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Books of Accounts
                </a>

            </div>
        </div>

        <!-- Messages - Yellow/Amber Theme -->
        <div class="space-y-1">
            <button type="button" @click="activeDropdown = activeDropdown === 'messages' ? null : 'messages'" 
                    class="group w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ request()->routeIs('messages.*') ? 'bg-gradient-to-r from-yellow-500 to-amber-500 text-white shadow-md' : 'text-gray-700 hover:bg-yellow-50' }}">
                <div class="flex items-center">
                    <div class="p-1.5 rounded-lg {{ request()->routeIs('messages.*') ? 'bg-white/20' : 'bg-yellow-100 text-yellow-600' }} mr-3">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    {{ __('messages.messages') }}
                </div>
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': activeDropdown === 'messages' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div x-show="activeDropdown === 'messages'" x-transition class="ml-8 space-y-1 border-l-2 border-yellow-200 pl-4">
                <a href="{{ safeRoute('messages.sender-ids') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('messages.sender-ids') || request()->routeIs('messages.request-sender-id') ? 'bg-yellow-100 text-yellow-700 font-medium' : 'text-gray-600 hover:text-yellow-600 hover:bg-yellow-50' }}">Apply Sender ID</a>
                <a href="{{ safeRoute('messages.compose') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('messages.compose') ? 'bg-yellow-100 text-yellow-700 font-medium' : 'text-gray-600 hover:text-yellow-600 hover:bg-yellow-50' }}">Send SMS</a>
                <a href="{{ safeRoute('messages.history') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('messages.history') ? 'bg-yellow-100 text-yellow-700 font-medium' : 'text-gray-600 hover:text-yellow-600 hover:bg-yellow-50' }}">Message History</a>
                <a href="{{ safeRoute('messages.buy') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('messages.buy') ? 'bg-yellow-100 text-yellow-700 font-medium' : 'text-gray-600 hover:text-yellow-600 hover:bg-yellow-50' }}">{{ __('messages.buy_sms_credits') }}</a>
            </div>
        </div>

        <!-- Staff & Roles - Slate/Gray Theme -->
        <div class="space-y-1">
            <button type="button" @click="activeDropdown = activeDropdown === 'staff_roles' ? null : 'staff_roles'" 
                    class="group w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ request()->routeIs('user.staff*') || request()->routeIs('user.roles*') || request()->routeIs('user.branches*') ? 'bg-gradient-to-r from-slate-600 to-slate-700 text-white shadow-md' : 'text-gray-700 hover:bg-slate-100' }}">
                <div class="flex items-center">
                    <div class="p-1.5 rounded-lg {{ request()->routeIs('user.staff*') || request()->routeIs('user.roles*') || request()->routeIs('user.branches*') ? 'bg-white/20' : 'bg-slate-200 text-slate-600' }} mr-3">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    {{ __('messages.staff_roles') }}
                </div>
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': activeDropdown === 'staff_roles' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div x-show="activeDropdown === 'staff_roles'" x-transition class="ml-8 space-y-1 border-l-2 border-slate-200 pl-4">
                <a href="{{ safeRoute('user.staff') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('user.staff') && !request()->routeIs('user.staff.create') ? 'bg-slate-200 text-slate-700 font-medium' : 'text-gray-600 hover:text-slate-700 hover:bg-slate-100' }}">{{ __('messages.all_staff') }}</a>
                <a href="{{ safeRoute('user.staff.create') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('user.staff.create') ? 'bg-slate-200 text-slate-700 font-medium' : 'text-gray-600 hover:text-slate-700 hover:bg-slate-100' }}">{{ __('messages.create_staff') }}</a>
                <a href="{{ safeRoute('user.roles.manage') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('user.roles.manage') ? 'bg-slate-200 text-slate-700 font-medium' : 'text-gray-600 hover:text-slate-700 hover:bg-slate-100' }}">{{ __('messages.all_roles') }}</a>
                <a href="{{ safeRoute('user.roles.create') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('user.roles.create') ? 'bg-slate-200 text-slate-700 font-medium' : 'text-gray-600 hover:text-slate-700 hover:bg-slate-100' }}">{{ __('messages.create_role') }}</a>
                <a href="{{ safeRoute('user.branches.index') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('user.branches.index') ? 'bg-slate-200 text-slate-700 font-medium' : 'text-gray-600 hover:text-slate-700 hover:bg-slate-100' }}">{{ __('messages.all_branches') }}</a>
                <a href="{{ safeRoute('user.branches.create') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('user.branches.create') ? 'bg-slate-200 text-slate-700 font-medium' : 'text-gray-600 hover:text-slate-700 hover:bg-slate-100' }}">{{ __('messages.create_branch') }}</a>
            </div>
        </div>

        <!-- Payroll - Emerald Theme -->
        <div class="space-y-1">
            <button type="button" @click="activeDropdown = activeDropdown === 'payroll' ? null : 'payroll'"
                    class="group w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ request()->routeIs('payroll.*') ? 'bg-gradient-to-r from-emerald-500 to-green-500 text-white shadow-md' : 'text-gray-700 hover:bg-emerald-50' }}">
                <div class="flex items-center">
                    <div class="p-1.5 rounded-lg {{ request()->routeIs('payroll.*') ? 'bg-white/20' : 'bg-emerald-100 text-emerald-600' }} mr-3">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    {{ __('messages.payroll') }}
                </div>
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': activeDropdown === 'payroll' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div x-show="activeDropdown === 'payroll'" x-transition class="ml-8 space-y-1 border-l-2 border-emerald-200 pl-4">
                @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                <a href="{{ safeRoute('payroll.index') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('payroll.index') ? 'bg-emerald-100 text-emerald-700 font-medium' : 'text-gray-600 hover:text-emerald-600 hover:bg-emerald-50' }}">{{ __('messages.salary_records') }}</a>
                <a href="{{ safeRoute('payroll.create') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('payroll.create') ? 'bg-emerald-100 text-emerald-700 font-medium' : 'text-gray-600 hover:text-emerald-600 hover:bg-emerald-50' }}">{{ __('messages.add_salary') }}</a>
                <a href="{{ safeRoute('payroll.advances.admin') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('payroll.advances.admin') ? 'bg-emerald-100 text-emerald-700 font-medium' : 'text-gray-600 hover:text-emerald-600 hover:bg-emerald-50' }}">{{ __('messages.salary_advance_requests') }}</a>
                @else
                <a href="{{ safeRoute('payroll.my-slips') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('payroll.my-slips') ? 'bg-emerald-100 text-emerald-700 font-medium' : 'text-gray-600 hover:text-emerald-600 hover:bg-emerald-50' }}">{{ __('messages.my_salary_slips') }}</a>
                <a href="{{ safeRoute('payroll.advances') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('payroll.advances') ? 'bg-emerald-100 text-emerald-700 font-medium' : 'text-gray-600 hover:text-emerald-600 hover:bg-emerald-50' }}">{{ __('messages.my_salary_advances') }}</a>
                <a href="{{ safeRoute('payroll.advance.create') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('payroll.advance.create') ? 'bg-emerald-100 text-emerald-700 font-medium' : 'text-gray-600 hover:text-emerald-600 hover:bg-emerald-50' }}">{{ __('messages.apply_salary_advance') }}</a>
                @endif
            </div>
        </div>

        <!-- Leave - Teal Theme -->
        <div class="space-y-1">
            <button type="button" @click="activeDropdown = activeDropdown === 'leave' ? null : 'leave'"
                    class="group w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ request()->routeIs('leave.*') ? 'bg-gradient-to-r from-teal-500 to-cyan-500 text-white shadow-md' : 'text-gray-700 hover:bg-teal-50' }}">
                <div class="flex items-center">
                    <div class="p-1.5 rounded-lg {{ request()->routeIs('leave.*') ? 'bg-white/20' : 'bg-teal-100 text-teal-600' }} mr-3">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    {{ __('messages.leave') }}
                </div>
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': activeDropdown === 'leave' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div x-show="activeDropdown === 'leave'" x-transition class="ml-8 space-y-1 border-l-2 border-teal-200 pl-4">
                @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                <a href="{{ safeRoute('leave.index') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('leave.index') ? 'bg-teal-100 text-teal-700 font-medium' : 'text-gray-600 hover:text-teal-600 hover:bg-teal-50' }}">{{ __('messages.leave_requests') }}</a>
                <a href="{{ safeRoute('leave.balances') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('leave.balances') ? 'bg-teal-100 text-teal-700 font-medium' : 'text-gray-600 hover:text-teal-600 hover:bg-teal-50' }}">{{ __('messages.leave_balances') }}</a>
                @endif
                <a href="{{ safeRoute('leave.my') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('leave.my') ? 'bg-teal-100 text-teal-700 font-medium' : 'text-gray-600 hover:text-teal-600 hover:bg-teal-50' }}">{{ __('messages.my_leaves') }}</a>
                <a href="{{ safeRoute('leave.create') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('leave.create') ? 'bg-teal-100 text-teal-700 font-medium' : 'text-gray-600 hover:text-teal-600 hover:bg-teal-50' }}">{{ __('messages.apply_leave') }}</a>
            </div>
        </div>

        <!-- Documents - Violet Theme -->
        <div class="space-y-1">
            <button type="button" @click="activeDropdown = activeDropdown === 'documents' ? null : 'documents'"
                    class="group w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ request()->routeIs('documents.*') ? 'bg-gradient-to-r from-violet-500 to-purple-500 text-white shadow-md' : 'text-gray-700 hover:bg-violet-50' }}">
                <div class="flex items-center">
                    <div class="p-1.5 rounded-lg {{ request()->routeIs('documents.*') ? 'bg-white/20' : 'bg-violet-100 text-violet-600' }} mr-3">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    {{ __('messages.documents') }}
                </div>
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': activeDropdown === 'documents' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div x-show="activeDropdown === 'documents'" x-transition class="ml-8 space-y-1 border-l-2 border-violet-200 pl-4">
                <a href="{{ safeRoute('documents.index') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('documents.index') ? 'bg-violet-100 text-violet-700 font-medium' : 'text-gray-600 hover:text-violet-600 hover:bg-violet-50' }}">{{ __('messages.all_documents') }}</a>
                <a href="{{ safeRoute('documents.create') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('documents.create') ? 'bg-violet-100 text-violet-700 font-medium' : 'text-gray-600 hover:text-violet-600 hover:bg-violet-50' }}">{{ __('messages.upload_document') }}</a>
            </div>
        </div>

        <!-- Notifications - Red Theme -->
        <a href="{{ safeRoute('notifications.index') }}" 
           class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('notifications.*') ? 'bg-gradient-to-r from-red-500 to-red-600 text-white shadow-md' : 'text-gray-700 hover:bg-red-50 hover:text-red-700' }}">
            <div class="p-1.5 rounded-lg {{ request()->routeIs('notifications.*') ? 'bg-white/20' : 'bg-red-100 text-red-600' }} mr-3">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </div>
            {{ __('messages.notifications') }}
        </a>
        </div>
    </div>

    <!-- Loan Calculator Trigger Button -->
    <div class="px-4 py-4 border-t border-gray-200 flex-shrink-0" style="background:#ffffff;">
        <button type="button" onclick="document.getElementById('loanCalcModal').style.display='flex'"
                class="w-full flex items-center justify-between px-4 py-3 rounded-xl" style="background:#f0fdf4;border:1px solid #bbf7d0;">
            <div class="flex items-center space-x-2">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:#dcfce7;">
                    <svg class="w-4 h-4" style="color:#16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span style="color:#15803d;font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;">Loan Calculator</span>
            </div>
            <svg class="w-4 h-4" style="color:#6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
        </button>
    </div>

    <!-- Loan Calculator Full Modal -->
    <div id="loanCalcModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:flex-start;justify-content:center;overflow-y:auto;padding:20px 10px;"
         x-data="loanCalcModal()" x-init="init()">
        <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:16px;width:100%;max-width:900px;margin:auto;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
            <!-- Header -->
            <div style="background:#f9fafb;border-radius:16px 16px 0 0;padding:20px 24px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:40px;height:40px;border-radius:10px;background:#dcfce7;display:flex;align-items:center;justify-content:center;">
                        <svg style="width:20px;height:20px;color:#16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 style="color:#111827;font-size:18px;font-weight:700;margin:0;">Loan Calculator</h2>
                        <p style="color:#6b7280;font-size:12px;margin:0;">Calculate repayment schedule in TSHS</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('loanCalcModal').style.display='none'"
                        style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;padding:8px;cursor:pointer;color:#374151;display:flex;align-items:center;justify-content:center;">
                    <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Inputs -->
            <div style="padding:24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;border-bottom:1px solid #e5e7eb;">
                <div>
                    <label style="color:#374151;font-size:11px;font-weight:600;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.06em;">Loan Amount (TSHS)</label>
                    <input type="number" x-model="amount" @input="calculate()" placeholder="e.g. 3000000" min="0"
                           style="width:100%;padding:10px 12px;border-radius:8px;background:#ffffff;border:1px solid #d1d5db;color:#111827;font-size:14px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="color:#374151;font-size:11px;font-weight:600;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.06em;">Interest Rate (% / month)</label>
                    <input type="number" x-model="rate" @input="calculate()" placeholder="e.g. 10" min="0" max="100" step="0.1"
                           style="width:100%;padding:10px 12px;border-radius:8px;background:#ffffff;border:1px solid #d1d5db;color:#111827;font-size:14px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="color:#374151;font-size:11px;font-weight:600;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.06em;">Term</label>
                    <input type="number" x-model="term" @input="calculate()" placeholder="e.g. 12" min="1"
                           style="width:100%;padding:10px 12px;border-radius:8px;background:#ffffff;border:1px solid #d1d5db;color:#111827;font-size:14px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="color:#374151;font-size:11px;font-weight:600;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.06em;">Repayment</label>
                    <select x-model="schedule" @change="calculate()"
                            style="width:100%;padding:10px 12px;border-radius:8px;background:#ffffff;border:1px solid #d1d5db;color:#111827;font-size:14px;box-sizing:border-box;">
                        <option value="monthly">Monthly</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="biweekly">Bi-weekly</option>
                    </select>
                </div>
                <div>
                    <label style="color:#374151;font-size:11px;font-weight:600;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.06em;">Interest Type</label>
                    <select x-model="type" @change="calculate()"
                            style="width:100%;padding:10px 12px;border-radius:8px;background:#ffffff;border:1px solid #d1d5db;color:#111827;font-size:14px;box-sizing:border-box;">
                        <option value="flat">Flat Rate</option>
                        <option value="reducing">Reducing Balance</option>
                    </select>
                </div>
            </div>

            <!-- Summary Cards -->
            <div x-show="result" style="padding:20px 24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;border-bottom:1px solid #e5e7eb;">
                <div style="background:#fefce8;border-radius:10px;padding:14px 16px;border:1px solid #fde68a;">
                    <p style="color:#92400e;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 6px;">Installment Payment</p>
                    <p style="color:#b45309;font-size:16px;font-weight:700;margin:0;" x-text="result?.installment"></p>
                </div>
                <div style="background:#fff7ed;border-radius:10px;padding:14px 16px;border:1px solid #fed7aa;">
                    <p style="color:#9a3412;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 6px;">Total Interest</p>
                    <p style="color:#c2410c;font-size:16px;font-weight:700;margin:0;" x-text="result?.totalInterest"></p>
                </div>
                <div style="background:#f0fdf4;border-radius:10px;padding:14px 16px;border:1px solid #bbf7d0;">
                    <p style="color:#14532d;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 6px;">Total Repayment</p>
                    <p style="color:#15803d;font-size:16px;font-weight:700;margin:0;" x-text="result?.total"></p>
                </div>
                <div style="background:#eff6ff;border-radius:10px;padding:14px 16px;border:1px solid #bfdbfe;">
                    <p style="color:#1e3a5f;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 6px;">Principal Amount</p>
                    <p style="color:#1d4ed8;font-size:16px;font-weight:700;margin:0;" x-text="result?.principal"></p>
                </div>
            </div>

            <!-- Amortization Table -->
            <div x-show="schedule_rows.length > 0" style="padding:0 0 24px;">
                <div style="padding:16px 24px 12px;display:flex;align-items:center;justify-content:space-between;">
                    <h3 style="color:#111827;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin:0;">Repayment Schedule</h3>
                    <span style="color:#6b7280;font-size:12px;" x-text="`${schedule_rows.length} installments`"></span>
                </div>
                <div style="overflow-x:auto;max-height:360px;overflow-y:auto;">
                    <table style="width:100%;border-collapse:collapse;min-width:500px;">
                        <thead style="position:sticky;top:0;z-index:1;">
                            <tr>
                                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;background:#f3f4f6;color:#374151;border-bottom:2px solid #e5e7eb;">#</th>
                                <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;background:#f3f4f6;color:#374151;border-bottom:2px solid #e5e7eb;">Installment</th>
                                <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;background:#f0fdf4;color:#15803d;border-bottom:2px solid #e5e7eb;">Principal</th>
                                <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;background:#fff7ed;color:#c2410c;border-bottom:2px solid #e5e7eb;">Interest</th>
                                <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;background:#f3f4f6;color:#374151;border-bottom:2px solid #e5e7eb;">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, idx) in schedule_rows" :key="idx">
                                <tr :style="idx % 2 === 0 ? 'background:#ffffff;' : 'background:#f9fafb;'">
                                    <td style="padding:9px 16px;font-size:12px;font-weight:700;color:#b45309;border-bottom:1px solid #f3f4f6;" x-text="row.n"></td>
                                    <td style="padding:9px 16px;text-align:right;font-size:12px;font-weight:600;color:#111827;border-bottom:1px solid #f3f4f6;" x-text="row.payment"></td>
                                    <td style="padding:9px 16px;text-align:right;font-size:12px;color:#15803d;border-bottom:1px solid #f3f4f6;" x-text="row.principal"></td>
                                    <td style="padding:9px 16px;text-align:right;font-size:12px;color:#c2410c;border-bottom:1px solid #f3f4f6;" x-text="row.interest"></td>
                                    <td style="padding:9px 16px;text-align:right;font-size:12px;color:#374151;border-bottom:1px solid #f3f4f6;" x-text="row.balance"></td>
                                </tr>
                            </template>
                            <!-- Totals row -->
                            <tr style="background:#f0fdf4;">
                                <td style="padding:10px 16px;font-size:12px;font-weight:700;color:#111827;border-top:2px solid #16a34a;">TOTAL</td>
                                <td style="padding:10px 16px;text-align:right;font-size:12px;font-weight:700;color:#b45309;border-top:2px solid #16a34a;" x-text="result?.total"></td>
                                <td style="padding:10px 16px;text-align:right;font-size:12px;font-weight:700;color:#15803d;border-top:2px solid #16a34a;" x-text="result?.principal"></td>
                                <td style="padding:10px 16px;text-align:right;font-size:12px;font-weight:700;color:#c2410c;border-top:2px solid #16a34a;" x-text="result?.totalInterest"></td>
                                <td style="padding:10px 16px;text-align:right;font-size:12px;font-weight:700;color:#374151;border-top:2px solid #16a34a;">0.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty state -->
            <div x-show="!result" style="padding:40px 24px;text-align:center;">
                <svg style="width:48px;height:48px;color:#d1d5db;margin:0 auto 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <p style="color:#6b7280;font-size:14px;margin:0;">Enter loan details above to see the repayment schedule</p>
            </div>
        </div>
    </div>

    <script>
    function loanCalcModal() {
        return {
            amount: '', rate: '', term: '', type: 'flat', schedule: 'monthly',
            result: null, schedule_rows: [],
            init() {},
            fmt(v) {
                return 'TSHS ' + Math.round(v).toLocaleString('en-TZ');
            },
            calculate() {
                const p = parseFloat(this.amount);
                const r = parseFloat(this.rate) / 100;
                const n = parseInt(this.term);
                if (!p || !r || !n || p <= 0 || r <= 0 || n <= 0) {
                    this.result = null; this.schedule_rows = []; return;
                }

                // Convert monthly rate to per-installment rate
                let rInst = r;
                if (this.schedule === 'daily')     rInst = r / 30;
                else if (this.schedule === 'weekly')   rInst = r / 4;
                else if (this.schedule === 'biweekly') rInst = r / 2;

                // Term in months for flat interest
                let termMonths = n;
                if (this.schedule === 'daily')     termMonths = n / 30;
                else if (this.schedule === 'weekly')   termMonths = n / 4;
                else if (this.schedule === 'biweekly') termMonths = n / 2;

                let installment, totalInterest, totalAmount;
                const rows = [];

                if (this.type === 'reducing') {
                    if (rInst > 0) {
                        installment = p * (rInst * Math.pow(1 + rInst, n)) / (Math.pow(1 + rInst, n) - 1);
                    } else {
                        installment = p / n;
                    }
                    let balance = p;
                    for (let i = 1; i <= n; i++) {
                        const interest = balance * rInst;
                        const principal = installment - interest;
                        balance = Math.max(0, balance - principal);
                        rows.push({
                            n: i,
                            payment: this.fmt(installment),
                            principal: this.fmt(principal),
                            interest: this.fmt(interest),
                            balance: this.fmt(balance)
                        });
                    }
                    totalAmount = installment * n;
                    totalInterest = totalAmount - p;
                } else {
                    // Flat rate
                    totalInterest = p * r * termMonths;
                    const interestPerInst = totalInterest / n;
                    const principalPerInst = p / n;
                    installment = principalPerInst + interestPerInst;
                    totalAmount = p + totalInterest;
                    let balance = p;
                    for (let i = 1; i <= n; i++) {
                        balance = Math.max(0, balance - principalPerInst);
                        rows.push({
                            n: i,
                            payment: this.fmt(installment),
                            principal: this.fmt(principalPerInst),
                            interest: this.fmt(interestPerInst),
                            balance: this.fmt(balance)
                        });
                    }
                }

                this.schedule_rows = rows;
                this.result = {
                    installment: this.fmt(installment),
                    totalInterest: this.fmt(totalInterest),
                    total: this.fmt(totalAmount),
                    principal: this.fmt(p)
                };
            }
        };
    }
    </script>

    <!-- User Footer -->
    @auth
    <div class="px-4 py-4 border-t border-gray-200 flex-shrink-0 bg-gradient-to-r from-gray-50 to-gray-100">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="h-10 w-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-md">
                    <span class="text-white text-sm font-bold">{{ strtoupper(substr(auth()->user()->name ?? auth()->user()->role, 0, 1)) }}</span>
                </div>
            </div>
            <div class="ml-3">
                <p class="text-sm font-semibold text-gray-800 capitalize">{{ auth()->user()->name ?? auth()->user()->role }}</p>
                <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>
    @else
    <div class="px-4 py-4 border-t border-gray-200 flex-shrink-0">
        <a href="{{ safeRoute('login') }}" class="text-sm text-blue-600 hover:underline font-medium">{{ __('messages.sign_in') }}</a>
    </div>
    @endauth
</nav>
