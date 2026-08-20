<?php

namespace App\Menu\Filters;

use App\Models\RoleDefinition;
use Illuminate\Support\Str;
use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;

/**
 * Hides an admin-panel "PLATFORM" module link when the current user's role
 * has switched it off under Settings -> Menu Settings - the same
 * role_menu_item table SetSocietyContext reads to build the Society-portal
 * sidebar (see App\Models\MenuItem, App\Models\RoleDefinition).
 *
 * Unlike a ServiceProvider::boot() hook, AdminLTE runs its configured
 * filters while the menu is actually being rendered - after the 'auth'
 * middleware has resolved the session - so auth()->user() is reliably
 * available here. (An earlier attempt filtered inside
 * AdminMenuServiceProvider::boot(), which runs during application
 * bootstrapping, before any middleware - auth()->user() is always null
 * there, so that version silently did nothing.)
 */
class RoleMenuVisibilityFilter implements FilterInterface
{
    /**
     * 'admin.modules.<key>' route suffix -> menu_items.key. Only routes
     * mapped here are ever hidden; anything else (Societies, Settings, …)
     * is left untouched so Super Admin can never lock themselves out.
     */
    private const MODULE_TO_MENU_KEY = [
        'maintenance' => 'maintenance',
        'visitor' => 'visitors',
        'complaint' => 'complaints',
        'election' => 'elections',
        'announcement' => 'announcements',
        'payment' => 'payments',
        'directory' => 'directory',
        'event' => 'events',
    ];

    public function transform($item)
    {
        $route = $item['route'] ?? null;

        if (!$route || !Str::startsWith($route, 'admin.modules.')) {
            return $item;
        }

        $menuKey = self::MODULE_TO_MENU_KEY[Str::afterLast($route, '.')] ?? null;

        if ($menuKey === null || $this->isVisible($menuKey)) {
            return $item;
        }

        $item['restricted'] = true;

        return $item;
    }

    private function isVisible(string $menuKey): bool
    {
        $roleName = auth()->user()?->role;

        if (!$roleName) {
            return true;
        }

        $role = RoleDefinition::where('name', $roleName)->first();

        // Role not in the catalog, or Menu Settings never touched for it:
        // don't hide anything rather than guessing.
        if (!$role || !$role->menuItems()->exists()) {
            return true;
        }

        return $role->menuItems()
            ->wherePivot('is_visible', true)
            ->where('key', $menuKey)
            ->exists();
    }
}
