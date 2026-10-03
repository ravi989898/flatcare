<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-society override of role_menu_item. Super Admin's Settings ->
     * Menu Settings now only controls Super Admin/Society Admin/Chairman/
     * Security; visibility for Treasurer/Vice Chairman/Secretary/Committee
     * Member/Resident is instead decided per society by that society's own
     * Admin (Society\PermissionSettingController), so one society's choices
     * never leak into another's. MenuItem::visibleForRole() prefers a row
     * here over role_menu_item whenever this society has configured that
     * role at all; otherwise it falls back to the global default.
     */
    public function up(): void
    {
        if (!Schema::hasTable('society_role_menu_item')) {
            Schema::create('society_role_menu_item', function (Blueprint $table) {
                $table->id();
                $table->foreignId('society_id')->constrained('societies')->cascadeOnDelete();
                $table->foreignId('role_definition_id')->constrained('role_definitions')->cascadeOnDelete();
                $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
                $table->boolean('is_visible')->default(true);
                $table->timestamps();

                $table->unique(['society_id', 'role_definition_id', 'menu_item_id'], 'society_role_menu_item_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('society_role_menu_item');
    }
};
