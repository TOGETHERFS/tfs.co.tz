<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ 
    sidebarOpen: false,
    lastScrollY: 0,
    sidebarHidden: false
}" x-init="
    $watch('sidebarOpen', value => localStorage.setItem('sidebarOpen', value)); 
    sidebarOpen = localStorage.getItem('sidebarOpen') === 'true';
    
    // Scroll behavior for sidebar
    window.addEventListener('scroll', () => {
        const currentScrollY = window.scrollY;
        
        if (currentScrollY > lastScrollY && currentScrollY > 100) {
            // Scrolling down and past 100px
            sidebarHidden = true;
        } else if (currentScrollY < lastScrollY) {
            // Scrolling up
            sidebarHidden = false;
        }
        
        lastScrollY = currentScrollY;
    });
"
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Admin Panel</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS - CDN fallback for production -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    @if(file_exists(public_path('css/app.css')))
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @endif

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }
        
        /* Sticky Sidebar Styles */
        .sidebar-sticky {
            transition: transform 0.3s ease-in-out;
        }
        
        .sidebar-hidden {
            transform: translateY(-100px);
        }
        
        .sidebar-visible {
            transform: translateY(0);
        }
    </style>
    <!-- Facebook Pixel -->
    <script>
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window,document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '767769028708718');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=767769028708718&ev=PageView&noscript=1"/></noscript>
    <!-- End Facebook Pixel -->
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Admin Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 lg:ml-64">
            <div class="flex items-center justify-between px-4 py-3">
                <!-- Mobile menu button -->
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Logo -->
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-900">{{ __('messages.admin_panel') }}</h1>
                </div>

                <!-- Admin Navigation -->
                <div class="flex items-center space-x-4">
                    <!-- Language Switcher -->
                    @include('components.language-switcher')
                    
                    
                    <!-- Notifications -->
                    <button class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>

                    <!-- Admin Profile Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                            <div class="h-8 w-8 bg-red-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-medium">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                            <span class="hidden md:block text-sm font-medium">{{ auth()->user()->name }}</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile Settings</a>
                            <a href="{{ route('tenant.settings') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Organization Settings</a>
                            <div class="border-t border-gray-100"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign Out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex">
            <!-- Admin Sidebar - Mobile -->
            <div x-show="sidebarOpen" 
                 x-transition:enter="transition ease-in-out duration-300"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg lg:hidden">
                @include('layouts.admin-sidebar')
            </div>

            <!-- Admin Sidebar - Desktop -->
            <div class="hidden lg:flex lg:flex-shrink-0">
                <div class="w-64 bg-white shadow-sm border-r border-gray-200 fixed top-0 left-0 h-full overflow-y-auto z-40 sidebar-sticky"
                     :class="sidebarHidden ? 'sidebar-hidden' : 'sidebar-visible'">
                    @include('layouts.admin-sidebar')
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 min-w-0 lg:ml-64">
                <!-- Success/Error Messages -->
                @if (session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md m-4" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md m-4" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <!-- Page Content -->
                <main class="p-6">
                    @yield('content')
                </main>
            </div>
        </div>
    </div>

    <!-- Mobile sidebar overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-75 lg:hidden"
         @click="sidebarOpen = false"></div>

    @stack('scripts')
</body>
</html>