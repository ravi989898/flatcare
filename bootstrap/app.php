<?php

use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetSocietyContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'society.context' => SetSocietyContext::class,
            'api.auth' => AuthenticateApiToken::class,
        ]);

        $middleware->trustProxies(at: '*'); // safe default for local/WAMP; tighten to your LB's IPs in production

        // Force HTTPS scheme in generated URLs once the app is deployed behind TLS.
        // $middleware->trustHosts(at: fn () => config('app.url'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
