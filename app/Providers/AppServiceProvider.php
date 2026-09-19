<?php

namespace App\Providers;

use App\Services\TenantService;
use App\Support\SecurityLog;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Schema\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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

            // Fail safe: a production box must never render stack traces or
            // environment details, even if APP_DEBUG was left on by mistake.
            if (config('app.debug')) {
                config(['app.debug' => false]);
                Log::channel('security')->critical('APP_DEBUG was true in production; forced off. Fix the .env file.');
            }

            if ((string) config('flatcare.otp_default_code') === '0000') {
                Log::channel('security')->critical(
                    'OTP_DEFAULT_CODE is the publicly-known default "0000" in production: anyone who knows a registered '
                    .'mobile number can sign in as that resident. Connect an SMS OTP gateway (App\Services\Api\OtpService).'
                );
            }
        }

        $this->registerRateLimiters();
        $this->registerSecurityEventLogging();
    }

    /**
     * Per-client ceiling for the token-authenticated mobile API — the auth
     * endpoints already have their own tighter route throttles. Keyed by the
     * bearer token (so one leaked/abused token can't exhaust others) and
     * falling back to the IP.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('api-auth', function (Request $request) {
            $key = $request->bearerToken() ? hash('sha256', $request->bearerToken()) : $request->ip();

            return Limit::perMinute((int) env('API_RATE_LIMIT_PER_MINUTE', 120))->by($key);
        });

        $tokenKey = fn (Request $request) => $request->bearerToken() ? hash('sha256', $request->bearerToken()) : $request->ip();

        // Creating visitors / gate passes / pre-approvals and uploading files
        // are the abuse-prone writes (each can carry a photo): cap them per
        // token, well above any real household's usage.
        RateLimiter::for('visitor-create', fn (Request $request) => Limit::perHour(30)->by('vc:'.$tokenKey($request)));
        RateLimiter::for('guard-visitor-create', fn (Request $request) => Limit::perHour(240)->by('gvc:'.$tokenKey($request)));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perHour(30)->by('up:'.$tokenKey($request)));
        RateLimiter::for('password-change', fn (Request $request) => Limit::perMinute(5)->by('pw:'.$tokenKey($request)));

        // Session-based panels (super-admin + society portal): keyed by the
        // session id and IP so a fresh query string / header can't reset it.
        RateLimiter::for('panel', function (Request $request) {
            $session = $request->hasSession() ? $request->session()->getId() : '';

            return Limit::perMinute(300)->by('panel:'.sha1($session.'|'.$request->ip()));
        });

        // SMS/OTP requests per mobile number, so the OTP endpoint can't be
        // used to spam a resident's phone (or burn SMS credits) from many IPs.
        RateLimiter::for('otp-request', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perHour(10)->by('otp:'.sha1((string) $request->input('mobile_number'))),
            ];
        });
    }

    private function registerSecurityEventLogging(): void
    {
        Event::listen(Lockout::class, function (Lockout $event) {
            SecurityLog::warning('auth.lockout', [
                'email' => (string) $event->request->input('email', ''),
                'mobile' => substr((string) $event->request->input('mobile_number', ''), -4),
            ]);
        });
    }
}
