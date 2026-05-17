@php
    $currentLocale = app()->getLocale();
    $languages = [
        'sw' => ['name' => 'Kiswahili', 'flag' => '🇹🇿', 'short' => 'SW'],
        'en' => ['name' => 'English',   'flag' => '🇬🇧', 'short' => 'EN'],
        'fr' => ['name' => 'Français',  'flag' => '🇫🇷', 'short' => 'FR'],
        'ar' => ['name' => 'العربية',   'flag' => '🇸🇦', 'short' => 'AR'],
    ];
    $current = $languages[$currentLocale] ?? $languages['sw'];
@endphp

<div class="relative" x-data="{ open: false }">
    <button @click="open = !open"
            class="flex items-center space-x-1.5 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-md hover:bg-gray-100 focus:outline-none border border-gray-200 bg-white">
        <span class="text-base leading-none">{{ $current['flag'] }}</span>
        <span class="hidden sm:inline text-sm">{{ $current['name'] }}</span>
        <span class="sm:hidden text-xs font-bold">{{ $current['short'] }}</span>
        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div x-show="open"
         @click.away="open = false"
         x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50 py-1">

        <div class="px-3 py-1.5 border-b border-gray-100">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Chagua Lugha / Language</p>
        </div>

        @foreach($languages as $code => $lang)
        <a href="{{ route('language.switch', $code) }}"
           class="flex items-center justify-between px-3 py-2.5 text-sm transition-colors
                  {{ $currentLocale === $code ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
            <div class="flex items-center space-x-2.5">
                <span class="text-base leading-none">{{ $lang['flag'] }}</span>
                <span>{{ $lang['name'] }}</span>
            </div>
            @if($currentLocale === $code)
                <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
            @endif
        </a>
        @endforeach
    </div>
</div>
