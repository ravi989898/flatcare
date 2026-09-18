<?php

namespace App\Http\Middleware;

use App\Models\MenuItem;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Settings -> Menu Settings (role_menu_item.is_visible) previously only
 * hid a sidebar link - SetSocietyContext read it to build the menu, but
 * nothing stopped a role from opening the same route directly by URL. This
 * closes that gap: any route whose name falls under a cataloged MenuItem's
 * active_pattern (e.g. 'society.extra-charges.*') 404s unless the current
 * user's role has that menu item switched on. Routes with no matching
 * MenuItem (profile, logout, ajax helpers, ...) are left alone.
 */
class EnsureMenuItemVisible
{
    /**
     * Route-name prefix -> menu_items.key, for routes that belong to a
     * sidebar feature but live under their own prefix rather than the
     * MenuItem's own route_name (e.g. Fee Types management is only reached
     * via a button on the Extra Charges page, never its own sidebar link).
     */
    private const EXTRA_ROUTE_PREFIXES = [
        'society.fee-types' => 'extra-charges',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if (!$routeName) {
            return $next($request);
        }

        $menuItem = $this->resolveMenuItem($routeName);

        if (!$menuItem) {
            return $next($request);
        }

        $roleName = Auth::guard('society')->user()?->roles()->orderByDesc('priority')->value('name');

        $visibleKeys = MenuItem::visibleForRole($roleName)->pluck('key');

        if (!$visibleKeys->contains($menuItem->key)) {
            abort(404);
        }

        return $next($request);
    }

    private function resolveMenuItem(string $routeName): ?MenuItem
    {
        $menuItem = MenuItem::all()->first(
            fn (MenuItem $item) => $routeName === $item->active_pattern
                || Str::startsWith($routeName, $item->active_pattern . '.')
        );

        if ($menuItem) {
            return $menuItem;
        }

        foreach (self::EXTRA_ROUTE_PREFIXES as $prefix => $menuKey) {
            if ($routeName === $prefix || Str::startsWith($routeName, $prefix . '.')) {
                return MenuItem::where('key', $menuKey)->first();
            }
        }

        return null;
    }
}
