<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SuperAdmin;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MainAppSeeder extends Seeder
{
    /**
     * Seed the main application database with core data.
     * Creates modules, permissions, and default super admin user.
     */
    public function run(): void
    {
        $this->seedModules();
        $this->seedPermissions();
        $this->seedSuperAdmin();
        $this->call(RoleMenuSettingSeeder::class);
        $this->call(DashboardWidgetSeeder::class);
    }

    /**
     * Seed available modules/features
     */
    private function seedModules(): void
    {
        $modules = [
            [
                'name' => 'Maintenance',
                'display_name' => 'Maintenance',
                'description' => 'Maintenance requests and tracking',
                'icon' => 'fas fa-tools',
                'color' => '#FF6B6B',
                'display_order' => 1,
                'is_core' => true,
            ],
            [
                'name' => 'Visitor',
                'display_name' => 'Visitor Management',
                'description' => 'Visitor entry and exit management',
                'icon' => 'fas fa-door-open',
                'color' => '#4ECDC4',
                'display_order' => 2,
                'is_core' => true,
            ],
            [
                'name' => 'Complaint',
                'display_name' => 'Complaints',
                'description' => 'Complaint registration and resolution',
                'icon' => 'fas fa-exclamation-circle',
                'color' => '#FFE66D',
                'display_order' => 3,
                'is_core' => true,
            ],
            [
                'name' => 'Election',
                'display_name' => 'Elections',
                'description' => 'Society election management',
                'icon' => 'fas fa-ballot',
                'color' => '#95E1D3',
                'display_order' => 4,
                'is_core' => false,
            ],
            [
                'name' => 'Announcement',
                'display_name' => 'Announcements',
                'description' => 'Society announcements and notices',
                'icon' => 'fas fa-bell',
                'color' => '#A8E6CF',
                'display_order' => 5,
                'is_core' => true,
            ],
            [
                'name' => 'Payment',
                'display_name' => 'Payments',
                'description' => 'Maintenance fee and payment management',
                'icon' => 'fas fa-credit-card',
                'color' => '#FF8C42',
                'display_order' => 6,
                'is_core' => true,
            ],
            [
                'name' => 'Directory',
                'display_name' => 'Directory',
                'description' => 'Society member directory',
                'icon' => 'fas fa-address-book',
                'color' => '#FFB3BA',
                'display_order' => 7,
                'is_core' => true,
            ],
            [
                'name' => 'Event',
                'display_name' => 'Events',
                'description' => 'Society events and activities',
                'icon' => 'fas fa-calendar',
                'color' => '#BAE1FF',
                'display_order' => 8,
                'is_core' => false,
            ],
        ];

        foreach ($modules as $moduleData) {
            Module::firstOrCreate(
                ['name' => $moduleData['name']],
                $moduleData
            );
        }

        $this->command->info('Modules seeded successfully');
    }

    /**
     * Seed global permissions for super admin
     */
    private function seedPermissions(): void
    {
        $modules = Module::all();

        foreach ($modules as $module) {
            $permissions = [
                [
                    'name' => "{$module->name}:View",
                    'display_name' => "View {$module->display_name}",
                    'description' => "View {$module->display_name} data",
                ],
                [
                    'name' => "{$module->name}:Create",
                    'display_name' => "Create {$module->display_name}",
                    'description' => "Create new {$module->display_name} entries",
                ],
                [
                    'name' => "{$module->name}:Edit",
                    'display_name' => "Edit {$module->display_name}",
                    'description' => "Edit {$module->display_name} entries",
                ],
                [
                    'name' => "{$module->name}:Delete",
                    'display_name' => "Delete {$module->display_name}",
                    'description' => "Delete {$module->display_name} entries",
                ],
            ];

            foreach ($permissions as $permissionData) {
                Permission::firstOrCreate(
                    ['name' => $permissionData['name']],
                    array_merge($permissionData, ['module_id' => $module->id])
                );
            }
        }

        // System-level permissions
        $systemPermissions = [
            [
                'name' => 'SuperAdmin:Manage',
                'display_name' => 'Manage Super Admins',
                'description' => 'Manage super admin users',
            ],
            [
                'name' => 'Society:Manage',
                'display_name' => 'Manage Societies',
                'description' => 'Create and manage societies',
            ],
            [
                'name' => 'Module:Manage',
                'display_name' => 'Manage Modules',
                'description' => 'Enable/disable modules for societies',
            ],
            [
                'name' => 'Audit:View',
                'display_name' => 'View Audit Logs',
                'description' => 'View system audit logs',
            ],
            [
                'name' => 'Webhook:Manage',
                'display_name' => 'Manage Webhooks',
                'description' => 'Configure payment webhooks',
            ],
        ];

        foreach ($systemPermissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        $this->command->info('Permissions seeded successfully');
    }

    /**
     * Seed default super admin user
     */
    private function seedSuperAdmin(): void
    {
        if (SuperAdmin::exists()) {
            $this->command->info('Super admin already exists — skipping creation');
            return;
        }

        // Check if user already exists
        $user = User::where('email', 'superadmin@flatcare.local')->first();
        
        if (!$user) {
            $password = Str::password(16);

            $user = User::create([
                'name' => 'FlatCare Super Admin',
                'email' => 'superadmin@flatcare.local',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'role' => 'super_admin',
                'is_active' => true,
            ]);
        } else {
            $password = 'not-regenerated'; // User exists, we won't show password
        }

        // Create super admin profile
        $superAdmin = SuperAdmin::create([
            'user_id' => $user->id,
            'name' => 'FlatCare Super Admin',
            'email' => 'superadmin@flatcare.local',
            'phone' => '9999999999',
            'status' => 'active',
        ]);

        // Assign all permissions to super admin
        $allPermissions = Permission::all();
        $superAdmin->permissions()->attach($allPermissions);

        $this->command->warn('✓ Super admin account created successfully');
        $this->command->line('');
        $this->command->info('Super Admin Credentials:');
        $this->command->line("  Email:    {$user->email}");
        if ($password !== 'not-regenerated') {
            $this->command->line("  Password: {$password}");
            $this->command->line('');
            $this->command->warn('⚠️  Save these credentials securely. They will not be shown again.');
        }
    }
}
