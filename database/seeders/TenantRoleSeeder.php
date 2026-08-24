<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantRoleSeeder extends Seeder
{
    /**
     * Run the tenant database seeder.
     *
     * Creates the standard FlatCare role set for a society and grants each
     * role the permissions matching its real-world scope (see ARCHITECTURE.md
     * section 5.1):
     *
     *   1. Super Admin       - full platform reach, mirrored into every
     *                          tenant so platform staff can act inside a
     *                          society without a separate account.
     *   2. Society Admin     - full operational control of the society
     *                          (residents, maintenance, committee, finance).
     *   3. Committee Member  - block-level oversight: maintenance, visitor
     *                          and complaint handling, announcements/events.
     *   4. User / Flat Owner / Resident - the resident's own profile,
     *                          payments, complaints, directory, visitors,
     *                          elections.
     *   5. Security          - gate/visitor management only.
     */
    public function run(): void
    {
        // Roles are sourced from the main database's role_definitions
        // catalog (Settings -> Roles in the Super Admin panel) rather than a
        // hardcoded list here, so a role Super Admin adds/edits there flows
        // into every newly provisioned society. Editing an existing
        // society's roles afterwards is a separate, explicit action -
        // Admin\RoleController::sync().
        $catalogRoles = DB::connection('main')->table('role_definitions')->get();

        foreach ($catalogRoles as $role) {
            if (!DB::table('roles')->where('name', $role->name)->exists()) {
                DB::table('roles')->insert([
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'description' => $role->description,
                    'is_system_role' => $role->is_system_role,
                    'priority' => $role->priority,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Get modules from main database and create tenant permissions
        $modules = DB::connection('main')
            ->table('modules')
            ->where('is_active', true)
            ->get();

        $permissions = [];
        foreach ($modules as $module) {
            $moduleName = strtolower($module->name);
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                $permissions[] = [
                    'name' => "{$moduleName}.{$action}",
                    'display_name' => ucfirst($action) . " {$module->display_name}",
                    'description' => ucfirst($action) . " {$module->display_name} entries",
                    'module' => $moduleName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Add common user-management permissions
        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            $permissions[] = [
                'name' => "user.{$action}",
                'display_name' => ucfirst($action) . ' Users',
                'description' => ucfirst($action) . ' user accounts',
                'module' => 'user',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($permissions as $permission) {
            if (!DB::table('permissions')->where('name', $permission['name'])->exists()) {
                DB::table('permissions')->insert($permission);
            }
        }

        $this->assignRolePermissions();
    }

    /**
     * Grant each role the permissions matching its documented scope.
     */
    private function assignRolePermissions(): void
    {
        $roles = DB::table('roles')->get()->keyBy('name');
        $permissions = DB::table('permissions')->get();

        $grants = [
            // Super Admin & Society Admin: unrestricted access.
            'super_admin' => fn ($p) => true,
            'admin' => fn ($p) => true,

            // Committee Member, and the Chairman/Vice Chairman/Secretary
            // office-bearer positions (see the migration that added them to
            // role_definitions): view/create/edit on maintenance, visitor,
            // complaint, announcement, event; view-only on directory/users.
            // All four share one grant set for now - split them out here
            // if an office bearer ever needs something Committee Member
            // doesn't.
            'committee_member' => $committeeGrant = fn ($p) => (
                    in_array($p->module, ['maintenance', 'visitor', 'complaint', 'announcement', 'event'], true)
                    && in_array($p->name, [
                        "{$p->module}.view", "{$p->module}.create", "{$p->module}.edit",
                    ], true)
                )
                || in_array($p->name, ['directory.view', 'user.view'], true),
            'chairman' => $committeeGrant,
            'vice_chairman' => $committeeGrant,
            'secretary' => $committeeGrant,

            // Treasurer: full run of the payment module (raising bills,
            // recording payments), plus the same view-only access to
            // maintenance/directory/users as Committee Member.
            'treasurer' => fn ($p) => in_array($p->name, [
                'payment.view', 'payment.create', 'payment.edit', 'payment.delete',
                'maintenance.view',
                'directory.view', 'user.view',
            ], true),

            // Resident: view own-facing modules, plus raising complaints,
            // pre-approving visitors and voting in elections.
            'resident' => fn ($p) => in_array($p->name, [
                'maintenance.view', 'maintenance.create',
                'payment.view',
                'complaint.view', 'complaint.create',
                'directory.view',
                'visitor.view', 'visitor.create',
                'election.view', 'election.create',
                'announcement.view',
                'event.view',
            ], true),

            // Security: visitor management only.
            'security' => fn ($p) => in_array($p->name, [
                'visitor.view', 'visitor.create', 'visitor.edit',
                'directory.view',
            ], true),
        ];

        foreach ($grants as $roleName => $matcher) {
            $role = $roles->get($roleName);

            if (!$role) {
                continue;
            }

            foreach ($permissions as $permission) {
                if (!$matcher($permission)) {
                    continue;
                }

                $exists = DB::table('permission_role')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permission->id)
                    ->exists();

                if (!$exists) {
                    DB::table('permission_role')->insert([
                        'role_id' => $role->id,
                        'permission_id' => $permission->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
