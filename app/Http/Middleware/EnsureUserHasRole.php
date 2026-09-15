<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts an /api/v1/* route group to a specific role, on top of the
 * AuthenticateApiToken middleware that already populated the 'society'
 * guard — e.g. Route::middleware('role:security') guards the gate-security
 * endpoints (routes/api.php) so a resident's token can't reach them just
 * because it's otherwise valid.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::guard('society')->user();

        if (!$user || !$user->hasRole($role)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this feature.',
            ], 403);
        }

        return $next($request);
    }
}
