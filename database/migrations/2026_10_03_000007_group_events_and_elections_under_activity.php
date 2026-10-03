<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Turns the Society-portal sidebar's standalone "Events" and "Elections"
 * entries into children of a new "Activity" parent group, same pattern as
 * "Reports" -> "Payment Report". RoleMenuSettingSeeder's catalog array
 * already declares this shape, but MenuItem::firstOrCreate() only applies
 * it to a brand-new install - 'events'/'elections' already exist here, so
 * their parent_key/display_order need an explicit update to actually move.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_items')->insertOrIgnore([
            'key' => 'activity',
            'label' => 'Activity',
            'route_name' => 'society.events.index',
            'icon' => 'bi-calendar2-week',
            'display_order' => 14,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('menu_items')->where('key', 'events')->update(['parent_key' => 'activity', 'display_order' => 1]);
        DB::table('menu_items')->where('key', 'elections')->update(['parent_key' => 'activity', 'display_order' => 2]);

        $activityId = DB::table('menu_items')->where('key', 'activity')->value('id');

        // Every role that can already see Events or Elections needs the new
        // parent visible too, or SetSocietyContext::configureAdminlteSidebar
        // won't render the group at all (it only nests children under a
        // parent that itself passed MenuItem::visibleForRole()).
        // super_admin/admin aren't listed here: RoleMenuSettingSeeder's
        // applyDefaultVisibility(), invoked below, grants every new key to
        // both automatically via $allKeys.
        $roleIds = DB::table('role_definitions')
            ->whereIn('name', ['committee_member', 'resident', 'chairman', 'secretary', 'treasurer', 'vice_chairman'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_menu_item')->insertOrIgnore([
                'role_definition_id' => $roleId,
                'menu_item_id' => $activityId,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        (new \Database\Seeders\RoleMenuSettingSeeder)->run();
    }

    public function down(): void
    {
        // Intentionally no-op, matching the other menu-catalog migrations:
        // only reorders/reshapes catalog data that create_role_menu_settings_tables's
        // down() already tears down.
    }
};
