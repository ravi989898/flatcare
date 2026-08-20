<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalog of Super Admin dashboard widgets (the stat cards + the
     * revenue chart) and the per-role visibility Super Admin sets under
     * Settings -> Dashboard Widgets - the same pattern as menu_items /
     * role_menu_item (see 2026_08_20_000002_...), applied to the admin
     * dashboard instead of the society-portal sidebar.
     */
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // matches a key in PlatformStatsService::summary(), or 'revenue_chart'
            $table->string('label');
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('role_dashboard_widget', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_definition_id')->constrained('role_definitions')->cascadeOnDelete();
            $table->foreignId('dashboard_widget_id')->constrained('dashboard_widgets')->cascadeOnDelete();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['role_definition_id', 'dashboard_widget_id'], 'role_dashboard_widget_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_dashboard_widget');
        Schema::dropIfExists('dashboard_widgets');
    }
};
