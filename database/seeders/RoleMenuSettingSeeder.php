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
            ['key' => 'admins', 'label' => 'Admins', 'route_name' => 'society.admins.index', 'icon' => 'bi-person-badge', 'display_order' => 2],
            ['key' => 'payments', 'label' => 'Payments', 'route_name' => 'society.payments.index', 'icon' => 'bi-credit-card', 'display_order' => 3],
            ['key' => 'water-readings', 'label' => 'Water Readings', 'route_name' => 'society.water-readings.index', 'icon' => 'bi-droplet', 'display_order' => 4],
            ['key' => 'blocks', 'label' => 'Blocks', 'route_name' => 'society.blocks.index', 'icon' => 'bi-building', 'display_order' => 5],
            ['key' => 'security', 'label' => 'Security', 'route_name' => 'society.security.index', 'icon' => 'bi-shield-lock', 'display_order' => 6],
            ['key' => 'visitors', 'label' => 'Visitors', 'route_name' => 'society.visitors.index', 'icon' => 'bi-person-badge', 'display_order' => 7],
            // Parent group for the sidebar's "Reports" submenu (see
            // SetSocietyContext::configureAdminlteSidebar). Its own
            // route_name doubles as the first child's route so active_pattern
            // still highlights the group when a report page is open.
            ['key' => 'reports', 'label' => 'Reports', 'route_name' => 'society.reports.payments', 'icon' => 'bi-file-earmark-bar-graph', 'display_order' => 8],
            ['key' => 'complaints', 'label' => 'Complaints', 'route_name' => 'society.complaints.index', 'icon' => 'bi-exclamation-circle', 'display_order' => 9],
            // Admin-only (EnsureMenuItemVisible::ADMIN_ONLY_KEYS): Society
            // Admin decides per-society visibility here for Treasurer/Vice
            // Chairman/Secretary/Committee Member/Resident; see
            // Society\PermissionSettingController and society_role_menu_item.
            ['key' => 'permissions', 'label' => 'Permissions', 'route_name' => 'society.permissions.index', 'icon' => 'bi-shield-check', 'display_order' => 10],
            ['key' => 'extra-charges', 'label' => 'Extra Charges', 'route_name' => 'society.extra-charges.index', 'icon' => 'bi-cash-stack', 'display_order' => 11],
            ['key' => 'directory', 'label' => 'Directory', 'route_name' => 'society.directory.index', 'icon' => 'bi-people', 'display_order' => 12],
            ['key' => 'announcements', 'label' => 'Announcements', 'route_name' => 'society.announcements.index', 'icon' => 'bi-megaphone', 'display_order' => 13],
            // Parent group for the sidebar's "Activity" submenu, same
            // pattern as "Reports" above: its own route_name doubles as the
            // first child's route so active_pattern still highlights it.
            ['key' => 'activity', 'label' => 'Activity', 'route_name' => 'society.events.index', 'icon' => 'bi-calendar2-week', 'display_order' => 14],
            ['key' => 'events', 'parent_key' => 'activity', 'label' => 'Events', 'route_name' => 'society.events.index', 'icon' => 'bi-calendar-event', 'display_order' => 1],
            ['key' => 'elections', 'parent_key' => 'activity', 'label' => 'Elections', 'route_name' => 'society.elections.index', 'icon' => 'bi-check2-square', 'display_order' => 2],
            ['key' => 'documents', 'label' => 'Documents', 'route_name' => 'society.documents.index', 'icon' => 'bi-file-earmark-text', 'display_order' => 16],
            ['key' => 'emergency-contacts', 'label' => 'Emergency Contacts', 'route_name' => 'society.emergency-contacts.index', 'icon' => 'bi-telephone', 'display_order' => 17],
            ['key' => 'polls', 'label' => 'Polls & Surveys', 'route_name' => 'society.polls.index', 'icon' => 'bi-bar-chart-steps', 'display_order' => 18],
            ['key' => 'service-providers', 'label' => 'Service Providers', 'route_name' => 'society.service-providers.index', 'icon' => 'bi-wrench', 'display_order' => 19],
            ['key' => 'payment-report', 'parent_key' => 'reports', 'label' => 'Payment Report', 'route_name' => 'society.reports.payments', 'icon' => 'bi-receipt-cutoff', 'display_order' => 1],
            // Children of the "Permissions" parent group (see
            // 2026_10_03_000004_split_permissions_into_web_and_app): Web
            // Permission is the original per-society menu-visibility page
            // (society_role_menu_item); App Permission is its mobile-app
            // counterpart (society_role_app_menu_item).
            ['key' => 'web-permission', 'parent_key' => 'permissions', 'label' => 'Web Permission', 'route_name' => 'society.permissions.index', 'icon' => 'bi-display', 'display_order' => 1],
            ['key' => 'app-permission', 'parent_key' => 'permissions', 'label' => 'App Permission', 'route_name' => 'society.app-permissions.index', 'icon' => 'bi-phone', 'display_order' => 2],
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
            'committee_member' => ['dashboard', 'visitors', 'complaints', 'directory', 'announcements', 'activity', 'events', 'documents', 'emergency-contacts', 'polls', 'service-providers'],
            // Residents use the mobile app; the society portal is refused to them at the door (SetSocietyContext),
            // and admin-only modules are refused server-side (EnsureMenuItemVisible::ADMIN_ONLY_KEYS).
            'resident' => ['dashboard', 'visitors', 'complaints', 'directory', 'announcements', 'activity', 'events', 'elections', 'documents', 'emergency-contacts', 'polls', 'service-providers'],
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
