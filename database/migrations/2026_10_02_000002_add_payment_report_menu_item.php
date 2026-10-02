<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds nested-submenu support to the menu catalog (a `parent_key`
     * pointing at another menu_items.key; SetSocietyContext groups children
     * under their parent when building the sidebar) and registers the new
     * "Reports" parent with a "Payment Report" child under it, following the
     * same seed-from-migration pattern as 2026_09_17_000004_add_extra_charges_menu_item
     * (plain `php artisan migrate` never runs database/seeders/*, so the
     * seeder has to be invoked here to actually reach production).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('menu_items', 'parent_key')) {
            Schema::table('menu_items', function (Blueprint $table) {
                // References another menu_items.key, not a foreign id, so the
                // RoleMenuSettingSeeder's plain key-literal catalog array can
                // keep declaring parentage without needing the parent's
                // auto-increment id up front.
                $table->string('parent_key')->nullable()->after('key');
            });
        }

        (new \Database\Seeders\RoleMenuSettingSeeder)->run();

        DB::table('menu_items')->where('key', 'payment-report')->update(['parent_key' => 'reports']);

        $order = [
            'dashboard' => 1,
            'admins' => 2,
            'payments' => 3,
            'extra-charges' => 4,
            'water-readings' => 5,
            'blocks' => 6,
            'security' => 7,
            'visitors' => 8,
            'reports' => 9,
            'complaints' => 10,
            'directory' => 11,
            'announcements' => 12,
            'events' => 13,
            'elections' => 14,
            'documents' => 15,
            'emergency-contacts' => 16,
            'polls' => 17,
            'service-providers' => 18,
            'maintenance' => 19,
            'payment-report' => 1, // ordered within its own parent, not against top-level siblings
        ];

        foreach ($order as $key => $position) {
            DB::table('menu_items')->where('key', $key)->update(['display_order' => $position]);
        }

        // 2026_08_21_000001_add_office_bearer_roles seeded Chairman/Vice
        // Chairman/Secretary/Treasurer with a fixed snapshot of menu items,
        // so none of them got a row for any menu item added since. The
        // report is financial data, same as Payments — give these 4 roles
        // the same hidden default they already have for 'payments'.
        $reportKeyIds = DB::table('menu_items')->whereIn('key', ['reports', 'payment-report'])->pluck('id');
        $officeBearerRoleIds = DB::table('role_definitions')
            ->whereIn('name', ['chairman', 'vice_chairman', 'secretary', 'treasurer'])
            ->pluck('id');

        foreach ($officeBearerRoleIds as $roleId) {
            foreach ($reportKeyIds as $menuItemId) {
                DB::table('role_menu_item')->insertOrIgnore([
                    'role_definition_id' => $roleId,
                    'menu_item_id' => $menuItemId,
                    'is_visible' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Intentionally no-op, matching 2026_09_17_000004_add_extra_charges_menu_item:
        // only backfills/reorders catalog data that create_role_menu_settings_tables's
        // down() already tears down. The parent_key column is left in place
        // since dropping it would also need to be guarded for repeat-migrate
        // safety and nothing downstream depends on its absence.
    }
};
