<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Loan;
use App\Models\User;
use App\Policies\LoanPolicy;
use App\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Loan::class => LoanPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        Gate::define('manage-settings', function ($user) {
            return $user->hasPermission('manage-settings');
        });

        Gate::define('manage-billing', function ($user) {
            return $user->hasPermission('manage-billing');
        });

        Gate::define('manage-loan-products', function ($user) {
            return $user->hasPermission('manage-loan-products');
        });
    }
}