@extends('layouts.app')
@section('title', __('messages.upload_document'))
@section('page-title', __('messages.upload_document'))

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('documents.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-semibold text-gray-900">{{ __('messages.upload_document') }}</h1>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="text-sm text-red-700 space-y-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data"
          class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.document_title') }} *</label>
            <input type="text" name="title" value="{{ old('title') }}" required
                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-violet-500 focus:border-violet-500"
                   placeholder="{{ __('messages.document_title_placeholder') }}">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.description') }}</label>
            <textarea name="description" rows="3"
                      class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-violet-500 focus:border-violet-500"
                      placeholder="{{ __('messages.document_desc_placeholder') }}">{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('messages.upload_file') }} *</label>
            <div class="border-2 border-dashed border-gray-300 hover:border-violet-400 rounded-xl p-6 text-center transition"
                 x-data="{ fileName: '' }"
                 @dragover.prevent
                 @drop.prevent="fileName = $event.dataTransfer.files[0]?.name">
                <svg class="mx-auto w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <input type="file" name="file" id="file-input" class="hidden" required
                       @change="fileName = $event.target.files[0]?.name">
                <label for="file-input" class="cursor-pointer text-sm font-medium text-violet-600 hover:text-violet-800">
                    {{ __('messages.choose_file') }}
                </label>
                <p class="text-xs text-gray-400 mt-1" x-text="fileName || '{{ __('messages.no_file_chosen') }}'"></p>
                <p class="text-xs text-gray-400 mt-1">{{ __('messages.max_file_size') }}: 20MB</p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.send_to') }} *</label>
            <p class="text-xs text-gray-500 mb-3">{{ __('messages.send_to_desc') }}</p>
            <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-3">
                @forelse($staff as $member)
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="recipient_ids[]" value="{{ $member->id }}"
                           {{ is_array(old('recipient_ids')) && in_array($member->id, old('recipient_ids')) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                    <div class="flex items-center gap-2 flex-1">
                        <div class="w-7 h-7 rounded-full bg-violet-100 flex items-center justify-center text-xs font-bold text-violet-700 flex-shrink-0">
                            {{ strtoupper(substr($member->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ $member->name }}</div>
                            <div class="text-xs text-gray-400">{{ $member->position ?? $member->role }}</div>
                        </div>
                    </div>
                </label>
                @empty
                <p class="text-sm text-gray-400 text-center py-4">{{ __('messages.no_staff_found') }}</p>
                @endforelse
            </div>
        </div>

        <div style="margin-top:16px;border-top:1px solid #f3f4f6;padding-top:16px;">
            <button type="submit" style="display:block;width:100%;padding:14px;background:#7c3aed;color:#fff;font-weight:700;font-size:15px;border:none;border-radius:8px;cursor:pointer;margin-bottom:10px;">
                {{ __('messages.upload_and_send') }}
            </button>
            <a href="{{ route('documents.index') }}" style="display:block;width:100%;padding:14px;background:#f3f4f6;color:#374151;font-weight:600;font-size:14px;border-radius:8px;text-align:center;text-decoration:none;">
                {{ __('messages.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
