<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The Maintenance Requests feature (society.maintenance.* routes,
     * MaintenanceController, MaintenanceRequest model) has been removed
     * entirely. Deleting the menu_items row also cascades to every
     * role_menu_item pivot row for it (see
     * 2026_08_20_000002_create_role_menu_settings_tables's
     * ->cascadeOnDelete()), so no separate pivot cleanup is needed.
     */
    public function up(): void
    {
        DB::table('menu_items')->where('key', 'maintenance')->delete();
    }

    public function down(): void
    {
        DB::table('menu_items')->insertOrIgnore([
            'key' => 'maintenance',
            'label' => 'Maintenance',
            'route_name' => 'society.maintenance.index',
            'icon' => 'bi-tools',
            'display_order' => 18,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
