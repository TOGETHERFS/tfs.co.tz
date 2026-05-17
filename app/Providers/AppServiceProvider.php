<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Only bind a dummy Request in console to satisfy URL generator
        if ($this->app->runningInConsole() && !$this->app->bound('request')) {
            $request = \Illuminate\Http\Request::create(config('app.url', 'http://localhost'), 'GET');
            $this->app->instance(\Illuminate\Http\Request::class, $request);
            $this->app->instance('request', $request);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share general settings across all views and expose sane defaults
        View::composer('*', function ($view) {
            $general = [
                'company_name' => Cache::get('setting_company_name', 'Microfinance Institution'),
                'company_logo' => Cache::get('setting_company_logo', null),
                'currency' => Cache::get('setting_currency', 'TZS'),
                'currency_symbol' => Cache::get('setting_currency_symbol', 'TSHS'),
                'timezone' => Cache::get('setting_timezone', 'UTC'),
                'date_format' => Cache::get('setting_date_format', 'Y-m-d'),
                'locale' => app()->getLocale(),
            ];

            $view->with('generalSettings', $general);
        });
    }
}