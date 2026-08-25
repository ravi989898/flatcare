<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\RoleDefinition;
use Illuminate\Database\Seeder;

class RoleMenuSettingSeeder extends Seeder
{
    /**
     * Seed the main-database role catalog (mirrors TenantRoleSeeder's 5
     * roles) and the Society-portal menu catalog, then apply the default
     * per-role menu visibility. Super Admin can adjust both afterwards under
     * Settings -> Roles / Menu Settings.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'super_admin', 'display_name' => 'Super Admin', 'description' => 'Platform-wide access, mirrored into every society', 'is_system_role' => true, 'priority' => 100],
            ['name' => 'admin', 'display_name' => 'Society Admin', 'description' => 'Full access to society operations, residents, maintenance, committee and finance', 'is_system_role' => true, 'priority' => 80],
            ['name' => 'committee_member', 'display_name' => 'Committee Member', 'description' => 'Block-level access to maintenance, visitors, complaints and announcements', 'is_system_role' => true, 'priority' => 50],
            ['name' => 'resident', 'display_name' => 'User / Flat Owner / Resident', 'description' => 'Own profile, payments, complaints, directory, visitor approval and elections', 'is_system_role' => true, 'priority' => 20],
            ['name' => 'security', 'display_name' => 'Security', 'description' => 'Visitor entry/exit tracking at the gate', 'is_system_role' => true, 'priority' => 10],
        ];

        foreach ($roles as $role) {
            RoleDefinition::firstOrCreate(['name' => $role['name']], $role);
        }

        $menuItems = [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'route_name' => 'society.dashboard', 'icon' => 'bi-speedometer2', 'display_order' => 1],
            ['key' => 'maintenance', 'label' => 'Maintenance', 'route_name' => 'society.maintenance.index', 'icon' => 'bi-tools', 'display_order' => 2],
            ['key' => 'visitors', 'label' => 'Visitors', 'route_name' => 'society.visitors.index', 'icon' => 'bi-person-badge', 'display_order' => 3],
            ['key' => 'complaints', 'label' => 'Complaints', 'route_name' => 'society.complaints.index', 'icon' => 'bi-exclamation-circle', 'display_order' => 4],
            ['key' => 'directory', 'label' => 'Directory', 'route_name' => 'society.directory.index', 'icon' => 'bi-people', 'display_order' => 5],
            ['key' => 'announcements', 'label' => 'Announcements', 'route_name' => 'society.announcements.index', 'icon' => 'bi-megaphone', 'display_order' => 6],
            ['key' => 'events', 'label' => 'Events', 'route_name' => 'society.events.index', 'icon' => 'bi-calendar-event', 'display_order' => 7],
            ['key' => 'elections', 'label' => 'Elections', 'route_name' => 'society.elections.index', 'icon' => 'bi-check2-square', 'display_order' => 8],
            ['key' => 'payments', 'label' => 'Payments', 'route_name' => 'society.payments.index', 'icon' => 'bi-credit-card', 'display_order' => 9],
            ['key' => 'water-readings', 'label' => 'Water Readings', 'route_name' => 'society.water-readings.index', 'icon' => 'bi-droplet', 'display_order' => 10],
            ['key' => 'documents', 'label' => 'Documents', 'route_name' => 'society.documents.index', 'icon' => 'bi-file-earmark-text', 'display_order' => 11],
            ['key' => 'emergency-contacts', 'label' => 'Emergency Contacts', 'route_name' => 'society.emergency-contacts.index', 'icon' => 'bi-telephone', 'display_order' => 12],
            ['key' => 'polls', 'label' => 'Polls & Surveys', 'route_name' => 'society.polls.index', 'icon' => 'bi-bar-chart-steps', 'display_order' => 13],
            ['key' => 'service-providers', 'label' => 'Service Providers', 'route_name' => 'society.service-providers.index', 'icon' => 'bi-wrench', 'display_order' => 14],
        ];

        foreach ($menuItems as $item) {
            MenuItem::firstOrCreate(['key' => $item['key']], $item);
        }

        $this->applyDefaultVisibility();
    }

    /**
     * Default menu visibility per role, matching the scope already granted
     * to each role's permissions in TenantRoleSeeder (committee/resident/
     * security see only what their permissions actually let them use).
     */
    private function applyDefaultVisibility(): void
    {
        $allKeys = MenuItem::pluck('key')->all();

        $visibleKeysByRole = [
            'super_admin' => $allKeys,
            'admin' => $allKeys,
            'committee_member' => ['dashboard', 'maintenance', 'visitors', 'complaints', 'directory', 'announcements', 'events', 'documents', 'emergency-contacts', 'polls', 'service-providers'],
            'resident' => $allKeys, // resident permissions already cover every module, just their own-facing view
            'security' => ['dashboard', 'visitors', 'directory'],
        ];

        $menuItemsByKey = MenuItem::all()->keyBy('key');

        foreach ($visibleKeysByRole as $roleName => $visibleKeys) {
            $role = RoleDefinition::where('name', $roleName)->first();

            if (!$role) {
                continue;
            }

            // Only set a default for a menu item this role has no row for
            // yet - never overwrite a visibility choice Super Admin already
            // made under Settings -> Menu Settings, e.g. when a new menu
            // item is added later and this seeder runs again.
            $alreadyConfigured = $role->menuItems()->pluck('menu_items.id');

            foreach ($menuItemsByKey as $key => $menuItem) {
                if ($alreadyConfigured->contains($menuItem->id)) {
                    continue;
                }

                $role->menuItems()->attach($menuItem->id, ['is_visible' => in_array($key, $visibleKeys, true)]);
            }
        }
    }
}
