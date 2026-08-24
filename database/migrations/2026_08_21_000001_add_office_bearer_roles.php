<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds the society committee's office-bearer positions — Chairman,
     * Vice Chairman, Secretary — to the platform-wide role catalog
     * (role_definitions), alongside the existing Committee Member role.
     *
     * Like every role_definitions row, TenantRoleSeeder will include these
     * automatically in any society provisioned from here on. Societies
     * that already exist need Super Admin to press "Sync to societies" on
     * Settings -> Roles to get the role itself into their own `roles`
     * table; see TenantRoleSeeder for the matching permission grants
     * (chairman/vice_chairman/secretary get the same grants as Committee
     * Member) that already-provisioned societies pick up automatically,
     * since permissions are read from the tenant `permissions` table by
     * name rather than needing a separate sync step.
     */
    public function up(): void
    {
        $roles = [
            [
                'name' => 'chairman',
                'display_name' => 'Chairman',
                'description' => 'Society committee chairperson — oversees maintenance, committee and finance alongside the Society Admin',
                'priority' => 60,
            ],
            [
                'name' => 'vice_chairman',
                'display_name' => 'Vice Chairman',
                'description' => 'Deputises for the Chairman on committee matters',
                'priority' => 55,
            ],
            [
                'name' => 'secretary',
                'display_name' => 'Secretary',
                'description' => 'Handles society correspondence, announcements, events and elections',
                'priority' => 52,
            ],
        ];

        foreach ($roles as $role) {
            DB::table('role_definitions')->insertOrIgnore([
                ...$role,
                'is_system_role' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Start these roles with the same Society-portal sidebar visibility
        // as Committee Member, rather than none — Settings -> Menu Settings
        // can be used to fine-tune it per role afterwards.
        $committeeMemberId = DB::table('role_definitions')->where('name', 'committee_member')->value('id');

        if (!$committeeMemberId) {
            return;
        }

        $templateVisibility = DB::table('role_menu_item')
            ->where('role_definition_id', $committeeMemberId)
            ->get(['menu_item_id', 'is_visible']);

        foreach ($roles as $role) {
            $roleId = DB::table('role_definitions')->where('name', $role['name'])->value('id');

            foreach ($templateVisibility as $item) {
                DB::table('role_menu_item')->insertOrIgnore([
                    'role_definition_id' => $roleId,
                    'menu_item_id' => $item->menu_item_id,
                    'is_visible' => $item->is_visible,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $roleIds = DB::table('role_definitions')
            ->whereIn('name', ['chairman', 'vice_chairman', 'secretary'])
            ->pluck('id');

        DB::table('role_menu_item')->whereIn('role_definition_id', $roleIds)->delete();
        DB::table('role_definitions')->whereIn('id', $roleIds)->delete();
    }
};
