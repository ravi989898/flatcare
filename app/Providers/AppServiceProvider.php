<?php

namespace App\Providers;

use App\Services\TenantService;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register TenantService as singleton for multi-tenant support
        $this->app->singleton(TenantService::class, function ($app) {
            return new TenantService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Older MySQL/MariaDB setups (utf8mb4 + MyISAM default engine) hit the
        // "key too long" error on unique/indexed string columns. Cap the
        // default string length so migrations stay portable across DB setups.
        Builder::defaultStringLength(191);

        // Force HTTPS-generated URLs when the app sits behind a TLS terminator/proxy.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
