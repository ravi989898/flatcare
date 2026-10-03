<?php

namespace App\Http\Middleware;

use App\Models\AppMenuItem;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards an /api/v1/* route group behind an App Permission item, e.g.
 * Route::middleware('app.menu:app-water-readings'). The Society Admin always
 * passes; any other user passes only if one of their roles has been granted
 * that item for their society (Settings -> Permissions -> App Permission).
 */
class EnsureAppMenuAccess
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        $user = Auth::guard('society')->user();
        $society = $request->attributes->get('api_society');

        if (!$user || !in_array($key, AppMenuItem::visibleKeysForUser($user, $society?->id), true)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this feature.',
            ], 403);
        }

        return $next($request);
    }
}
