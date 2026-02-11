<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quotation;
use App\Observers\ClientObserver;
use App\Observers\InvoiceObserver;
use App\Observers\PaymentObserver;
use App\Observers\QuotationObserver;
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

        // Register observers
        Client::observe(ClientObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Payment::observe(PaymentObserver::class);
        Quotation::observe(QuotationObserver::class);

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
