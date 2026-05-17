@extends('layouts.user')

@section('title', 'Fiscal Years')

@section('content')
<div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Fiscal Years & Periods</h1>
            <a href="{{ route('accounting.fiscal-years.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Fiscal Year
            </a>
        </div>

        @if(session('success'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4">
            <p class="text-green-700">{{ session('success') }}</p>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
            <p class="text-red-700">{{ session('error') }}</p>
        </div>
        @endif

        <div class="space-y-6">
            @forelse($fiscalYears as $fiscalYear)
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">{{ $fiscalYear->name }}</h3>
                        <p class="text-sm text-gray-500">
                            {{ $fiscalYear->start_date->format('M d, Y') }} - {{ $fiscalYear->end_date->format('M d, Y') }}
                        </p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 text-sm font-medium rounded-full {{ $fiscalYear->is_closed ? 'bg-gray-100 text-gray-800' : 'bg-green-100 text-green-800' }}">
                            {{ $fiscalYear->is_closed ? 'Closed' : 'Open' }}
                        </span>
                        @if(!$fiscalYear->is_closed)
                        <form action="{{ route('accounting.fiscal-years.close', $fiscalYear) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800" onclick="return confirm('Close this fiscal year? All periods will be locked.')">
                                Close Year
                            </button>
                        </form>
                        @else
                        <form action="{{ route('accounting.fiscal-years.reopen', $fiscalYear) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-blue-600 hover:text-blue-800" onclick="return confirm('Reopen this fiscal year?')">
                                Reopen
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                <div class="p-6">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Accounting Periods</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        @foreach($fiscalYear->periods as $period)
                        <div class="border rounded-lg p-3 {{ $period->is_closed ? 'bg-gray-50' : 'bg-white' }}">
                            <p class="text-sm font-medium text-gray-900">{{ $period->name }}</p>
                            <p class="text-xs text-gray-500">{{ $period->start_date->format('M d') }} - {{ $period->end_date->format('M d') }}</p>
                            <div class="mt-2 flex justify-between items-center">
                                <span class="text-xs {{ $period->is_closed ? 'text-gray-500' : 'text-green-600' }}">
                                    {{ $period->is_closed ? 'Closed' : 'Open' }}
                                </span>
                                @if(!$fiscalYear->is_closed)
                                    @if(!$period->is_closed)
                                    <form action="{{ route('accounting.fiscal-years.periods.close', $period) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-red-600 hover:text-red-800">Close</button>
                                    </form>
                                    @else
                                    <form action="{{ route('accounting.fiscal-years.periods.reopen', $period) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">Reopen</button>
                                    </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                No fiscal years configured. <a href="{{ route('accounting.fiscal-years.create') }}" class="text-blue-600 hover:underline">Create your first fiscal year</a>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
