<?php

namespace App\Providers;

use App\Notifications\Channels\SmsChannel;
use App\Services\BeemSmsService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the Beem SMS Service
        $this->app->singleton(BeemSmsService::class, function ($app) {
            return new BeemSmsService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the SMS notification channel
        Notification::extend('sms', function ($app) {
            return new SmsChannel($app->make(BeemSmsService::class));
        });
    }
}