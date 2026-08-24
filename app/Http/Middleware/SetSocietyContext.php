<?php

namespace App\Http\Middleware;

use App\Models\MenuItem;
use App\Models\Society;
use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetSocietyContext
 *
 * Web counterpart to SetTenantConnection (which is API/Sanctum-oriented).
 * The society portal identifies its tenant by an id stashed in the session
 * at login time (see SocietyAuthController::login).
 *
 * This middleware ALSO performs the 'society' guard's auth check itself,
 * rather than being paired with Laravel's generic 'auth:society' middleware.
 * Laravel sorts every request's middleware (route + global) by a fixed
 * priority list before running them, and \Illuminate\Auth\Middleware\
 * Authenticate sits in that list — so declaring 'auth:society' after this
 * middleware on a route is not reliable: Authenticate can still end up
 * running first and query the tenant User model before the 'society'
 * connection has been pointed at the right database. Doing both jobs in one
 * middleware makes the ordering a plain fact of the code instead of
 * something the framework's sort has to be trusted to preserve.
 */
class SetSocietyContext
{
    public function __construct(
        private TenantService $tenantService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $societyId = $request->session()->get('tenant_society_id');

        if (!$societyId) {
            return redirect()->route('society.login')
                ->with('error', 'Please sign in to continue.');
        }

        $society = Society::find($societyId);

        if (!$society || !$this->tenantService->validateSocietyAccessPeriod($society)) {
            $request->session()->forget('tenant_society_id');

            return redirect()->route('society.login')
                ->with('error', 'Your society access has expired or is inactive. Please contact your platform administrator.');
        }

        $this->tenantService->setTenant($society);

        if (!Auth::guard('society')->check()) {
            return redirect()->route('society.login');
        }

        // The Society portal shares the Super Admin panel's AdminLTE shell
        // (resources/views/society/layout.blade.php extends adminlte::page).
        // AdminLTE's built-in top-bar user menu and logout link always read
        // Auth::user()/Auth::guard() with NO guard argument, so make the
        // default guard resolve to 'society' for the rest of this request.
        Auth::shouldUse('society');

        // Make the current society available to controllers/views without
        // every one of them having to re-resolve it from the session.
        $request->attributes->set('society', $society);
        view()->share('currentSociety', $society);
        $menuItems = $this->menuItemsFor(Auth::guard('society')->user());
        view()->share('visibleMenuItems', $menuItems);
        $this->configureAdminlteSidebar($menuItems);

        return $next($request);
    }

    /**
     * Point the (globally-scoped) AdminLTE config at this request's Society
     * sidebar instead of the Super Admin one. Safe to overwrite outright:
     * config('adminlte.menu') is only ever read when AdminLte::class is
     * resolved while rendering a response for *this* request, and admin
     * routes never run this middleware.
     */
    private function configureAdminlteSidebar($menuItems): void
    {
        $items = $menuItems->map(fn (MenuItem $item) => [
            'text' => $item->label,
            'url' => route($item->route_name),
            'icon' => 'bi ' . $item->icon,
            'active' => request()->routeIs($item->active_pattern) || request()->routeIs($item->active_pattern . '.*'),
        ])->all();

        config([
            'adminlte.menu' => $items,
            'adminlte.logout_url' => 'society.logout',
            'adminlte.dashboard_url' => 'society.dashboard',
            'adminlte.classes_sidebar' => 'sidebar-dark-success elevation-4',
        ]);
    }

    /**
     * The sidebar entries the given tenant user's role is allowed to see,
     * per Settings -> Menu Settings (role_menu_item.is_visible). Falls back
     * to just Dashboard if the user has no role assigned.
     */
    private function menuItemsFor($tenantUser)
    {
        $roleName = $tenantUser?->roles()->orderByDesc('priority')->value('name');

        $items = MenuItem::query()
            ->when($roleName, function ($query) use ($roleName) {
                $query->whereHas('roles', function ($q) use ($roleName) {
                    $q->where('name', $roleName)->where('role_menu_item.is_visible', true);
                });
            }, fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('display_order')
            ->get();

        if ($items->isEmpty()) {
            $items = MenuItem::where('key', 'dashboard')->get();
        }

        return $items;
    }
}
