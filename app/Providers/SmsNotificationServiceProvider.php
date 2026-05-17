<?php

namespace App\Providers;

use App\Models\SenderId;
use App\Models\SmsTopup;
use App\Models\SmsWallet;
use App\Observers\SenderIdObserver;
use App\Observers\SmsTopupObserver;
use App\Observers\SmsWalletObserver;
use App\Console\Commands\CheckSmsLowBalanceCommand;
use App\Console\Commands\SendDailySmsUsageSummaryCommand;
use App\Console\Commands\SendWeeklySmsUsageReportCommand;
use Illuminate\Support\ServiceProvider;

class SmsNotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register console commands
        $this->commands([
            CheckSmsLowBalanceCommand::class,
            SendDailySmsUsageSummaryCommand::class,
            SendWeeklySmsUsageReportCommand::class,
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register model observers
        SmsWallet::observe(SmsWalletObserver::class);
        SenderId::observe(SenderIdObserver::class);
        SmsTopup::observe(SmsTopupObserver::class);
    }
}