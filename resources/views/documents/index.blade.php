@extends('layouts.app')
@section('title', __('messages.documents'))
@section('page-title', __('messages.documents'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.documents') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.documents_desc') }}</p>
        </div>
        <a href="{{ route('documents.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            {{ __('messages.upload_document') }}
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    @php $unread = $received->filter(fn($d) => $d->recipients->where('recipient_id', auth()->id())->where('is_read', false)->count())->count(); @endphp
    <div class="space-y-4">
        {{-- Tabs --}}
        <div class="flex border-b border-gray-200">
            <button onclick="switchTab('received')" id="tab-received"
                    class="px-5 py-2.5 text-sm transition border-b-2 border-violet-600 text-violet-700 font-semibold">
                {{ __('messages.received_documents') }}
                @if($unread > 0)
                <span class="ml-1.5 bg-violet-100 text-violet-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ $unread }}</span>
                @endif
            </button>
            <button onclick="switchTab('sent')" id="tab-sent"
                    class="px-5 py-2.5 text-sm transition text-gray-500 hover:text-gray-700">
                {{ __('messages.sent_documents') }}
                <span class="ml-1.5 bg-gray-100 text-gray-600 text-xs font-medium px-2 py-0.5 rounded-full">{{ $sent->count() }}</span>
            </button>
            @if($isAdmin)
            <button onclick="switchTab('all')" id="tab-all"
                    class="px-5 py-2.5 text-sm transition text-gray-500 hover:text-gray-700">
                {{ __('messages.all_documents') }}
                <span class="ml-1.5 bg-gray-100 text-gray-600 text-xs font-medium px-2 py-0.5 rounded-full">{{ $allDocuments->count() }}</span>
            </button>
            @endif
        </div>

        {{-- Received --}}
        <div id="pane-received" style="display:block;">
            @if($received->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-gray-500 text-sm">{{ __('messages.no_documents_received') }}</p>
            </div>
            @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.document_title') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.from') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.file_size') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.date') }}</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.status') }}</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($received as $doc)
                            @php
                                $myRecipient = $doc->recipients->where('recipient_id', auth()->id())->first();
                                $isRead = $myRecipient?->is_read ?? true;
                            @endphp
                            <tr class="{{ !$isRead ? 'bg-violet-50/40' : 'hover:bg-gray-50' }} transition-colors">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        @if(!$isRead)
                                        <span class="w-2 h-2 bg-violet-500 rounded-full flex-shrink-0"></span>
                                        @endif
                                        <div>
                                            <div class="font-medium text-gray-900 {{ !$isRead ? 'font-semibold' : '' }}">{{ $doc->title }}</div>
                                            @if($doc->description)
                                            <div class="text-xs text-gray-400 truncate max-w-xs">{{ $doc->description }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-gray-700">{{ optional($doc->uploader)->name ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-500 text-xs">{{ $doc->file_size ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-500 text-xs">{{ $doc->created_at->format('d M Y') }}</td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $isRead ? 'bg-gray-100 text-gray-600' : 'bg-violet-100 text-violet-700' }}">
                                        {{ $isRead ? __('messages.read') : __('messages.unread') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('documents.show', $doc) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                                            {{ __('messages.view') }}
                                        </a>
                                        <a href="{{ route('documents.download', $doc) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-violet-50 hover:bg-violet-100 text-violet-700 rounded-lg transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            {{ __('messages.download') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- Sent --}}
        <div id="pane-sent" style="display:none;">
            @if($sent->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 py-16 text-center">
                <p class="text-gray-500 text-sm">{{ __('messages.no_documents_sent') }}</p>
            </div>
            @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.document_title') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.recipients') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.file_size') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.date') }}</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($sent as $doc)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-gray-900">{{ $doc->title }}</div>
                                    @if($doc->description)
                                    <div class="text-xs text-gray-400 truncate max-w-xs">{{ $doc->description }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($doc->recipientUsers->take(3) as $r)
                                        <span class="inline-flex px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">{{ $r->name }}</span>
                                        @endforeach
                                        @if($doc->recipientUsers->count() > 3)
                                        <span class="inline-flex px-2 py-0.5 text-xs bg-gray-100 text-gray-500 rounded-full">+{{ $doc->recipientUsers->count() - 3 }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-gray-500 text-xs">{{ $doc->file_size ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-500 text-xs">{{ $doc->created_at->format('d M Y') }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('documents.show', $doc) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                                            {{ __('messages.view') }}
                                        </a>
                                        <form method="POST" action="{{ route('documents.destroy', $doc) }}"
                                              onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition">
                                                {{ __('messages.delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- All Documents (Admin only) --}}
        @if($isAdmin)
        <div id="pane-all" style="display:none;">
            @if($allDocuments->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 py-16 text-center">
                <p class="text-gray-500 text-sm">{{ __('messages.no_documents_sent') }}</p>
            </div>
            @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.document_title') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.from') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.recipients') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.file_size') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('messages.date') }}</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($allDocuments as $doc)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-gray-900">{{ $doc->title }}</div>
                                    @if($doc->description)
                                    <div class="text-xs text-gray-400 truncate max-w-xs">{{ $doc->description }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-700">{{ optional($doc->uploader)->name ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($doc->recipientUsers->take(3) as $r)
                                        <span class="inline-flex px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">{{ $r->name }}</span>
                                        @endforeach
                                        @if($doc->recipientUsers->count() > 3)
                                        <span class="inline-flex px-2 py-0.5 text-xs bg-gray-100 text-gray-500 rounded-full">+{{ $doc->recipientUsers->count() - 3 }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-gray-500 text-xs">{{ $doc->file_size ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-500 text-xs">{{ $doc->created_at->format('d M Y') }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('documents.show', $doc) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                                            {{ __('messages.view') }}
                                        </a>
                                        <a href="{{ route('documents.download', $doc) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-violet-50 hover:bg-violet-100 text-violet-700 rounded-lg transition">
                                            {{ __('messages.download') }}
                                        </a>
                                        <form method="POST" action="{{ route('documents.destroy', $doc) }}"
                                              onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition">
                                                {{ __('messages.delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>{{-- .space-y-4 --}}
</div>{{-- .space-y-6 --}}

<script>
function switchTab(tab) {
    ['received','sent','all'].forEach(function(t) {
        var pane = document.getElementById('pane-' + t);
        var btn  = document.getElementById('tab-' + t);
        if (!pane || !btn) return;
        var active   = 'px-5 py-2.5 text-sm transition border-b-2 border-violet-600 text-violet-700 font-semibold';
        var inactive = 'px-5 py-2.5 text-sm transition text-gray-500 hover:text-gray-700';
        pane.style.display = (t === tab) ? 'block' : 'none';
        btn.className      = (t === tab) ? active : inactive;
    });
}
</script>
@endsection
