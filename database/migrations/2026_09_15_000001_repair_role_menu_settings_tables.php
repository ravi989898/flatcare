<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repairs environments where 2026_08_20_000002_create_role_menu_settings_tables
     * ran partway (MySQL table creation isn't transactional, so an
     * environment issue partway through that migration could leave
     * role_definitions/menu_items created but role_menu_item missing) yet
     * still got recorded as run, so migrate wouldn't retry it. Also seeds
     * the baseline roles, menu items and default visibility if they're
     * missing, since a table left uncreated meant RoleMenuSettingSeeder's
     * attach step for it never ran either.
     */
    public function up(): void
    {
        if (!Schema::hasTable('role_menu_item')) {
            Schema::create('role_menu_item', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_definition_id')->constrained('role_definitions')->cascadeOnDelete();
                $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
                $table->boolean('is_visible')->default(true);
                $table->timestamps();

                $table->unique(['role_definition_id', 'menu_item_id']);
            });
        }

        // Idempotent: firstOrCreate for roles/menu items, and only attaches
        // a role<->menu_item pivot row if that pair doesn't already exist -
        // safe to run again even where the seeder already ran successfully.
        (new \Database\Seeders\RoleMenuSettingSeeder)->run();
    }

    public function down(): void
    {
        // Intentionally no-op: this migration only repairs/seeds baseline
        // data that 2026_08_20_000002_create_role_menu_settings_tables'
        // down() already tears down.
    }
};
