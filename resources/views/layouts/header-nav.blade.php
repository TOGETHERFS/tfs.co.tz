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

<div class="flex items-center space-x-4 ml-auto">
    <!-- Notifications -->
    <div x-data="{ 
            open: false, 
            notifications: @json($notifications ?? []), 
            unreadCount: {{ $unreadNotificationsCount ?? 0 }} 
         }"
         class="relative">
        <button @click="open = !open" 
                class="relative p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-3.5-3.5a1.5 1.5 0 010-2.12L20 8h-5M9 17H4l3.5-3.5a1.5 1.5 0 000-2.12L4 8h5m6 9a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span x-show="unreadCount > 0" 
                  x-text="unreadCount" 
                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"></span>
        </button>

        <!-- Notifications dropdown -->
        <div x-show="open" 
             @click.away="open = false"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50">
            <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
                    <button class="text-sm text-primary-600 hover:text-primary-500">Mark all read</button>
                </div>
                
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <template x-for="notification in notifications" :key="notification.id">
                        <div class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50" 
                             :class="{ 'bg-blue-50': !notification.read_at }">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 2L3 7v11a1 1 0 001 1h3v-8h6v8h3a1 1 0 001-1V7l-7-5z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900" x-text="notification.data.message"></p>
                                <p class="text-xs text-gray-500" x-text="new Date(notification.created_at).toLocaleDateString()"></p>
                            </div>
                        </div>
                    </template>
                    
                    <div x-show="notifications.length === 0" class="text-center py-6 text-gray-500">
                        No notifications
                    </div>
                </div>
                
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <a href="{{ route('notifications.index') }}" 
                       class="block text-center text-sm text-primary-600 hover:text-primary-500">
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
                class="flex items-center space-x-2 p-2 text-sm rounded-full hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500">
            <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center">
                <span class="text-sm font-medium text-white">
                    {{ substr(optional(auth()->user())->name ?? 'U', 0, 1) }}
                </span>
            </div>
            <span class="hidden md:block text-gray-700">{{ optional(auth()->user())->name ?? '' }}</span>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <!-- User dropdown -->
        <div x-show="open" 
             @click.away="open = false"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50">
            <div class="py-1">
                <div class="px-4 py-2 border-b border-gray-200">
                    <p class="text-sm font-medium text-gray-900">{{ optional(auth()->user())->name ?? '' }}</p>
                    <p class="text-xs text-gray-500">{{ optional(auth()->user())->email ?? '' }}</p>
                    @if(session('current_tenant'))
                        <p class="text-xs text-primary-600 mt-1">{{ session('current_tenant')->name }}</p>
                    @endif
                    @if(optional(auth()->user())->role)
                        <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded bg-blue-100 text-blue-800">
                            {{ ucfirst(optional(auth()->user())->role) }}
                        </span>
                    @endif
                </div>
                
                <a href="{{ route('profile.edit') }}" 
                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    My Profile
                </a>

                @can('super-admin')
                <a href="{{ route('admin.tenants.index') }}" 
                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    Admin Panel
                </a>
                @endcan
                
                <div class="border-t border-gray-200"></div>
                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" 
                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
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
    <a href="{{ route('login') }}" class="text-sm text-primary-600 hover:underline">Sign in</a>
    @endif
    @endauth
</div>