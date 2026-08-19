<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * Guards the /admin panel: being logged in is not enough, the account's
     * `role` column must be "admin". Runs after the 'auth' middleware, so
     * $request->user() is always present here.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            abort(403, 'You do not have access to the admin panel.');
        }

        return $next($request);
    }
}
