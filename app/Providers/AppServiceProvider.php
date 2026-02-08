<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Define role-based gates
        Gate::define('admin', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('accounting', function ($user) {
            return $user->hasAnyRole(['admin', 'accounting']);
        });

        Gate::define('sales', function ($user) {
            return $user->hasAnyRole(['admin', 'accounting', 'sales']);
        });

        // Permission gates
        Gate::define('manage-settings', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('manage-payments', function ($user) {
            return $user->hasAnyRole(['admin', 'accounting']);
        });

        Gate::define('send-reminders', function ($user) {
            return $user->hasAnyRole(['admin', 'accounting']);
        });

        Gate::define('view-reports', function ($user) {
            return $user->hasAnyRole(['admin', 'accounting']);
        });
    }
}
