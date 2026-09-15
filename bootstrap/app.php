<?php

use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetSocietyContext;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            'role' => EnsureUserHasRole::class,
        ]);

        $middleware->trustProxies(at: '*'); // safe default for local/WAMP; tighten to your LB's IPs in production

        // Force HTTPS scheme in generated URLs once the app is deployed behind TLS.
        // $middleware->trustHosts(at: fn () => config('app.url'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Api\V1\ApiController::fail() responds {success:false, message, errors}
        // for every hand-written failure path; without this, a thrown
        // ValidationException/AuthenticationException instead falls through to
        // Laravel's default {message, errors} shape, so JSON clients (the
        // mobile app) would have to special-case two different error envelopes.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], $e->status);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => null,
                ], 401);
            }
        });
    })->create();
