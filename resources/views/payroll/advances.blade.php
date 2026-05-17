@extends('layouts.app')
@section('title', __('messages.my_salary_advances'))
@section('page-title', __('messages.my_salary_advances'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.my_salary_advances') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.my_advances_desc') }}</p>
        </div>
        <a href="{{ route('payroll.advance.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('messages.apply_salary_advance') }}
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    @if($advances->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 py-16 text-center">
        <p class="text-gray-500 text-sm">{{ __('messages.no_advance_requests') }}</p>
    </div>
    @else
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.date') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.amount') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.reason') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.status') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.review_note') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($advances as $adv)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-700">{{ $adv->requested_date->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-800">TZS {{ number_format($adv->amount, 0) }}</td>
                        <td class="px-5 py-3 text-gray-600 text-xs max-w-xs truncate">{{ $adv->reason ?? '—' }}</td>
                        <td class="px-5 py-3 text-center">
                            @php $colors = ['pending'=>'bg-yellow-100 text-yellow-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700']; @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$adv->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ __('messages.status_' . $adv->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-500">{{ $adv->review_note ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
