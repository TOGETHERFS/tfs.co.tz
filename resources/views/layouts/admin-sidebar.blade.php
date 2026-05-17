<nav class="h-full flex flex-col bg-white" x-data="{
    activeDropdown: '{{ request()->routeIs('admin.users.*') ? 'users' : (request()->routeIs('admin.staff.*') ? 'staff' : (request()->routeIs('admin.settings.*') || request()->routeIs('admin.sms-providers.*') || request()->routeIs('admin.plans.*') ? 'settings' : (request()->routeIs('admin.messages.*') || request()->routeIs('admin.sms.*') || request()->routeIs('admin.sender-ids.*') ? 'messages' : (request()->routeIs('admin.reports.*') ? 'reports' : (request()->routeIs('billing.*') ? 'billing' : (request()->routeIs('admin.accounts.*') ? 'accounts' : '')))))) }}',
    toggleDropdown(dropdown) {
        this.activeDropdown = this.activeDropdown === dropdown ? '' : dropdown;
    }
}">
    <!-- Logo Section -->
    <div class="flex items-center justify-center h-16 px-4 bg-gradient-to-r from-slate-800 to-slate-900">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-pink-600 rounded-lg flex items-center justify-center shadow-md">
                <span class="text-white font-bold text-lg">T</span>
            </div>
            <div>
                <span class="text-white font-semibold text-base leading-tight">TOGETHER FINANCIAL</span>
                <span class="text-slate-300 text-xs block">Admin Panel</span>
            </div>
        </div>
    </div>

    <div class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <!-- Admin Dashboard -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasAdminPermission('view-dashboard'))
        <a href="{{ route('admin.dashboard') }}" 
           class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-rose-500 to-rose-600 text-white shadow-md' : 'text-gray-700 hover:bg-rose-50 hover:text-rose-700' }}">
            <div class="p-1.5 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-white/20' : 'bg-rose-100 text-rose-600' }} mr-3">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                </svg>
            </div>
            Admin Dashboard
        </a>
        @endif

        <!-- Staff & Roles -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasAnyAdminPermission(['view-staff', 'create-staff', 'edit-staff', 'delete-staff', 'manage-roles']))
        <div class="space-y-1">
            <button @click="toggleDropdown('staff')" 
                    class="group w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ request()->routeIs('admin.staff.*') ? 'bg-gradient-to-r from-teal-500 to-teal-600 text-white shadow-md' : 'text-gray-700 hover:bg-teal-50' }}">
                <div class="flex items-center">
                    <div class="p-1.5 rounded-lg {{ request()->routeIs('admin.staff.*') ? 'bg-white/20' : 'bg-teal-100 text-teal-600' }} mr-3">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    Staff & Roles
                </div>
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': activeDropdown === 'staff' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div x-show="activeDropdown === 'staff'" x-transition class="ml-8 space-y-1 border-l-2 border-teal-200 pl-4">
                <a href="{{ route('admin.staff.index') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.staff.index') ? 'bg-teal-100 text-teal-700 font-medium' : 'text-gray-600 hover:text-teal-600 hover:bg-teal-50' }}">All Staff</a>
                <a href="{{ route('admin.staff.create') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.staff.create') ? 'bg-teal-100 text-teal-700 font-medium' : 'text-gray-600 hover:text-teal-600 hover:bg-teal-50' }}">Create Staff</a>
                <a href="{{ route('admin.staff.roles') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.staff.roles') ? 'bg-teal-100 text-teal-700 font-medium' : 'text-gray-600 hover:text-teal-600 hover:bg-teal-50' }}">Manage Roles</a>
            </div>
        </div>
        @endif

        <!-- System Settings -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasAnyAdminPermission(['view-settings', 'edit-settings']))
        <div class="space-y-1">
            <button @click="toggleDropdown('settings')" 
                    class="group w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.sms-providers.*') || request()->routeIs('admin.plans.*') ? 'bg-gradient-to-r from-slate-600 to-slate-700 text-white shadow-md' : 'text-gray-700 hover:bg-slate-50' }}">
                <div class="flex items-center">
                    <div class="p-1.5 rounded-lg {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.sms-providers.*') ? 'bg-white/20' : 'bg-slate-100 text-slate-600' }} mr-3">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    System Settings
                </div>
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': activeDropdown === 'settings' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div x-show="activeDropdown === 'settings'" x-transition class="ml-8 space-y-1 border-l-2 border-slate-200 pl-4">
                <a href="{{ route('admin.settings.index') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.settings.index') ? 'bg-slate-100 text-slate-700 font-medium' : 'text-gray-600 hover:text-slate-600 hover:bg-slate-50' }}">General Settings</a>
            </div>
        </div>
        @endif

        <!-- Reports & Analytics -->
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasAnyAdminPermission(['view-reports', 'export-reports']))
        <div class="space-y-1">
            <button @click="toggleDropdown('reports')" 
                    class="group w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ request()->routeIs('admin.reports.*') ? 'bg-gradient-to-r from-cyan-500 to-cyan-600 text-white shadow-md' : 'text-gray-700 hover:bg-cyan-50' }}">
                <div class="flex items-center">
                    <div class="p-1.5 rounded-lg {{ request()->routeIs('admin.reports.*') ? 'bg-white/20' : 'bg-cyan-100 text-cyan-600' }} mr-3">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    Reports & Analytics
                </div>
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': activeDropdown === 'reports' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div x-show="activeDropdown === 'reports'" x-transition class="ml-8 space-y-1 border-l-2 border-cyan-200 pl-4">
                <a href="{{ route('admin.reports.index') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.reports.index') ? 'bg-cyan-100 text-cyan-700 font-medium' : 'text-gray-600 hover:text-cyan-600 hover:bg-cyan-50' }}">Overview</a>
                <a href="{{ route('admin.reports.loan-portfolio') }}" class="block px-3 py-2 text-sm rounded-md transition-colors text-gray-600 hover:text-cyan-600 hover:bg-cyan-50">Loan Portfolio</a>
                <a href="{{ route('admin.reports.collections') }}" class="block px-3 py-2 text-sm rounded-md transition-colors text-gray-600 hover:text-cyan-600 hover:bg-cyan-50">Collections</a>
                <a href="{{ route('admin.reports.profit-loss') }}" class="block px-3 py-2 text-sm rounded-md transition-colors text-gray-600 hover:text-cyan-600 hover:bg-cyan-50">Profit & Loss</a>
                <a href="{{ route('admin.reports.client-analysis') }}" class="block px-3 py-2 text-sm rounded-md transition-colors text-gray-600 hover:text-cyan-600 hover:bg-cyan-50">Client Analysis</a>
            </div>
        </div>
        @endif

    </div>

    <!-- Admin Footer -->
    <div class="px-4 py-4 border-t border-gray-200 bg-gradient-to-r from-slate-50 to-slate-100">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="h-10 w-10 bg-gradient-to-br from-rose-500 to-rose-600 rounded-full flex items-center justify-center shadow-md">
                    <span class="text-white text-sm font-bold">{{ substr(auth()->user()->name ?? 'A', 0, 1) }}</span>
                </div>
            </div>
            <div class="ml-3 min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-800 truncate">{{ auth()->user()->name ?? 'Administrator' }}</p>
                <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>
</nav>