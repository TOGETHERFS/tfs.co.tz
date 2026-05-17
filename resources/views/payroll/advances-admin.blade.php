@extends('layouts.app')
@section('title', __('messages.salary_advance_requests'))
@section('page-title', __('messages.salary_advance_requests'))

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.salary_advance_requests') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.advances_admin_desc') }}</p>
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
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.staff_name') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.date') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('messages.amount') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.reason') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.status') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($advances as $adv)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-xs font-bold text-amber-700 flex-shrink-0">
                                    {{ strtoupper(substr($adv->user->name ?? 'U', 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $adv->user->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ $adv->user->position ?? $adv->user->role }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-gray-700">{{ $adv->requested_date->format('d M Y') }}</td>
                        <td class="px-5 py-4 text-right font-bold text-gray-800">TZS {{ number_format($adv->amount, 0) }}</td>
                        <td class="px-5 py-4 text-gray-600 text-xs max-w-xs">{{ $adv->reason ?? '—' }}</td>
                        <td class="px-5 py-4 text-center">
                            @php $colors = ['pending'=>'bg-yellow-100 text-yellow-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700']; @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$adv->status] ?? '' }}">
                                {{ __('messages.status_' . $adv->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            @if($adv->status === 'pending')
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                                    {{ __('messages.review') }} ▾
                                </button>
                                <div x-show="open" @click.outside="open=false" x-transition
                                     class="absolute right-0 z-20 mt-1 w-72 bg-white rounded-xl shadow-lg border border-gray-200 p-4">
                                    <form method="POST" action="{{ route('payroll.advance.review', $adv) }}" class="space-y-3">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">{{ __('messages.review_note') }}</label>
                                            <textarea name="review_note" rows="2"
                                                      class="w-full rounded-lg border-gray-300 text-xs shadow-sm focus:ring-emerald-500 focus:border-emerald-500"
                                                      placeholder="{{ __('messages.optional_note') }}"></textarea>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="submit" name="action" value="approved"
                                                    class="flex-1 py-2 text-xs font-semibold bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                                                {{ __('messages.approve') }}
                                            </button>
                                            <button type="submit" name="action" value="rejected"
                                                    class="flex-1 py-2 text-xs font-semibold bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                                                {{ __('messages.reject') }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @else
                            <span class="text-xs text-gray-400">{{ __('messages.reviewed') }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
