<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DashboardWidget;
use App\Models\RoleDefinition;
use App\Models\SuperAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lets Super Admin choose which admin-dashboard widgets each role sees
 * (Settings -> Dashboard Widgets). Read by DashboardController on every
 * dashboard request to decide what to render for the current user's role.
 * Same shape as MenuSettingController, applied to dashboard widgets instead
 * of the society-portal sidebar.
 */
class DashboardWidgetSettingController extends Controller
{
    public function edit(): View
    {
        $roles = RoleDefinition::orderByDesc('priority')->get();
        $widgets = DashboardWidget::orderBy('display_order')->get();

        // [role_id => [widget_id => bool]]
        $visibility = $roles->mapWithKeys(function (RoleDefinition $role) {
            return [$role->id => $role->dashboardWidgets->pluck('pivot.is_visible', 'id')];
        });

        return view('admin.settings.dashboard-widgets', compact('roles', 'widgets', 'visibility'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'visibility' => 'array',
            'visibility.*' => 'array',
        ]);

        $roles = RoleDefinition::all();
        $widgets = DashboardWidget::all();
        $submitted = $validated['visibility'] ?? [];

        foreach ($roles as $role) {
            $pivotData = [];

            foreach ($widgets as $widget) {
                $pivotData[$widget->id] = [
                    'is_visible' => isset($submitted[$role->id][$widget->id]),
                ];
            }

            $role->dashboardWidgets()->sync($pivotData);
        }

        AuditLog::log($this->currentSuperAdmin(), null, 'dashboard_widgets.updated', 'role_management');

        return redirect()
            ->route('admin.settings.dashboard_widgets.edit')
            ->with('success', 'Dashboard widget visibility updated for all roles.');
    }

    private function currentSuperAdmin(): ?SuperAdmin
    {
        $userId = auth()->id();

        return $userId ? SuperAdmin::where('user_id', $userId)->first() : null;
    }
}
