@extends('layouts.public')

@section('title', 'Together Financial Services — Microfinance Management System')
@section('description', 'Together Financial Services provides a complete microfinance management solution for institutions across Tanzania. Manage loans, borrowers, repayments, and reporting with confidence.')

@section('content')
<!-- Hero Section -->
<section class="relative overflow-hidden bg-gradient-to-br from-green-900 via-green-800 to-emerald-700" id="hero-section">
    <!-- Subtle grid pattern overlay -->
    <div class="absolute inset-0 opacity-10" style="background-image:url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32 z-10">
        <div class="max-w-3xl mx-auto text-center">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 text-green-100 text-sm font-medium px-4 py-2 rounded-full mb-8">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Microfinance Management System — Tanzania
            </div>

            <!-- Headline -->
            <h1 class="text-4xl lg:text-6xl font-extrabold text-white leading-tight mb-6">
                Together Financial<br>
                <span class="text-emerald-300">Services</span>
            </h1>
            <p class="text-lg lg:text-xl text-green-100 leading-relaxed mb-10 max-w-2xl mx-auto">
                A complete, secure platform for managing borrowers, loans, repayments, accounting, and SMS communications — built for microfinance institutions across Tanzania.
            </p>

            <!-- CTA -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @guest
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center gap-2 bg-white text-green-800 px-8 py-4 rounded-xl text-base font-bold shadow-lg hover:bg-green-50 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Sign In to Your Account
                    </a>
                @else
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center justify-center gap-2 bg-white text-green-800 px-8 py-4 rounded-xl text-base font-bold shadow-lg hover:bg-green-50 transition-all">
                        Go to Dashboard &rarr;
                    </a>
                @endguest
            </div>
        </div>
    </div>

    <!-- Wave divider -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 80L80 66.7C160 53 320 27 480 20C640 13 800 27 960 40C1120 53 1280 67 1360 73.3L1440 80V80H0Z" fill="white"/>
        </svg>
    </div>
</section>

<!-- Features -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <span class="inline-block bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wide mb-3">Platform Features</span>
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900">Everything You Need to Run Your Institution</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @php
            $features = [
                ['icon'=>'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z','title'=>'Borrower Management','desc'=>'Manage borrower profiles, groups, guarantors and documents in one place.','color'=>'green'],
                ['icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z','title'=>'Loan Processing','desc'=>'Full loan lifecycle: application, multi-stage approval, disbursement, and tracking.','color'=>'emerald'],
                ['icon'=>'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z','title'=>'Repayment Tracking','desc'=>'Record payments, track schedules, and monitor arrears in real time.','color'=>'teal'],
                ['icon'=>'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z','title'=>'Financial Reports','desc'=>'Portfolio reports, arrears aging, profit & loss, and balance sheets.','color'=>'green'],
                ['icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4','title'=>'Multi-Branch Support','desc'=>'Manage multiple branches with user access control per branch.','color'=>'emerald'],
                ['icon'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z','title'=>'SMS Notifications','desc'=>'Automated and manual SMS alerts to borrowers via Beem Africa.','color'=>'teal'],
            ];
            @endphp
            @foreach($features as $f)
            <div class="group bg-gray-50 hover:bg-green-50 border border-gray-200 hover:border-green-200 p-7 rounded-2xl transition-all duration-200">
                <div class="w-12 h-12 bg-{{ $f['color'] }}-100 rounded-xl flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-{{ $f['color'] }}-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $f['icon'] }}"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $f['title'] }}</h3>
                <p class="text-sm text-gray-600 leading-relaxed">{{ $f['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Loan Types -->
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <span class="inline-block bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wide mb-3">Loan Products</span>
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900">Types of Loans We Support</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white border border-green-100 p-8 rounded-2xl shadow-sm hover:shadow-md transition text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-5 mx-auto">
                    <svg class="w-8 h-8 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Individual Loans</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Personal financing tailored for individual borrowers with flexible repayment schedules.</p>
            </div>
            <div class="bg-white border border-emerald-100 p-8 rounded-2xl shadow-sm hover:shadow-md transition text-center">
                <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mb-5 mx-auto">
                    <svg class="w-8 h-8 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Group Loans</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Community-based lending for groups with shared responsibility and guaranteed access to credit.</p>
            </div>
            <div class="bg-white border border-teal-100 p-8 rounded-2xl shadow-sm hover:shadow-md transition text-center">
                <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mb-5 mx-auto">
                    <svg class="w-8 h-8 text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Staff Loans</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Exclusive products for employees with payroll-based repayment and priority processing.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Banner -->
<section class="py-16 bg-gradient-to-r from-green-800 to-emerald-700">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">Ready to Manage Your Institution?</h2>
        <p class="text-green-100 mb-8">Sign in to access your dashboard and start managing borrowers, loans, and reports.</p>
        @guest
        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 bg-white text-green-800 px-8 py-4 rounded-xl font-bold shadow-lg hover:bg-green-50 transition">
            Sign In &rarr;
        </a>
        @else
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 bg-white text-green-800 px-8 py-4 rounded-xl font-bold shadow-lg hover:bg-green-50 transition">
            Go to Dashboard &rarr;
        </a>
        @endguest
    </div>
</section>

@endsection