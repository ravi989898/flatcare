<?php

use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\EnsureMenuItemVisible;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SanitizeHtmlInput;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetSocietyContext;
use App\Support\SecurityLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        $middleware->append(SanitizeHtmlInput::class);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'society.context' => SetSocietyContext::class,
            'menu.visible' => EnsureMenuItemVisible::class,
            'api.auth' => AuthenticateApiToken::class,
            'role' => EnsureUserHasRole::class,
        ]);

        // X-Forwarded-* headers are only honoured from these proxies. '*' is
        // fine for local/WAMP, but on a server reachable directly (or behind
        // a known load balancer) set TRUSTED_PROXIES to its IP(s), comma
        // separated — otherwise a client can spoof its IP and slip past the
        // per-IP login/API rate limits.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES') ? array_map('trim', explode(',', env('TRUSTED_PROXIES'))) : '*');

        // Force HTTPS scheme in generated URLs once the app is deployed behind TLS.
        // $middleware->trustHosts(at: fn () => config('app.url'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Security monitoring. These are expected, user-facing outcomes that
        // Laravel deliberately never *reports*, so they are recorded from a
        // render callback (which returns null, leaving the normal response
        // untouched). A burst of them from one client is what an attack
        // looks like.
        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e) {
            SecurityLog::warning('authz.denied', ['api_token_id' => request()->attributes->get('api_token')?->id]);
        });

        $exceptions->render(function (ThrottleRequestsException $e) {
            SecurityLog::warning('rate_limit.exceeded');
        });

        $exceptions->render(function (TokenMismatchException $e) {
            SecurityLog::warning('csrf.token_mismatch');
        });

        $exceptions->render(function (InvalidSignatureException $e) {
            SecurityLog::warning('signature.invalid');
        });

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
