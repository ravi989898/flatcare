<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Main-database catalog of roles and society-portal menu items, plus the
     * per-role visibility toggle Super Admin manages under Settings.
     *
     * These roles are the platform-wide template: TenantRoleSeeder seeds new
     * societies from this table instead of a hardcoded list, and
     * Admin\RoleController::sync() can push edits into existing societies'
     * own `roles` tables (matched by name). Menu visibility here only
     * decides what shows in the Society-portal sidebar for a given role - it
     * does not control permissions, which remain per-tenant.
     */
    public function up(): void
    {
        Schema::create('role_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // matches the tenant `roles.name` value, e.g. 'admin', 'resident'
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->boolean('is_system_role')->default(false); // the 5 seeded roles; cannot be deleted or renamed
            $table->integer('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g. 'maintenance'
            $table->string('label');
            $table->string('route_name'); // society.* route this item links to
            $table->string('icon')->nullable(); // bootstrap-icons class, e.g. 'bi-tools'
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('role_menu_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_definition_id')->constrained('role_definitions')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['role_definition_id', 'menu_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_menu_item');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('role_definitions');
    }
};
