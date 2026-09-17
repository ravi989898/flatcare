<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Registers the new "Extra Charges" society-portal sidebar entry and
     * gives it default per-role visibility, the same way
     * 2026_09_15_000001_repair_role_menu_settings_tables and
     * 2026_09_17_000001_seed_dashboard_widget_catalog run their seeder from
     * inside a migration — `php artisan migrate` alone never runs
     * database/seeders/*, so this is what actually reaches production.
     * RoleMenuSettingSeeder is idempotent (firstOrCreate + only-attach-if-
     * missing), safe to rerun anywhere.
     *
     * Also re-pins every menu item's display_order to match the ordering
     * Super Admin already set for this deployment (Dashboard, Admins,
     * Payments, Extra Charges, Water Readings, Blocks, ...) — a plain
     * firstOrCreate for the new 'extra-charges' key alone would only ever
     * apply its display_order on environments where the row didn't exist
     * yet, leaving already-provisioned environments with a stale order.
     */
    public function up(): void
    {
        (new \Database\Seeders\RoleMenuSettingSeeder)->run();

        $order = [
            'dashboard' => 1,
            'admins' => 2,
            'payments' => 3,
            'extra-charges' => 4,
            'water-readings' => 5,
            'blocks' => 6,
            'security' => 7,
            'visitors' => 8,
            'complaints' => 9,
            'directory' => 10,
            'announcements' => 11,
            'events' => 12,
            'elections' => 13,
            'documents' => 14,
            'emergency-contacts' => 15,
            'polls' => 16,
            'service-providers' => 17,
            'maintenance' => 18,
        ];

        foreach ($order as $key => $position) {
            DB::table('menu_items')->where('key', $key)->update(['display_order' => $position]);
        }

        // 2026_08_21_000001_add_office_bearer_roles seeded Chairman/Vice
        // Chairman/Secretary/Treasurer with a fixed snapshot of menu items
        // as of that date (mirroring committee_member), so none of them got
        // a row for any menu item added since — including this one.
        // RoleMenuSettingSeeder's $visibleKeysByRole doesn't cover these 4
        // roles at all, so give 'extra-charges' the same hidden default
        // those roles already have for 'payments' (the closest existing
        // item), rather than leaving them with no row for it at all.
        $extraChargesId = DB::table('menu_items')->where('key', 'extra-charges')->value('id');
        $officeBearerRoleIds = DB::table('role_definitions')
            ->whereIn('name', ['chairman', 'vice_chairman', 'secretary', 'treasurer'])
            ->pluck('id');

        foreach ($officeBearerRoleIds as $roleId) {
            DB::table('role_menu_item')->insertOrIgnore([
                'role_definition_id' => $roleId,
                'menu_item_id' => $extraChargesId,
                'is_visible' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally no-op: only backfills/reorders catalog data that
        // 2026_08_20_000002_create_role_menu_settings_tables's down()
        // already tears down.
    }
};
