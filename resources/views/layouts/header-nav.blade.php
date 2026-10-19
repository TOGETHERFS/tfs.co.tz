<!-- Hamburger Menu Button (all screen sizes) -->
@auth
<button type="button" @click="sidebarOpen = !sidebarOpen" 
        class="p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200"
        :title="sidebarOpen ? 'Close menu' : 'Open menu'">
    <!-- Hamburger icon -->
    <svg x-show="!sidebarOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
    <!-- Close (X) icon -->
    <svg x-show="sidebarOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
    </svg>
</button>
@endauth

<!-- Brand / Page Title -->
<div class="flex items-center ml-3 min-w-0">
    @if(!empty($generalSettings['company_logo']))
        <img src="{{ asset('storage/' . $generalSettings['company_logo']) }}" alt="Logo" class="h-7 w-7 rounded object-cover mr-2 flex-shrink-0">
    @endif
    <span class="text-sm font-semibold text-gray-800 truncate hidden sm:block">{{ $generalSettings['company_name'] ?? config('app.name', 'TFS LMS') }}</span>
</div>

<div class="flex items-center space-x-4 ml-auto">
    <!-- Notifications -->
    <div x-data="{ open: false, notifications: @json($notifications ?? []), unreadCount: {{ $unreadNotificationsCount ?? 0 }} }" class="relative">
        <button @click="open = !open"
                class="relative p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span x-show="unreadCount > 0" x-text="unreadCount"
                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center font-medium"></span>
        </button>
        <div x-show="open" @click.away="open = false"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 z-50">
            <div class="p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Notifications</h3>
                <div class="space-y-2 max-h-72 overflow-y-auto">
                    <template x-for="notification in notifications" :key="notification.id">
                        <div class="flex items-start space-x-3 p-2.5 rounded-lg hover:bg-gray-50 cursor-pointer"
                             :class="{ 'bg-blue-50': !notification.read_at }">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-800" x-text="notification.data.message"></p>
                                <p class="text-xs text-gray-400 mt-0.5" x-text="new Date(notification.created_at).toLocaleDateString()"></p>
                            </div>
                        </div>
                    </template>
                    <div x-show="notifications.length === 0" class="text-center py-6 text-sm text-gray-400">
                        No notifications
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <a href="{{ route('notifications.index') }}" class="block text-center text-xs font-medium text-blue-600 hover:text-blue-700">
                        View all notifications
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- User menu -->
    @auth
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open"
                class="flex items-center space-x-2 pl-1 pr-2 py-1 text-sm rounded-full hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
            <div class="w-7 h-7 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-semibold text-white">{{ substr(optional(auth()->user())->name ?? 'U', 0, 1) }}</span>
            </div>
            <span class="hidden md:block text-gray-700 font-medium text-sm">{{ optional(auth()->user())->name ?? '' }}</span>
            <svg class="w-3.5 h-3.5 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <div x-show="open" @click.away="open = false"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 z-50">
            <div class="py-1">
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 rounded-t-xl">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ optional(auth()->user())->name ?? '' }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ optional(auth()->user())->email ?? '' }}</p>
                    @if(session('current_tenant'))
                        <p class="text-xs text-blue-600 mt-1">{{ session('current_tenant')->name }}</p>
                    @endif
                    @if(optional(auth()->user())->role)
                        <span class="inline-block mt-1.5 px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">
                            {{ ucfirst(optional(auth()->user())->role) }}
                        </span>
                    @endif
                </div>
                <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    My Profile
                </a>
                @can('super-admin')
                <a href="{{ route('admin.tenants.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    Admin Panel
                </a>
                @endcan
                <div class="border-t border-gray-100 my-1"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>
    @else
    @if (!request()->routeIs('login'))
    <a href="{{ route('login') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Sign in</a>
    @endif
    @endauth
</div>