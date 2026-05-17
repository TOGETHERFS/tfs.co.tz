@extends('layouts.admin')

@section('title', 'Balance Sheet')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Balance Sheet</h1>
            <p class="mt-1 text-sm text-gray-500">Financial position overview</p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="text-gray-600 hover:text-gray-900">Back to Reports</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Assets -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Assets</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Cash</span>
                    <span class="font-medium">TZS {{ number_format($data['assets']['cash'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Loans Receivable</span>
                    <span class="font-medium">TZS {{ number_format($data['assets']['loans_receivable'] ?? 0) }}</span>
                </div>
                <div class="border-t pt-3 flex justify-between font-semibold">
                    <span>Total Assets</span>
                    <span>TZS {{ number_format($data['assets']['total'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Liabilities -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Liabilities</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Deposits</span>
                    <span class="font-medium">TZS {{ number_format($data['liabilities']['deposits'] ?? 0) }}</span>
                </div>
                <div class="border-t pt-3 flex justify-between font-semibold">
                    <span>Total Liabilities</span>
                    <span>TZS {{ number_format($data['liabilities']['total'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Equity -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Equity</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Retained Earnings</span>
                    <span class="font-medium">TZS {{ number_format($data['equity']['retained_earnings'] ?? 0) }}</span>
                </div>
                <div class="border-t pt-3 flex justify-between font-semibold">
                    <span>Total Equity</span>
                    <span>TZS {{ number_format($data['equity']['total'] ?? 0) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
