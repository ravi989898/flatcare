<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds the Treasurer ("khajanji") office-bearer position to the
     * platform-wide role catalog, alongside Chairman/Vice Chairman/
     * Secretary/Committee Member added by the previous migration. Kept as
     * its own migration rather than folded into that one since that one
     * has already run — see it for how new societies vs. already-
     * provisioned ones pick this role up.
     */
    public function up(): void
    {
        DB::table('role_definitions')->insertOrIgnore([
            'name' => 'treasurer',
            'display_name' => 'Treasurer',
            'description' => 'Manages the society\'s maintenance bills, collections and payment records',
            'is_system_role' => false,
            'priority' => 58,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Same Society-portal sidebar visibility as Committee Member to
        // start - Settings -> Menu Settings can fine-tune it afterwards.
        $committeeMemberId = DB::table('role_definitions')->where('name', 'committee_member')->value('id');
        $treasurerId = DB::table('role_definitions')->where('name', 'treasurer')->value('id');

        if (!$committeeMemberId || !$treasurerId) {
            return;
        }

        $templateVisibility = DB::table('role_menu_item')
            ->where('role_definition_id', $committeeMemberId)
            ->get(['menu_item_id', 'is_visible']);

        foreach ($templateVisibility as $item) {
            DB::table('role_menu_item')->insertOrIgnore([
                'role_definition_id' => $treasurerId,
                'menu_item_id' => $item->menu_item_id,
                'is_visible' => $item->is_visible,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('role_definitions')->where('name', 'treasurer')->value('id');

        DB::table('role_menu_item')->where('role_definition_id', $roleId)->delete();
        DB::table('role_definitions')->where('id', $roleId)->delete();
    }
};
