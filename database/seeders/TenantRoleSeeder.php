<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantRoleSeeder extends Seeder
{
    /**
     * Run the tenant database seeder.
     * Creates default roles for society admins
     */
    public function run(): void
    {
        // Default roles for tenant database
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full access to society management',
                'is_system_role' => true,
                'priority' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Manage society operations',
                'is_system_role' => true,
                'priority' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'user',
                'display_name' => 'User',
                'description' => 'Regular user access',
                'is_system_role' => true,
                'priority' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($roles as $role) {
            if (!DB::table('roles')->where('name', $role['name'])->exists()) {
                DB::table('roles')->insert($role);
            }
        }

        // Get modules from main database and create tenant permissions
        $modules = DB::connection('main')
            ->table('modules')
            ->where('is_active', true)
            ->get();

        $permissions = [];
        foreach ($modules as $module) {
            $moduleName = $module->name;
            $permissions[] = [
                'name' => strtolower($moduleName) . '.view',
                'display_name' => "View {$module->display_name}",
                'description' => "View {$module->display_name} data",
                'module' => strtolower($moduleName),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $permissions[] = [
                'name' => strtolower($moduleName) . '.create',
                'display_name' => "Create {$module->display_name}",
                'description' => "Create {$module->display_name} entries",
                'module' => strtolower($moduleName),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $permissions[] = [
                'name' => strtolower($moduleName) . '.edit',
                'display_name' => "Edit {$module->display_name}",
                'description' => "Edit {$module->display_name} entries",
                'module' => strtolower($moduleName),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $permissions[] = [
                'name' => strtolower($moduleName) . '.delete',
                'display_name' => "Delete {$module->display_name}",
                'description' => "Delete {$module->display_name} entries",
                'module' => strtolower($moduleName),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Add common permissions
        $permissions[] = [
            'name' => 'user.view',
            'display_name' => 'View Users',
            'description' => 'View user list',
            'module' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $permissions[] = [
            'name' => 'user.create',
            'display_name' => 'Create Users',
            'description' => 'Create new users',
            'module' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $permissions[] = [
            'name' => 'user.edit',
            'display_name' => 'Edit Users',
            'description' => 'Edit existing users',
            'module' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $permissions[] = [
            'name' => 'user.delete',
            'display_name' => 'Delete Users',
            'description' => 'Delete users',
            'module' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach ($permissions as $permission) {
            if (!DB::table('permissions')->where('name', $permission['name'])->exists()) {
                DB::table('permissions')->insert($permission);
            }
        }

        // Assign all permissions to admin role
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        $allPermissions = DB::table('permissions')->get();

        foreach ($allPermissions as $permission) {
            if (!DB::table('permission_role')
                ->where('role_id', $adminRole->id)
                ->where('permission_id', $permission->id)
                ->exists()) {
                DB::table('permission_role')->insert([
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
