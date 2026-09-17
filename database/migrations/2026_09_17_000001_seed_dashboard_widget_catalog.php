<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * 2026_08_20_000003_create_dashboard_widget_settings_tables created the
     * dashboard_widgets / role_dashboard_widget tables but never populated
     * them — that catalog only ever got seeded by DashboardWidgetSeeder,
     * which `php artisan migrate` alone doesn't run. Any environment
     * deployed via migrate without also running db:seed (e.g. production)
     * ends up with an empty Settings -> Dashboard Widgets screen. Mirrors
     * 2026_09_15_000001_repair_role_menu_settings_tables's fix for the same
     * gap on the sibling Menu Settings feature.
     *
     * Safe to run anywhere: the seeder's firstOrCreate/whereNotIn logic
     * only fills in missing widgets and missing role<->widget rows, never
     * touching visibility choices already made via the settings screen.
     */
    public function up(): void
    {
        (new \Database\Seeders\DashboardWidgetSeeder)->run();
    }

    public function down(): void
    {
        // Intentionally no-op: this migration only backfills catalog/
        // default-visibility data that 2026_08_20_000003_create_dashboard_widget_settings_tables's
        // down() already tears down.
    }
};
