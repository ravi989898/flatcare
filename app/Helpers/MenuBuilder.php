<?php

namespace App\Helpers;

use App\Models\Module;
use Illuminate\Support\Facades\Cache;

class MenuBuilder
{
    /**
     * Build module menu items from database
     * Cached for 1 hour to avoid repeated queries
     */
    public static function getModuleMenuItems(): array
    {
        return Cache::remember('admin_module_menu_items', 3600, function () {
            $modules = Module::where('is_active', true)->orderBy('display_order')->get();
            
            $items = [];
            
            foreach ($modules as $module) {
                $items[] = [
                    'text' => $module->display_name,
                    'route' => 'admin.modules.' . strtolower($module->name),
                    'icon' => $module->icon ?? 'fas fa-fw fa-cube',
                    'badge' => '',
                    'badge_color' => 'success',
                ];
            }
            
            return $items;
        });
    }

    /**
     * Clear cached menu items (call when modules are added/removed)
     */
    public static function clearMenuCache(): void
    {
        Cache::forget('admin_module_menu_items');
    }
}
