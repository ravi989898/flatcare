<?php

namespace App\Providers;

use App\Helpers\MenuBuilder;
use App\Models\PlatformSetting;
use Illuminate\Support\ServiceProvider;

class AdminMenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Skip during console commands
        if ($this->app->runningInConsole()) {
            return;
        }

        $this->registerModuleMenuItems();
        $this->registerBranding();
    }

    /**
     * Point the AdminLTE layout's logo at the super-admin-configured
     * platform logo, if one has been set.
     */
    private function registerBranding(): void
    {
        try {
            if (!$this->app['db']->connection()->getPdo()) {
                return;
            }

            $logoUrl = PlatformSetting::current()->logoUrl();

            if ($logoUrl) {
                config([
                    'adminlte.logo_img' => $logoUrl,
                    'adminlte.logo_img_alt' => 'FlatCare',
                    // AdminLTELogo.png ships as a round icon; a rectangular
                    // uploaded logo shouldn't be forced into that same
                    // circular crop.
                    'adminlte.logo_img_class' => 'brand-image',
                ]);
            }
        } catch (\Throwable $e) {
            // Silently fail if database is not accessible — default
            // AdminLTE logo config from config/adminlte.php is used.
        }
    }

    /**
     * Register module menu items dynamically
     */
    private function registerModuleMenuItems(): void
    {
        try {
            // Only proceed if database connection exists
            if (!$this->app['db']->connection()->getPdo()) {
                return;
            }

            // Per-role visibility for these items is applied later, at
            // actual render time, by App\Menu\Filters\RoleMenuVisibilityFilter
            // (registered in config/adminlte.php) — this boot() method runs
            // during application bootstrapping, before the 'auth' middleware
            // has resolved a user, so auth()->user() is never available here.
            $moduleItems = MenuBuilder::getModuleMenuItems();

            // Get existing menu from config
            $menu = config('adminlte.menu', []);
            
            // Add each module as a menu item
            if (!empty($moduleItems)) {
                $menu = array_merge($menu, $moduleItems);
            }
            
            // Add admin section at the end. (No 'can' gate here — everything
            // under /admin already requires the 'admin' middleware, i.e. is
            // already super-admin-only.)
            $menu[] = [
                'header' => 'ADMINISTRATION',
            ];

            $menu[] = [
                'text' => 'Societies',
                'route' => 'admin.societies.index',
                'icon' => 'fas fa-fw fa-building',
            ];

            $menu[] = [
                'text' => 'Super Admins',
                'route' => 'admin.super_admins.index',
                'icon' => 'fas fa-fw fa-user-shield',
            ];

            $menu[] = [
                'text' => 'Audit Logs',
                'route' => 'admin.audit_logs.index',
                'icon' => 'fas fa-fw fa-history',
            ];

            // Settings — super-admin-only configuration screens.
            $menu[] = [
                'text' => 'Settings',
                'icon' => 'fas fa-fw fa-cogs',
                'submenu' => [
                    [
                        'text' => 'Roles',
                        'route' => 'admin.settings.roles.index',
                        'icon' => 'fas fa-fw fa-user-tag',
                    ],
                    [
                        'text' => 'Menu Settings',
                        'route' => 'admin.settings.menu.edit',
                        'icon' => 'fas fa-fw fa-bars',
                    ],
                    [
                        'text' => 'Dashboard Widgets',
                        'route' => 'admin.settings.dashboard_widgets.edit',
                        'icon' => 'fas fa-fw fa-th-large',
                    ],
                    [
                        'text' => 'Branding',
                        'route' => 'admin.settings.branding.edit',
                        'icon' => 'fas fa-fw fa-image',
                    ],
                ],
            ];

            // Update config
            config(['adminlte.menu' => $menu]);
        } catch (\Throwable $e) {
            // Silently fail if database is not accessible
            // Default menu from config will be used
        }
    }
}
