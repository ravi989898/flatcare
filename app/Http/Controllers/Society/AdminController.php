<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $admins = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
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

        return view('society.admins.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:society.users,email',
            'phone' => 'required|digits:10|unique:society.users,phone',
            'password' => 'required|min:10|confirmed',
            'role' => 'required|exists:society.roles,id',
        ]);

        $userId = DB::connection('society')
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

        DB::connection('society')
            ->table('role_user')
            ->insert([
                'user_id' => $userId,
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

        $adminRole = $admin->roles->first();

        return view('society.admins.edit', compact('admin', 'roles', 'adminRole'));
    }

    public function update(Request $request, int $adminId): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:society.users,email,' . $adminId,
            'phone' => 'required|digits:10|unique:society.users,phone,' . $adminId,
            'password' => 'nullable|min:10|confirmed',
            'role' => 'required|exists:society.roles,id',
        ]);

        $updates = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'updated_at' => now(),
        ];

        if ($validated['password'] ?? null) {
            $updates['password'] = Hash::make($validated['password']);
        }

        DB::connection('society')->table('users')->where('id', $adminId)->update($updates);

        DB::connection('society')
            ->table('role_user')
            ->where('user_id', $adminId)
            ->update(['role_id' => $validated['role']]);

        return redirect()
            ->route('society.admins.index')
            ->with('success', 'Admin user updated successfully.');
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
}
