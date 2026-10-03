<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Society\PermissionVisibilityRequest;
use App\Models\AppMenuItem;
use App\Models\AuditLog;
use App\Models\RoleDefinition;
use App\Models\SocietyRoleAppMenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * App Permission: the mobile-app counterpart of PermissionSettingController
 * (Web Permission). Lets a society's own Admin choose which of the mobile
 * app's navigation items (see app_menu_items, seeded from the Flutter
 * home screen's hardcoded menu groups) its Treasurer, Vice Chairman,
 * Secretary, Committee Member and Resident see in the app. Saved to
 * society_role_app_menu_item, scoped to the current society only. An item
 * with no override for a role is visible by default - there's no global
 * default layer here the way MenuItem has Settings -> Menu Settings.
 */
class AppPermissionSettingController extends Controller
{
    private const MANAGED_ROLES = ['treasurer', 'vice_chairman', 'secretary', 'committee_member', 'resident'];

    public function edit(): View
    {
        $society = request()->attributes->get('society');
        $roles = RoleDefinition::whereIn('name', self::MANAGED_ROLES)->orderByDesc('priority')->get();
        $menuItems = AppMenuItem::orderBy('display_order')->get();

        // [role_id => [menu_item_id => bool]]
        $visibility = $roles->mapWithKeys(fn (RoleDefinition $role) => [
            $role->id => $this->currentVisibility($society->id, $role, $menuItems),
        ]);

        return view('society.permissions.app', compact('roles', 'menuItems', 'visibility'));
    }

    public function update(PermissionVisibilityRequest $request): RedirectResponse
    {
        $society = $request->attributes->get('society');
        $validated = $request->validated();

        $roles = RoleDefinition::whereIn('name', self::MANAGED_ROLES)->get();
        $menuItems = AppMenuItem::all();
        $submitted = $validated['visibility'] ?? [];
        $actor = Auth::guard('society')->user();

        $before = [];
        $after = [];

        foreach ($roles as $role) {
            $roleBefore = $this->currentVisibility($society->id, $role, $menuItems);
            $roleAfter = $menuItems->mapWithKeys(fn (AppMenuItem $item) => [
                $item->id => isset($submitted[$role->id][$item->id]),
            ]);

            foreach ($menuItems as $menuItem) {
                SocietyRoleAppMenuItem::updateOrCreate(
                    [
                        'society_id' => $society->id,
                        'role_definition_id' => $role->id,
                        'app_menu_item_id' => $menuItem->id,
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
                action: 'app_permissions.updated',
                module: 'permission_management',
                oldValues: $before,
                newValues: $after,
                performedBy: $actor?->name ?? 'Society Admin',
            );
        }

        return redirect()
            ->route('society.app-permissions.index')
            ->with('success', 'App permissions updated for Treasurer, Vice Chairman, Secretary, Committee Member and Resident.');
    }

    /**
     * This society's own saved choice for the role, or true (visible) for
     * any item it hasn't overridden yet - unlike Web Permission there's no
     * global default to fall back to first.
     */
    private function currentVisibility(int $societyId, RoleDefinition $role, Collection $menuItems): Collection
    {
        $overrides = SocietyRoleAppMenuItem::where('society_id', $societyId)
            ->where('role_definition_id', $role->id)
            ->pluck('is_visible', 'app_menu_item_id');

        return $menuItems->mapWithKeys(fn (AppMenuItem $item) => [$item->id => (bool) ($overrides[$item->id] ?? true)]);
    }
}
