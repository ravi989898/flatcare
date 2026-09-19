<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * Adds the standard hardening headers to every response. These are
     * defense-in-depth: they don't replace input validation/output escaping,
     * but they close off whole classes of browser-side attacks (clickjacking,
     * MIME sniffing, referrer leakage, third-party script/style injection).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '0'); // superseded by CSP; explicitly disabling avoids legacy filter's own XSS bugs
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set(
            'Permissions-Policy',
            'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()'
        );

        // Don't advertise the PHP version to scanners.
        $response->headers->remove('X-Powered-By');
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        $isApi = $request->is('api/*');

        if ($isApi) {
            // JSON only: nothing in an API response should ever be rendered,
            // framed or scripted, and a token-bearing response must never be
            // written to a shared/browser cache.
            $response->headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
            $response->headers->set('Cache-Control', 'no-store');
        } else {
            $csp = [
                "default-src 'self'",
                // No 'unsafe-inline' / 'unsafe-eval': every page script is an
                // external file (public/js). The CDN hosts are for the
                // Bootstrap bundle, which the views load with an SRI hash.
                "script-src 'self' cdn.jsdelivr.net code.jquery.com",
                "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com",
                "img-src 'self' data:",
                "font-src 'self' fonts.gstatic.com cdn.jsdelivr.net",
                "connect-src 'self'",
                "object-src 'none'",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'",
            ];

            // Only assert these once the app is actually served over HTTPS:
            // sending them over plain HTTP local dev would be a lie the
            // browser can't verify (and would break http://127.0.0.1 assets).
            if ($request->secure()) {
                $csp[] = 'upgrade-insecure-requests';
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }

            $response->headers->set('Content-Security-Policy', implode('; ', $csp));

            // Signed-in pages (admin panel + society portal) must not be
            // served from the browser cache after logout via the Back button.
            if ($request->is('admin/*', 'society/*') && ! $response->headers->has('Cache-Control')) {
                $response->headers->set('Cache-Control', 'no-store, private');
            }
        }

        return $response;
    }
}
