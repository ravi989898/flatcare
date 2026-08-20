<?php

namespace Database\Seeders;

use App\Models\DashboardWidget;
use App\Models\RoleDefinition;
use Illuminate\Database\Seeder;

class DashboardWidgetSeeder extends Seeder
{
    /**
     * Seed the Super Admin dashboard's widget catalog and default every
     * role to seeing everything. Only 'super_admin' (and, if one is ever
     * added, an 'admin' role) can actually reach /admin/dashboard - the
     * other roles' rows exist for consistency with Menu Settings and are
     * inert until such a role gains admin-panel access.
     */
    public function run(): void
    {
        $widgets = [
            ['key' => 'total_societies', 'label' => 'Total Societies', 'display_order' => 1],
            ['key' => 'active_societies', 'label' => 'Active Societies', 'display_order' => 2],
            ['key' => 'inactive_societies', 'label' => 'Inactive Societies', 'display_order' => 3],
            ['key' => 'expired_societies', 'label' => 'Expired Societies', 'display_order' => 4],
            ['key' => 'total_blocks', 'label' => 'Total Blocks', 'display_order' => 5],
            ['key' => 'total_flats', 'label' => 'Total Flats', 'display_order' => 6],
            ['key' => 'total_registered_users', 'label' => 'Total Registered Users', 'display_order' => 7],
            ['key' => 'total_society_admins', 'label' => 'Total Society Admins', 'display_order' => 8],
            ['key' => 'total_committee_members', 'label' => 'Total Committee Members', 'display_order' => 9],
            ['key' => 'total_security_users', 'label' => 'Total Security Users', 'display_order' => 10],
            ['key' => 'active_maintenance_configs', 'label' => 'Active Maintenance Configs', 'display_order' => 11],
            ['key' => 'pending_society_setup', 'label' => 'Pending Society Setup', 'display_order' => 12],
            ['key' => 'revenue_chart', 'label' => 'Revenue Chart', 'display_order' => 13],
            ['key' => 'society_status_chart', 'label' => 'Society Status Chart', 'display_order' => 14],
        ];

        foreach ($widgets as $widget) {
            DashboardWidget::firstOrCreate(['key' => $widget['key']], $widget);
        }

        // Default new widgets to visible for every role, WITHOUT touching
        // widgets a role already has a row for - so re-running this (e.g.
        // when a new widget like society_status_chart is added later) never
        // clobbers visibility choices Super Admin already made under
        // Settings -> Dashboard Widgets.
        foreach (RoleDefinition::all() as $role) {
            $alreadyConfigured = $role->dashboardWidgets()->pluck('dashboard_widgets.id');

            DashboardWidget::whereNotIn('id', $alreadyConfigured)
                ->get()
                ->each(fn (DashboardWidget $widget) => $role->dashboardWidgets()->attach($widget->id, ['is_visible' => true]));
        }
    }
}
