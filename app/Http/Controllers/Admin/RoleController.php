<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\AuditLog;
use App\Models\RoleDefinition;
use App\Models\SocietyDatabase;
use App\Models\SuperAdmin;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Manages the platform-wide role catalog (Settings -> Roles). This is the
 * template every newly provisioned society is seeded from (see
 * TenantRoleSeeder); sync() can push edits into already-provisioned
 * societies' own `roles` tables. Permissions stay per-tenant - this screen
 * only owns the name/description/priority template and (via
 * MenuSettingController) which society-portal menu items a role can see.
 */
class RoleController extends Controller
{
    public function __construct(protected TenantService $tenantService) {}

    public function index(): View
    {
        $roles = RoleDefinition::orderByDesc('priority')->get();

        return view('admin.settings.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('admin.settings.roles.create');
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_system_role'] = false;

        $role = RoleDefinition::create($validated);

        AuditLog::log($this->currentSuperAdmin(), null, 'role.created', 'role_management', RoleDefinition::class, $role->id, null, $role->toArray());

        return redirect()
            ->route('admin.settings.roles.index')
            ->with('success', "Role \"{$role->display_name}\" created. Use \"Sync to societies\" to push it into already-provisioned societies, or it will be included automatically in new ones.");
    }

    public function edit(int $id): View
    {
        $role = RoleDefinition::findOrFail($id);

        return view('admin.settings.roles.edit', compact('role'));
    }

    public function update(RoleRequest $request, int $id): RedirectResponse
    {
        $role = RoleDefinition::findOrFail($id);
        $validated = $request->validated();

        if ($role->is_system_role) {
            // The name is what ties this row to every tenant's `roles.name`
            // and to the hardcoded permission grants in TenantRoleSeeder -
            // renaming a system role would silently break both.
            unset($validated['name']);
        }

        $before = $role->toArray();
        $role->update($validated);

        AuditLog::log($this->currentSuperAdmin(), null, 'role.updated', 'role_management', RoleDefinition::class, $role->id, $before, $role->fresh()->toArray());

        return redirect()
            ->route('admin.settings.roles.index')
            ->with('success', "Role \"{$role->display_name}\" updated. Use \"Sync to societies\" to push these changes into already-provisioned societies.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $role = RoleDefinition::findOrFail($id);

        if ($role->is_system_role) {
            return back()->with('warning', "\"{$role->display_name}\" is a system role and cannot be deleted.");
        }

        $role->delete();

        AuditLog::log($this->currentSuperAdmin(), null, 'role.deleted', 'role_management', RoleDefinition::class, $id, $role->toArray(), null);

        return redirect()
            ->route('admin.settings.roles.index')
            ->with('success', "Role \"{$role->display_name}\" deleted.");
    }

    /**
     * Push the current role catalog into every provisioned society's own
     * `roles` table, matched by name: updates display_name/description/
     * priority for roles that already exist there, inserts any that don't
     * (e.g. a custom role added after that society was created). Existing
     * role_user assignments and permission grants are left untouched.
     */
    public function sync(): RedirectResponse
    {
        $roles = RoleDefinition::orderByDesc('priority')->get();
        $societyIds = SocietyDatabase::where('status', 'active')->pluck('society_id');
        $synced = 0;

        foreach ($societyIds as $societyId) {
            try {
                $this->tenantService->switchConnection($societyId);
            } catch (\Throwable $e) {
                continue;
            }

            foreach ($roles as $role) {
                $tenantRoles = DB::connection('society')->table('roles');
                $existing = (clone $tenantRoles)->where('name', $role->name)->first();

                $values = [
                    'display_name' => $role->display_name,
                    'description' => $role->description,
                    'is_system_role' => $role->is_system_role,
                    'priority' => $role->priority,
                ];

                if ($existing) {
                    $tenantRoles->where('name', $role->name)->update($values + ['updated_at' => now()]);
                } else {
                    $tenantRoles->insert($values + ['name' => $role->name, 'created_at' => now(), 'updated_at' => now()]);
                }
            }

            $synced++;
        }

        AuditLog::log($this->currentSuperAdmin(), null, 'role.synced', 'role_management', RoleDefinition::class, null, null, ['societies_synced' => $synced]);

        return redirect()
            ->route('admin.settings.roles.index')
            ->with('success', "Role catalog synced to {$synced} ".str('society')->plural($synced).'.');
    }

    private function currentSuperAdmin(): ?SuperAdmin
    {
        $userId = auth()->id();

        return $userId ? SuperAdmin::where('user_id', $userId)->first() : null;
    }
}
