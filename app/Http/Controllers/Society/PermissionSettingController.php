<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Society\PermissionVisibilityRequest;
use App\Models\AuditLog;
use App\Models\MenuItem;
use App\Models\RoleDefinition;
use App\Models\SocietyRoleMenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Lets a society's own Admin choose which Society-portal sidebar items its
 * Treasurer, Vice Chairman, Secretary, Committee Member and Resident see
 * (Settings -> Permissions). Super Admin, Society Admin, Chairman and
 * Security stay fixed platform-wide - those are still managed under the
 * Super Admin panel's Settings -> Menu Settings (Admin\MenuSettingController).
 * Saved to society_role_menu_item, scoped to the current society only, and
 * read by MenuItem::visibleForRole() in preference to the global default.
 * Every change a society makes here is recorded to audit_logs (module
 * 'permission_management') so Super Admin can review it under Settings ->
 * Permission Logs (Admin\PermissionLogController).
 */
class PermissionSettingController extends Controller
{
    private const MANAGED_ROLES = ['treasurer', 'vice_chairman', 'secretary', 'committee_member', 'resident'];

    public function edit(): View
    {
        $society = request()->attributes->get('society');
        $roles = RoleDefinition::whereIn('name', self::MANAGED_ROLES)->orderByDesc('priority')->get();
        $menuItems = MenuItem::orderBy('display_order')->get();

        // [role_id => [menu_item_id => bool]]
        $visibility = $roles->mapWithKeys(fn (RoleDefinition $role) => [
            $role->id => $this->currentVisibility($society->id, $role, $menuItems),
        ]);

        return view('society.permissions.index', compact('roles', 'menuItems', 'visibility'));
    }

    public function update(PermissionVisibilityRequest $request): RedirectResponse
    {
        $society = $request->attributes->get('society');
        $validated = $request->validated();

        $roles = RoleDefinition::whereIn('name', self::MANAGED_ROLES)->get();
        $menuItems = MenuItem::all();
        $submitted = $validated['visibility'] ?? [];
        $actor = Auth::guard('society')->user();

        // One audit log row per form save, covering every role/item this
        // submission touched - not one row per role - so Super Admin sees
        // exactly what one "Save Permissions" click changed, as one entry.
        $before = [];
        $after = [];

        foreach ($roles as $role) {
            $roleBefore = $this->currentVisibility($society->id, $role, $menuItems);
            $roleAfter = $menuItems->mapWithKeys(fn (MenuItem $item) => [
                $item->id => isset($submitted[$role->id][$item->id]),
            ]);

            foreach ($menuItems as $menuItem) {
                SocietyRoleMenuItem::updateOrCreate(
                    [
                        'society_id' => $society->id,
                        'role_definition_id' => $role->id,
                        'menu_item_id' => $menuItem->id,
                    ],
                    [
                        'is_visible' => $roleAfter[$menuItem->id],
                    ]
                );

                $key = "{$role->display_name} → {$menuItem->label}";
                $before[$key] = $roleBefore[$menuItem->id];
                $after[$key] = $roleAfter[$menuItem->id];
            }
        }

        if ($before !== $after) {
            AuditLog::log(
                superAdmin: null,
                society: $society,
                action: 'permissions.updated',
                module: 'permission_management',
                oldValues: $before,
                newValues: $after,
                performedBy: $actor?->name ?? 'Society Admin',
            );
        }

        return redirect()
            ->route('society.permissions.index')
            ->with('success', 'Permissions updated for Treasurer, Vice Chairman, Secretary, Committee Member and Resident.');
    }

    /**
     * This society's own saved choice for the role if it has made one,
     * otherwise the global default it currently inherits from Settings ->
     * Menu Settings. [menu_item_id => bool], covering every cataloged item.
     */
    private function currentVisibility(int $societyId, RoleDefinition $role, Collection $menuItems): Collection
    {
        $overrides = SocietyRoleMenuItem::where('society_id', $societyId)
            ->where('role_definition_id', $role->id)
            ->pluck('is_visible', 'menu_item_id');

        if ($overrides->isNotEmpty()) {
            return $menuItems->mapWithKeys(fn (MenuItem $item) => [$item->id => (bool) ($overrides[$item->id] ?? false)]);
        }

        $globalDefault = $role->menuItems->pluck('pivot.is_visible', 'id');

        return $menuItems->mapWithKeys(fn (MenuItem $item) => [$item->id => (bool) ($globalDefault[$item->id] ?? false)]);
    }
}
