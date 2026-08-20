<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        // Get admin users
        $admins = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
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

        return view('admin.societies.admins.create', compact('society', 'roles'));
    }

    /**
     * Store new society admin
     */
    public function store(Request $request, int $societyId)
    {
        $society = Society::findOrFail($societyId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|digits:10|unique:users,phone',
            'password' => 'required|min:10|confirmed',
            'role' => 'required|exists:roles,id',
        ]);

        // Switch to society's database
        $this->tenantService->switchConnection($societyId);

        // Create user
        $user = DB::connection('society')
            ->table('users')
            ->insertGetId([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'country' => 'India',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // Assign role
        DB::connection('society')
            ->table('role_user')
            ->insert([
                'user_id' => $user,
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

        $adminRole = $admin->roles->first();

        return view('admin.societies.admins.edit', compact('society', 'admin', 'roles', 'adminRole'));
    }

    /**
     * Update society admin
     */
    public function update(Request $request, int $societyId, int $adminId)
    {
        $society = Society::findOrFail($societyId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $adminId,
            'phone' => 'required|digits:10|unique:users,phone,' . $adminId,
            'password' => 'nullable|min:10|confirmed',
            'role' => 'required|exists:roles,id',
        ]);

        // Switch to society's database
        $this->tenantService->switchConnection($societyId);

        $updates = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'updated_at' => now(),
        ];

        if ($validated['password'] ?? null) {
            $updates['password'] = Hash::make($validated['password']);
        }

        // Update user
        DB::connection('society')
            ->table('users')
            ->where('id', $adminId)
            ->update($updates);

        // Update role
        DB::connection('society')
            ->table('role_user')
            ->where('user_id', $adminId)
            ->update(['role_id' => $validated['role']]);

        return redirect()
            ->route('admin.societies.admins.index', $societyId)
            ->with('success', 'Admin user updated successfully');
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
