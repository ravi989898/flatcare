<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * App Permission (see 2026_10_03_000004_create_app_menu_item_tables) was
 * seeded with every item from the mobile home screen's menu groups, but the
 * society hasn't decided yet which of those should actually be configurable
 * per role — only Water Readings and Payment Status (the "Society Admin"
 * group) are decided so far. Removes the rest for now; re-add them (same
 * insertOrIgnore pattern) once the rest are decided. Safe to prune outright:
 * society_role_app_menu_item has no rows yet (App Permission shipped as a
 * blank placeholder page, so nothing could have been configured).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('app_menu_items')
            ->whereNotIn('key', ['app-water-readings', 'app-payment-status'])
            ->delete();
    }

    public function down(): void
    {
        // Intentionally no-op - the pruned rows are reseeded by re-running
        // 2026_10_03_000004_create_app_menu_item_tables's insertOrIgnore data
        // if they're ever needed again, same as other add_*_menu_item migrations.
    }
};
