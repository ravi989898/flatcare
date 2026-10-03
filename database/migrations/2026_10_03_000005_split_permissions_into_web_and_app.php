<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Turns the Society-portal sidebar's single "Permissions" entry into a
 * parent group with two children - "Web Permission" (the existing page,
 * unchanged) and "App Permission" (new: controls the mobile app's own
 * navigation instead of the web sidebar) - via RoleMenuSettingSeeder's
 * 'web-permission'/'app-permission' catalog rows, following the same
 * seed-from-migration pattern as 2026_10_02_000002_add_payment_report_menu_item.
 * Super Admin and Society Admin see both by default (RoleMenuSettingSeeder's
 * applyDefaultVisibility() grants every role in $allKeys to those two roles);
 * every other role stays hidden by default, same as "Permissions" itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\RoleMenuSettingSeeder)->run();
    }

    public function down(): void
    {
        // Intentionally no-op, matching 2026_10_02_000002_add_payment_report_menu_item:
        // only seeds/reorders catalog data that create_role_menu_settings_tables's
        // down() already tears down.
    }
};
