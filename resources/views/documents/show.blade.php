@extends('layouts.app')
@section('title', __('messages.document_details'))
@section('page-title', __('messages.document_details'))

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('documents.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-semibold text-gray-900">{{ __('messages.document_details') }}</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-violet-600 px-6 py-5">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-white/20 rounded-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-xl font-bold text-white">{{ $document->title }}</h2>
                    @if($document->description)
                    <p class="text-sm text-violet-100 mt-1">{{ $document->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-6 space-y-5">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.uploaded_by') }}</p>
                    <p class="font-medium text-gray-800">{{ optional($document->uploader)->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.upload_date') }}</p>
                    <p class="font-medium text-gray-800">{{ $document->created_at->format('d F Y, H:i') }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.file_name') }}</p>
                    <p class="font-medium text-gray-800 truncate">{{ $document->file_name }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('messages.file_size') }}</p>
                    <p class="font-medium text-gray-800">{{ $document->file_size ?? '—' }}</p>
                </div>
            </div>

            {{-- Recipients --}}
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ __('messages.recipients') }}</p>
                <div class="space-y-2">
                    @foreach($document->recipientUsers as $recipient)
                    @php $pivot = $recipient->pivot; @endphp
                    <div class="flex items-center justify-between p-3 rounded-lg {{ $pivot->is_read ? 'bg-gray-50' : 'bg-violet-50 border border-violet-100' }}">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-violet-100 flex items-center justify-center text-xs font-bold text-violet-700 flex-shrink-0">
                                {{ strtoupper(substr($recipient->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $recipient->name }}</p>
                                <p class="text-xs text-gray-400">{{ $recipient->position ?? $recipient->role }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            @if($pivot->is_read)
                            <span class="inline-flex items-center gap-1 text-xs text-green-600 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ __('messages.read') }}
                            </span>
                            @if($pivot->read_at)
                            <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($pivot->read_at)->format('d M Y') }}</p>
                            @endif
                            @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                {{ __('messages.unread') }}
                            </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="px-6 pb-6 flex gap-3">
            <a href="{{ route('documents.download', $document) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                {{ __('messages.download') }}
            </a>
            @if(auth()->id() === $document->uploaded_by || auth()->user()->isAdmin())
            <form method="POST" action="{{ route('documents.destroy', $document) }}"
                  onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-50 hover:bg-red-100 text-red-700 text-sm font-semibold rounded-lg transition">
                    {{ __('messages.delete') }}
                </button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
