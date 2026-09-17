<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetSocietyAdminPasswordRequest;
use App\Http\Requests\Admin\StoreSocietyAdminRequest;
use App\Http\Requests\Admin\UpdateSocietyAdminRequest;
use App\Models\Society;
use App\Models\Tenant\User;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SocietyAdminController extends Controller
{
    protected TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Display list of admins for a society
     */
    public function index(Request $request, int $societyId)
    {
        $society = Society::findOrFail($societyId);

        // Switch to society's database
        $this->tenantService->switchConnection($societyId);

        // Any user holding an elevated role granted from this screen —
        // Society Admin, Committee Member, Chairman, Treasurer, etc. — not
        // just the literal 'admin' role, since the create form below lets
        // you grant any of those roles.
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', '!=', 'resident');
        })
            ->with('roles')
            ->paginate(10);

        return view('admin.societies.admins.index', compact('society', 'admins'));
    }

    /**
     * Show create admin form
     */
    public function create(Request $request, int $societyId)
    {
        $society = Society::findOrFail($societyId);

        // Switch to society's database
        $this->tenantService->switchConnection($societyId);

        // Get available roles
        $roles = DB::connection('society')
            ->table('roles')
            ->where('name', '!=', 'super_admin')
            ->get();

        // Only plain residents (no elevated role yet) can be promoted here.
        $users = User::whereDoesntHave('roles', fn ($query) => $query->where('name', '!=', 'resident'))
            ->with(['residencies' => fn ($query) => $query->where('status', 'active')->with('flat')])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        return view('admin.societies.admins.create', compact('society', 'roles', 'users'));
    }

    /**
     * Promote an existing society user to admin by granting them a role.
     */
    public function store(StoreSocietyAdminRequest $request, int $societyId)
    {
        $society = Society::findOrFail($societyId);

        // Switch to society's database before validating so the exists
        // checks below run against this society's tables, not whichever
        // tenant a previous request happened to leave connected.
        $this->tenantService->switchConnection($societyId);

        $validated = $request->validated();

        DB::connection('society')
            ->table('users')
            ->where('id', $validated['user_id'])
            ->update([
                'password' => Hash::make($validated['password']),
                'updated_at' => now(),
            ]);

        // insertOrIgnore: the user may already hold this role.
        DB::connection('society')
            ->table('role_user')
            ->insertOrIgnore([
                'user_id' => $validated['user_id'],
                'role_id' => $validated['role'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.societies.admins.index', $societyId)
            ->with('success', 'Admin user created successfully');
    }

    /**
     * Show edit admin form
     */
    public function edit(Request $request, int $societyId, int $adminId)
    {
        $society = Society::findOrFail($societyId);

        // Switch to society's database
        $this->tenantService->switchConnection($societyId);

        $admin = User::with('roles')->findOrFail($adminId);

        $roles = DB::connection('society')
            ->table('roles')
            ->where('name', '!=', 'super_admin')
            ->get();

        // Prefer the elevated role over 'resident' — a promoted user holds
        // both, and roles->first() is otherwise just insertion order.
        $adminRole = $admin->roles->firstWhere('name', '!=', 'resident') ?? $admin->roles->first();

        return view('admin.societies.admins.edit', compact('society', 'admin', 'roles', 'adminRole'));
    }

    /**
     * Update society admin
     */
    public function update(UpdateSocietyAdminRequest $request, int $societyId, int $adminId)
    {
        $society = Society::findOrFail($societyId);

        // Switch to society's database before validating so the unique
        // checks below run against this society's users table.
        $this->tenantService->switchConnection($societyId);

        $validated = $request->validated();

        // Update user
        DB::connection('society')
            ->table('users')
            ->where('id', $adminId)
            ->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'updated_at' => now(),
            ]);

        $this->replaceElevatedRole($adminId, (int) $validated['role']);

        return redirect()
            ->route('admin.societies.admins.index', $societyId)
            ->with('success', 'Admin user updated successfully');
    }

    /**
     * Swaps a user's elevated (non-resident) role for a new one, leaving
     * their base 'resident' role_user row untouched. A plain "update the
     * role_id on every role_user row for this user" would try to collapse
     * both rows onto the same role_id and hit the (user_id, role_id)
     * unique constraint whenever the user holds both roles at once —
     * exactly the case since Add Admin promotes an existing resident
     * rather than replacing their resident role.
     */
    private function replaceElevatedRole(int $userId, int $roleId): void
    {
        $residentRoleId = DB::connection('society')->table('roles')->where('name', 'resident')->value('id');

        DB::connection('society')
            ->table('role_user')
            ->where('user_id', $userId)
            ->when($residentRoleId, fn ($query) => $query->where('role_id', '!=', $residentRoleId))
            ->delete();

        DB::connection('society')
            ->table('role_user')
            ->insertOrIgnore([
                'user_id' => $userId,
                'role_id' => $roleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Activate admin
     */
    public function activate(Request $request, int $societyId, int $adminId)
    {
        $society = Society::findOrFail($societyId);

        // Switch to society's database
        $this->tenantService->switchConnection($societyId);

        DB::connection('society')
            ->table('users')
            ->where('id', $adminId)
            ->update(['status' => 'active']);

        return redirect()
            ->route('admin.societies.admins.index', $societyId)
            ->with('success', 'Admin user activated successfully');
    }

    /**
     * Deactivate admin
     */
    public function deactivate(Request $request, int $societyId, int $adminId)
    {
        $society = Society::findOrFail($societyId);

        // Switch to society's database
        $this->tenantService->switchConnection($societyId);

        DB::connection('society')
            ->table('users')
            ->where('id', $adminId)
            ->update(['status' => 'inactive']);

        return redirect()
            ->route('admin.societies.admins.index', $societyId)
            ->with('success', 'Admin user deactivated successfully');
    }

    /**
     * List this society's admin accounts so the Super Admin can reset one
     * directly — for when a Society Admin is locked out and has no way to
     * receive a self-service reset link (login spans every tenant database,
     * so there's no single "forgot password" page to send one from).
     */
    public function resetPasswordIndex(Request $request, int $societyId)
    {
        $society = Society::findOrFail($societyId);

        $this->tenantService->switchConnection($societyId);

        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', '!=', 'resident');
        })
            ->orderBy('name')
            ->get();

        return view('admin.societies.reset_password', compact('society', 'admins'));
    }

    /**
     * Force-set a society admin's password. Distinct from update() above:
     * this only ever touches the password, so the Super Admin doesn't need
     * to re-enter (or risk overwriting) the admin's name/email/role/status.
     */
    public function resetPassword(ResetSocietyAdminPasswordRequest $request, int $societyId, int $adminId)
    {
        $society = Society::findOrFail($societyId);

        $this->tenantService->switchConnection($societyId);

        $validated = $request->validated();

        DB::connection('society')
            ->table('users')
            ->where('id', $adminId)
            ->update([
                'password' => Hash::make($validated['password']),
                'updated_at' => now(),
            ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Password reset successfully']);
        }

        return redirect()
            ->route('admin.societies.reset_password', $societyId)
            ->with('success', 'Password reset successfully');
    }

    /**
     * Delete admin
     */
    public function destroy(Request $request, int $societyId, int $adminId)
    {
        $society = Society::findOrFail($societyId);

        // Switch to society's database
        $this->tenantService->switchConnection($societyId);

        DB::connection('society')
            ->table('users')
            ->where('id', $adminId)
            ->update(['status' => 'deleted', 'deleted_at' => now()]);

        return redirect()
            ->route('admin.societies.admins.index', $societyId)
            ->with('success', 'Admin user deleted successfully');
    }
}
