<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /**
     * Show admin settings page (Selcom credentials).
     */
    public function index()
    {
        $this->authorizeAdmin();

        $settings = [
            'selcom_merchant_id' => $this->getSetting('selcom_merchant_id', ''),
            'selcom_api_key' => $this->getSetting('selcom_api_key', ''),
            'selcom_api_secret' => $this->getSetting('selcom_api_secret', ''),
            'selcom_base_url' => $this->getSetting('selcom_base_url', 'https://api.selcom.net'),
            'selcom_webhook_secret' => $this->getSetting('selcom_webhook_secret', ''),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update Selcom credentials.
     */
    public function update(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'selcom_merchant_id' => ['required','string','max:120'],
            'selcom_api_key' => ['required','string','max:255'],
            'selcom_api_secret' => ['required','string','max:255'],
            'selcom_base_url' => ['required','url'],
            'selcom_webhook_secret' => ['nullable','string','max:255'],
        ]);

        foreach ($validated as $key => $value) {
            $this->setSetting($key, $value);
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Selcom API credentials saved.');
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    private function getSetting(string $key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($default) {
            return $default;
        });
    }

    private function setSetting(string $key, $value): void
    {
        Cache::put("setting_{$key}", $value, 3600);

        activity()
            ->causedBy(Auth::user())
            ->withProperties(['key' => $key])
            ->log('Admin setting updated');
    }
}