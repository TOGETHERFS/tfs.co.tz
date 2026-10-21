<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: window.innerWidth >= 1024, isMobile: window.innerWidth < 1024 }">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light">
    <title>{{ config('app.name', 'TFS LMS') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        /* Force light mode — prevent OS/browser dark mode from inverting colours */
        :root { color-scheme: light only; }
        html, body {
            background-color: #f3f4f6 !important;
            color: #111827 !important;
            color-scheme: light !important;
        }
        /* All form controls: white bg, dark text */
        input, select, textarea, button {
            background-color: #ffffff;
            color: #111827;
            color-scheme: light;
        }
        input[type="date"], input[type="datetime-local"],
        input[type="month"],  input[type="time"] {
            background-color: #ffffff !important;
            color: #111827 !important;
            color-scheme: light !important;
        }
        /* Sidebar and cards always white */
        aside, .bg-white { background-color: #ffffff !important; }
        /* Responsive media defaults */
        img, video, canvas, iframe { max-width: 100%; height: auto; }
        [x-cloak] { display: none !important; }
        nav[x-data] { opacity: 1; }
        /* ── Print defaults ── */
        @media print {
            :root { color-scheme: light only; }
            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            input, select, textarea {
                background: #ffffff !important;
                color: #000000 !important;
                border: 1px solid #d1d5db !important;
            }
            aside, header, footer, nav,
            .no-print { display: none !important; }
            main { margin-left: 0 !important; padding: 0 !important; }
            .bg-white { background: #ffffff !important; }
            .shadow-sm, .shadow { box-shadow: none !important; }
            .text-gray-900, .text-gray-800, .text-gray-700 { color: #000000 !important; }
            .text-gray-500, .text-gray-400 { color: #4b5563 !important; }
            a { color: inherit !important; text-decoration: none !important; }
        }
    </style>
    @stack('styles')
    <script>
        window.AppSettings = @json($generalSettings ?? []);
        window.AppTranslations = @json(__('messages'));
        window.AppLocale = '{{ app()->getLocale() }}';
        window.__ = function(key, replacements = {}) {
            const keys = key.replace('messages.', '').split('.');
            let value = window.AppTranslations;
            for (const k of keys) {
                if (value && typeof value === 'object' && k in value) { value = value[k]; } else { return key; }
            }
            if (typeof value === 'string') {
                for (const [placeholder, replacement] of Object.entries(replacements)) {
                    value = value.replace(new RegExp(':' + placeholder, 'g'), replacement);
                }
            }
            return value || key;
        };
        window.trans = window.__;
    </script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Top Header Bar -->
        <header class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
            <div class="flex items-center justify-between h-16 px-4 sm:px-6">
                @include('layouts.header-nav')
            </div>
        </header>

        <div class="flex">
            @auth
                <!-- Desktop Sidebar - Toggle via hamburger -->
                <aside x-show="sidebarOpen"
                     x-transition:enter="transition ease-in-out duration-200 transform"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in-out duration-200 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full"
                     class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:top-16 lg:bottom-0 lg:left-0 bg-white border-r border-gray-200 z-40 overflow-y-auto"
                     x-cloak>
                    @include('layouts.user-sidebar')
                </aside>

                <!-- Mobile Sidebar Overlay (only on mobile) -->
                <div x-show="sidebarOpen && isMobile" 
                     x-transition:enter="transition-opacity ease-linear duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-linear duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75"
                     @click="sidebarOpen = false"
                     x-cloak>
                </div>

                <!-- Mobile Sidebar (only on mobile) -->
                <aside x-show="sidebarOpen && isMobile" 
                     x-transition:enter="transition ease-in-out duration-300 transform"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in-out duration-300 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full"
                     class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg"
                     x-cloak>
                    @include('layouts.user-sidebar')
                </aside>
            @endauth

            <main class="flex-1" @auth :class="sidebarOpen ? 'lg:ml-64' : ''" @endauth>
                <div class="p-4 sm:p-6 overflow-x-auto">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @include('partials.billing-subscription-modal')

    <script>
        // Pass user data to frontend for permission checks
        @auth
        @php
            $userPermissions = [];
            foreach (auth()->user()->roles as $role) {
                $rolePerms = $role->permissions()->pluck('slug')->toArray();
                $userPermissions = array_merge($userPermissions, $rolePerms);
            }
            $userPermissions = array_unique($userPermissions);
            
            // Get tenant-specific approval pipeline role-stage mapping
            $roleStageMap = \App\Services\ApprovalPipelineService::getTenantRoleStageMap(auth()->user()->tenant_id);
            
            // Check if user has disbursement permission via RBAC roles or role column
            $hasDisburseRole = false;
            $disburseRoles = ['admin', 'administrator', 'teller', 'accountant', 'cashier', 'loan_officer', 'manager', 'gm', 'cso', 'credit_officer'];
            
            // Check role column (case-insensitive)
            if (in_array(strtolower(auth()->user()->role), $disburseRoles)) {
                $hasDisburseRole = true;
            }
            
            // Check RBAC roles
            foreach (auth()->user()->roles as $role) {
                if (in_array(strtolower($role->slug), $disburseRoles)) {
                    $hasDisburseRole = true;
                    break;
                }
            }
            
            // Collect RBAC role slugs for this user (used by canDecide on loan approval)
            $userRbacSlugs = auth()->user()->roles->pluck('slug')->map(fn($s) => strtolower(trim($s)))->values()->toArray();

            $appUserData = [
                'id' => auth()->id(),
                'role' => auth()->user()->role,
                'roleSlugs' => $userRbacSlugs,
                'permissions' => $userPermissions,
                'canDisburse' => auth()->user()->hasPermission('loan.disburse') || auth()->user()->isAdmin() || $hasDisburseRole,
                'approvalStageMap' => $roleStageMap
            ];
        @endphp
        window.AppUser = @json($appUserData);
        @else
        window.AppUser = null;
        @endauth

        (function() {
            const shouldOpen = {{ session('open_billing_modal') ? 'true' : 'false' }};
            if (shouldOpen) {
                window.dispatchEvent(new CustomEvent('open-billing-modal'));
            }
        })();
    </script>

    @stack('scripts')

    @auth
    <!-- Idle Session Timeout: warn at 28 min, logout at 30 min -->
    <div id="idle-warning-modal"
         style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.55);align-items:center;justify-content:center;"
         class="flex">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center">
            <div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Session Expiring Soon</h3>
            <p class="text-sm text-gray-500 mb-1">You have been idle. You will be logged out in</p>
            <p class="text-3xl font-bold text-amber-600 mb-5" id="idle-countdown">2:00</p>
            <button onclick="window.idleTimer.reset()"
                    class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
                Stay Logged In
            </button>
        </div>
    </div>

    <script>
    window.idleTimer = (function() {
        const IDLE_LIMIT    = 30 * 60;   // 30 minutes in seconds
        const WARN_BEFORE   = 2  * 60;   // show warning 2 minutes before
        const WARN_AT       = IDLE_LIMIT - WARN_BEFORE;

        let idleSeconds  = 0;
        let warnInterval = null;
        let warnSecondsLeft = WARN_BEFORE;
        const modal      = document.getElementById('idle-warning-modal');
        const countdownEl= document.getElementById('idle-countdown');

        function fmt(s) {
            const m = Math.floor(s / 60);
            const sec = s % 60;
            return m + ':' + String(sec).padStart(2, '0');
        }

        function showWarning() {
            warnSecondsLeft = WARN_BEFORE;
            modal.style.display = 'flex';
            warnInterval = setInterval(function() {
                warnSecondsLeft--;
                countdownEl.textContent = fmt(warnSecondsLeft);
                if (warnSecondsLeft <= 0) {
                    doLogout();
                }
            }, 1000);
        }

        function hideWarning() {
            modal.style.display = 'none';
            clearInterval(warnInterval);
        }

        function doLogout() {
            hideWarning();
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("logout") }}';
            const csrf = document.createElement('input');
            csrf.type  = 'hidden';
            csrf.name  = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }

        function reset() {
            idleSeconds = 0;
            hideWarning();
        }

        // Tick every second
        setInterval(function() {
            idleSeconds++;
            if (idleSeconds === WARN_AT && modal.style.display === 'none') {
                showWarning();
            }
            if (idleSeconds >= IDLE_LIMIT) {
                doLogout();
            }
        }, 1000);

        // Reset on any user activity
        ['mousemove','mousedown','keydown','touchstart','scroll','click'].forEach(function(evt) {
            document.addEventListener(evt, function() {
                if (modal.style.display === 'none') {
                    idleSeconds = 0;
                }
            }, { passive: true });
        });

        return { reset: reset };
    })();
    </script>
    @endauth
</body>
</html>