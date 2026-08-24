<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Society;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Every registered user in one society, grouped by Block -> Flat, with a
 * role-assignment dropdown per row (Settings -> Roles is where the role
 * catalog itself is managed; this is where Super Admin actually hands a
 * role to a specific resident - Chairman, Secretary, Treasurer, Vice
 * Chairman, Committee Member, or any custom role added later). The role
 * list on screen is read live from this society's own `roles` table, so a
 * role added to the catalog and synced in (see Admin\RoleController::sync())
 * shows up here with no code change.
 */
class SocietyUserController extends Controller
{
    public function __construct(protected TenantService $tenantService) {}

    public function index(Request $request, int $societyId): View
    {
        $society = Society::findOrFail($societyId);

        $this->tenantService->switchConnection($societyId);

        $users = User::with(['roles', 'residencies' => fn ($query) => $query->where('status', 'active')->with('flat.block')])
            ->orderBy('name')
            ->get();

        // Group by the user's primary active flat's block - falls back to
        // their first active residency if none is marked primary, and to
        // "Unassigned" for users with no flat at all (e.g. a committee
        // member who isn't also a resident).
        $grouped = $users->groupBy(function (User $user) {
            $residency = $user->residencies->firstWhere('is_primary', true) ?? $user->residencies->first();

            return $residency?->flat?->block?->name ?? 'Unassigned';
        })->sortKeys();

        $roles = Role::where('name', '!=', 'super_admin')->orderByDesc('priority')->get();

        return view('admin.societies.users.index', compact('society', 'grouped', 'roles'));
    }

    /**
     * Assign (or clear) one role for one user. Delete-then-insert rather
     * than update() so it works whether the user currently has zero or one
     * role row - a plain update() silently does nothing for a user who has
     * never had a role assigned yet.
     */
    public function updateRole(Request $request, int $societyId, int $userId): RedirectResponse
    {
        $this->tenantService->switchConnection($societyId);

        $validated = $request->validate([
            'role_id' => 'nullable|exists:society.roles,id',
        ]);

        DB::connection('society')->table('role_user')->where('user_id', $userId)->delete();

        if ($validated['role_id'] ?? null) {
            DB::connection('society')->table('role_user')->insert([
                'user_id' => $userId,
                'role_id' => $validated['role_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.societies.users.index', $societyId)
            ->with('success', 'Role updated successfully.');
    }
}
