<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\JournalEntryService;
use App\Services\Accounting\AutomatedAccountingService;
use App\Services\Accounting\FinancialReportingService;

class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChartOfAccountsService::class, function ($app) {
            return new ChartOfAccountsService();
        });

        $this->app->singleton(JournalEntryService::class, function ($app) {
            return new JournalEntryService();
        });

        $this->app->singleton(AutomatedAccountingService::class, function ($app) {
            return new AutomatedAccountingService(
                $app->make(JournalEntryService::class)
            );
        });

        $this->app->singleton(FinancialReportingService::class, function ($app) {
            return new FinancialReportingService();
        });
    }

    public function boot(): void
    {
        //
    }
}
