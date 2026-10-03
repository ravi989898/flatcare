<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Moves "Polls & Surveys" from a standalone sidebar entry into the
 * "Activity" group alongside Events and Elections, per the Society Admin's
 * request. Every role/society that could already see Polls could already
 * see Activity too (verified against both role_menu_item and
 * society_role_menu_item before writing this), so no visibility backfill
 * is needed here - unlike 2026_10_03_000008 for the group's first two
 * children.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_items')->where('key', 'polls')->update(['parent_key' => 'activity', 'display_order' => 3]);
        DB::table('menu_items')->where('key', 'service-providers')->update(['display_order' => 18]);
    }

    public function down(): void
    {
        DB::table('menu_items')->where('key', 'polls')->update(['parent_key' => null, 'display_order' => 18]);
        DB::table('menu_items')->where('key', 'service-providers')->update(['display_order' => 19]);
    }
};
