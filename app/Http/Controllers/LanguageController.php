<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Switch the application language.
     */
    public static array $supported = [
        'sw' => ['name' => 'Kiswahili', 'flag' => '🇹🇿', 'native' => 'Kiswahili'],
        'en' => ['name' => 'English',   'flag' => '🇬🇧', 'native' => 'English'],
        'fr' => ['name' => 'French',    'flag' => '🇫🇷', 'native' => 'Français'],
        'ar' => ['name' => 'Arabic',    'flag' => '🇸🇦', 'native' => 'العربية'],
    ];

    public function switch(Request $request, $locale)
    {
        if (array_key_exists($locale, self::$supported)) {
            Session::put('locale', $locale);
        }

        return redirect()->back();
    }
}
