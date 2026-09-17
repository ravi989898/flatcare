<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetSocietyAdminPasswordRequest;
use App\Http\Requests\Society\AdminUserRequest;
use App\Http\Requests\Society\StoreAdminUserRequest;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Lets a Society Admin manage their own society's other admin accounts,
 * without needing the platform Super Admin to do it from the master panel
 * (App\Http\Controllers\Admin\SocietyAdminController does the same thing
 * cross-tenant, for Super Admin's use). SetSocietyContext has already
 * pointed the 'society' connection at the right tenant database, so no
 * TenantService switch is needed here.
 */
class AdminController extends Controller
{
    public function index(): View
    {
        // Any user holding an elevated role granted from this screen —
        // Society Admin, Committee Member, Chairman, Treasurer, etc. — not
        // just the literal 'admin' role, since the create form below lets
        // you grant any of those roles.
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', '!=', 'resident');
        })
            ->with('roles')
            ->orderBy('name')
            ->paginate(10);

        return view('society.admins.index', compact('admins'));
    }

    public function create(): View
    {
        $roles = DB::connection('society')
            ->table('roles')
            ->where('name', '!=', 'super_admin')
            ->get();

        // Only plain residents (no elevated role yet) can be promoted here.
        $users = User::whereDoesntHave('roles', fn ($query) => $query->where('name', '!=', 'resident'))
            ->with(['residencies' => fn ($query) => $query->where('status', 'active')->with('flat')])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        return view('society.admins.create', compact('roles', 'users'));
    }

    /**
     * Promote an existing society user to admin by granting them a role.
     */
    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
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
            ->route('society.admins.index')
            ->with('success', 'Admin user created successfully.');
    }

    public function edit(int $adminId): View
    {
        $admin = User::with('roles')->findOrFail($adminId);

        $roles = DB::connection('society')
            ->table('roles')
            ->where('name', '!=', 'super_admin')
            ->get();

        // Prefer the elevated role over 'resident' — a promoted user holds
        // both, and roles->first() is otherwise just insertion order.
        $adminRole = $admin->roles->firstWhere('name', '!=', 'resident') ?? $admin->roles->first();

        return view('society.admins.edit', compact('admin', 'roles', 'adminRole'));
    }

    public function update(AdminUserRequest $request, int $adminId): RedirectResponse
    {
        $validated = $request->validated();

        DB::connection('society')->table('users')->where('id', $adminId)->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'updated_at' => now(),
        ]);

        $this->replaceElevatedRole($adminId, (int) $validated['role']);

        return redirect()
            ->route('society.admins.index')
            ->with('success', 'Admin user updated successfully.');
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

    public function activate(int $adminId): RedirectResponse
    {
        DB::connection('society')->table('users')->where('id', $adminId)->update(['status' => 'active']);

        return redirect()
            ->route('society.admins.index')
            ->with('success', 'Admin user activated successfully.');
    }

    public function deactivate(int $adminId): RedirectResponse
    {
        DB::connection('society')->table('users')->where('id', $adminId)->update(['status' => 'inactive']);

        return redirect()
            ->route('society.admins.index')
            ->with('success', 'Admin user deactivated successfully.');
    }

    /**
     * Force-set a fellow admin's password from within the society portal
     * itself. Mirrors Admin\SocietyAdminController::resetPassword() (the
     * Super Admin's cross-tenant equivalent) — same request rules, same
     * JSON-vs-redirect branch for the AJAX modal on the index page.
     */
    public function resetPassword(ResetSocietyAdminPasswordRequest $request, int $adminId): RedirectResponse|JsonResponse
    {
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
            ->route('society.admins.index')
            ->with('success', 'Password reset successfully.');
    }
}
