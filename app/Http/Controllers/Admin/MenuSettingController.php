<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MenuItem;
use App\Models\RoleDefinition;
use App\Models\SuperAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lets Super Admin choose which Society-portal sidebar items each role can
 * see (Settings -> Menu Settings). Read by SetSocietyContext on every
 * society-portal request to build the logged-in tenant user's menu.
 */
class MenuSettingController extends Controller
{
    public function edit(): View
    {
        $roles = RoleDefinition::orderByDesc('priority')->get();
        $menuItems = MenuItem::orderBy('display_order')->get();

        // [role_id => [menu_item_id => bool]]
        $visibility = $roles->mapWithKeys(function (RoleDefinition $role) {
            return [$role->id => $role->menuItems->pluck('pivot.is_visible', 'id')];
        });

        return view('admin.settings.menu', compact('roles', 'menuItems', 'visibility'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'visibility' => 'array',
            'visibility.*' => 'array',
        ]);

        $roles = RoleDefinition::all();
        $menuItems = MenuItem::all();
        $submitted = $validated['visibility'] ?? [];

        foreach ($roles as $role) {
            $pivotData = [];

            foreach ($menuItems as $menuItem) {
                $pivotData[$menuItem->id] = [
                    'is_visible' => isset($submitted[$role->id][$menuItem->id]),
                ];
            }

            $role->menuItems()->sync($pivotData);
        }

        AuditLog::log($this->currentSuperAdmin(), null, 'menu_settings.updated', 'role_management');

        return redirect()
            ->route('admin.settings.menu.edit')
            ->with('success', 'Menu visibility updated for all roles.');
    }

    private function currentSuperAdmin(): ?SuperAdmin
    {
        $userId = auth()->id();

        return $userId ? SuperAdmin::where('user_id', $userId)->first() : null;
    }
}
