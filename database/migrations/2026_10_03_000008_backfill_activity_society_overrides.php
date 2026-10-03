<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MenuItem::visibleForRole() ignores the global role_menu_item default
 * entirely for a (society, role) pair that has ANY society_role_menu_item
 * row at all - it only consults that society's own overrides. So a society
 * that already customized a role's menu under Web Permission (before
 * 2026_10_03_000007 introduced the "Activity" parent) has no row for it and
 * silently can't see the new group, even if that role could already see
 * Events or Elections. Backfills an 'activity' override wherever one of its
 * new children was already visible, so existing customizations keep working
 * exactly as they did before the two items were nested under a parent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $activityId = DB::table('menu_items')->where('key', 'activity')->value('id');
        $childIds = DB::table('menu_items')->whereIn('key', ['events', 'elections'])->pluck('id');

        $pairs = DB::table('society_role_menu_item')
            ->whereIn('menu_item_id', $childIds)
            ->where('is_visible', true)
            ->select('society_id', 'role_definition_id')
            ->distinct()
            ->get();

        foreach ($pairs as $pair) {
            DB::table('society_role_menu_item')->insertOrIgnore([
                'society_id' => $pair->society_id,
                'role_definition_id' => $pair->role_definition_id,
                'menu_item_id' => $activityId,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally no-op, matching the other menu-catalog migrations.
    }
};
