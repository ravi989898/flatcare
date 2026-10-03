<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Registers the Society portal's new "Permissions" sidebar entry (where
     * a Society Admin decides which of Treasurer/Vice Chairman/Secretary/
     * Committee Member/Resident see which menu items for their own
     * society - see society_role_menu_item). Admin-only, like Admins/
     * Blocks/Security (EnsureMenuItemVisible::ADMIN_ONLY_KEYS), so it's
     * seeded visible only for super_admin/admin and placed right after
     * Complaints in the sidebar.
     */
    public function up(): void
    {
        DB::table('menu_items')->insertOrIgnore([
            'key' => 'permissions',
            'label' => 'Permissions',
            'route_name' => 'society.permissions.index',
            'icon' => 'bi-shield-check',
            'display_order' => 11,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('menu_items')->where('key', 'directory')->update(['display_order' => 12]);
        DB::table('menu_items')->where('key', 'announcements')->update(['display_order' => 13]);
        DB::table('menu_items')->where('key', 'events')->update(['display_order' => 14]);
        DB::table('menu_items')->where('key', 'elections')->update(['display_order' => 15]);
        DB::table('menu_items')->where('key', 'documents')->update(['display_order' => 16]);
        DB::table('menu_items')->where('key', 'emergency-contacts')->update(['display_order' => 17]);
        DB::table('menu_items')->where('key', 'polls')->update(['display_order' => 18]);
        DB::table('menu_items')->where('key', 'service-providers')->update(['display_order' => 19]);

        $menuItemId = DB::table('menu_items')->where('key', 'permissions')->value('id');
        $roleIds = DB::table('role_definitions')->whereIn('name', ['super_admin', 'admin'])->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_menu_item')->insertOrIgnore([
                'role_definition_id' => $roleId,
                'menu_item_id' => $menuItemId,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally no-op, matching the other add_*_menu_item migrations.
    }
};
