<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <title>@yield('title', 'Together Financial Services')</title>
    <meta name="description" content="@yield('description', 'Professional microfinance management system for institutions in Tanzania. Manage loans, clients, repayments, and reporting with ease.')">
    <meta name="keywords" content="microfinance, loan management, Tanzania, Together Financial Services, financial software">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="@yield('title', 'Together Financial Services')">
    <meta property="og:description" content="@yield('description', 'Professional microfinance management system for institutions in Tanzania.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    
    <!-- Scripts and Styles -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-hover { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
        /* Responsive media defaults */
        img, video, canvas, iframe { max-width: 100%; height: auto; }
    </style>
    
    @stack('styles')
</head>
<body class="bg-white">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center">
                        <div class="w-8 h-8 bg-pink-600 rounded-lg flex items-center justify-center mr-3">
                            <span class="text-white font-bold text-sm">T</span>
                        </div>
                        <span class="text-xl font-semibold text-gray-900">TOGETHER FINANCIAL SERVICES</span>
                    </a>
                </div>
                
                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('home') }}" class="text-gray-700 hover:text-blue-600 transition-colors">{{ __('messages.home') }}</a>
                    <a href="#features" class="text-gray-700 hover:text-blue-600 transition-colors">{{ __('messages.features') }}</a>
                </div>
                
                <!-- Auth Buttons - Desktop -->
                <div class="hidden md:flex items-center space-x-4">
                    @guest
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-pink-600 transition-colors">{{ __('messages.login') }}</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700 transition-colors">{{ __('messages.dashboard') }}</a>
                    @endguest
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" class="text-gray-700 hover:text-blue-600 p-2" x-data x-on:click="$dispatch('toggle-mobile-menu')">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div x-data="{ open: false }" x-on:toggle-mobile-menu.window="open = !open" x-show="open" x-transition class="md:hidden border-t border-gray-200">
            <div class="px-4 py-3 space-y-3">
                <a href="{{ route('home') }}" class="block text-gray-700 hover:text-pink-600">{{ __('messages.home') }}</a>
                <a href="#features" class="block text-gray-700 hover:text-pink-600">{{ __('messages.features') }}</a>
                @guest
                    <div class="pt-3 border-t border-gray-200">
                        <a href="{{ route('login') }}" class="block text-gray-700 hover:text-pink-600 mb-2">{{ __('messages.sign_in') }}</a>
                    </div>
                @else
                    <div class="pt-3 border-t border-gray-200">
                        <a href="{{ route('dashboard') }}" class="block bg-pink-600 text-white px-4 py-2 rounded-lg text-center">{{ __('messages.go_to_dashboard') }}</a>
                    </div>
                @endguest
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 bg-pink-600 rounded-lg flex items-center justify-center mr-3">
                            <span class="text-white font-bold text-sm">T</span>
                        </div>
                        <span class="text-xl font-semibold">TOGETHER FINANCIAL SERVICES</span>
                    </div>
                    <p class="text-gray-400 mb-4">Together Financial Services is a licensed microfinance institution based in Mkuranga, Pwani, Tanzania. We provide accessible and affordable financial services including individual loans, group loans, and staff loans to empower communities and drive economic growth.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('home') }}" class="text-gray-400 hover:text-white transition-colors">Home</a></li>
                        <li><a href="#features" class="text-gray-400 hover:text-white transition-colors">Features</a></li>
                        <li><a href="{{ route('login') }}" class="text-gray-400 hover:text-white transition-colors">Login</a></li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>TOGETHER FINANCIAL SERVICES</li>
                        <li>Mkuranga, Pwani</li>
                        <li>0659130440</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} Together Financial Services. All rights reserved.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
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
</body>
</html>