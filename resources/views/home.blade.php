@extends('layouts.public')

@section('title', 'Together Financial Services - Professional Microfinance Management System')
@section('description', 'Streamline your microfinance operations with Together Financial Services. Manage loans, clients, repayments, and reporting efficiently. Trusted by institutions across Tanzania.')

@section('content')
<!-- Hero Section -->
<section class="bg-gradient-to-r from-pink-600 via-rose-500 to-pink-700 relative overflow-hidden" id="hero-section">
    <div class="absolute inset-0 bg-black opacity-10"></div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24 z-10">
        <div class="flex flex-col items-center">
            <!-- Main Hero Content -->
            <div class="w-full max-w-3xl text-center">
                <div class="flex flex-col sm:flex-row gap-4 justify-center mt-4">
                    @guest
                        <a href="{{ route('login') }}" class="bg-yellow-400 text-gray-900 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-yellow-300 transition-colors">
                            {{ __('messages.login') }}
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="bg-yellow-400 text-gray-900 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-yellow-300 transition-colors">
                            {{ __('messages.go_to_dashboard') }}
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hero Background Pattern -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="white"/>
        </svg>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                {{ __('messages.features_title') }}
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                {{ __('messages.features_subtitle') }}
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Client Management -->
            <div class="bg-white p-8 rounded-xl shadow-sm card-hover">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">{{ __('messages.client_management') }}</h3>
            </div>
            
            <!-- Loan Processing -->
            <div class="bg-white p-8 rounded-xl shadow-sm card-hover">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">{{ __('messages.loan_processing') }}</h3>
            </div>
            
            <!-- Repayment Tracking -->
            <div class="bg-white p-8 rounded-xl shadow-sm card-hover">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">{{ __('messages.repayment_tracking') }}</h3>
            </div>
            
            <!-- Financial Reporting -->
            <div class="bg-white p-8 rounded-xl shadow-sm card-hover">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">{{ __('messages.financial_reporting') }}</h3>
            </div>
            
            <!-- Branch Management -->
            <div class="bg-white p-8 rounded-xl shadow-sm card-hover">
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">{{ __('messages.multi_branch_support') }}</h3>
            </div>
            
            <!-- SMS Integration -->
            <div class="bg-white p-8 rounded-xl shadow-sm card-hover">
                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">{{ __('messages.sms_notifications') }}</h3>
            </div>
        </div>
    </div>
</section>

<!-- Loan Types Section -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Types of Loans We Offer</h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Flexible financing solutions designed to meet the needs of every borrower.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Individual Loans -->
            <div class="bg-pink-50 border border-pink-100 p-8 rounded-2xl shadow-sm card-hover text-center">
                <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <svg class="w-8 h-8 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Individual Loans</h3>
                <p class="text-gray-600 leading-relaxed">Personal financing tailored for individual borrowers. Quick approval, flexible repayment schedules, and competitive interest rates to help you achieve your goals.</p>
            </div>
            <!-- Group Loans -->
            <div class="bg-rose-50 border border-rose-100 p-8 rounded-2xl shadow-sm card-hover text-center">
                <div class="w-16 h-16 bg-rose-100 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <svg class="w-8 h-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Group Loans</h3>
                <p class="text-gray-600 leading-relaxed">Community-based lending for groups of borrowers who guarantee each other. Stronger together — shared responsibility with lower risk and better access to credit.</p>
            </div>
            <!-- Staff Loans -->
            <div class="bg-fuchsia-50 border border-fuchsia-100 p-8 rounded-2xl shadow-sm card-hover text-center">
                <div class="w-16 h-16 bg-fuchsia-100 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <svg class="w-8 h-8 text-fuchsia-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Staff Loans</h3>
                <p class="text-gray-600 leading-relaxed">Exclusive loan products for employees with salary-based repayment. Convenient deductions from payroll, higher limits, and priority processing for staff members.</p>
            </div>
        </div>
    </div>
</section>

@endsection