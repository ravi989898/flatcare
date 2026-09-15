<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSocietyUserRequest;
use App\Http\Requests\Admin\UpdateSocietyUserRequest;
use App\Models\Society;
use App\Models\Tenant\Block;
use App\Models\Tenant\Flat;
use App\Models\Tenant\FlatResident;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Every resident in one society, grouped by Block -> Flat, plus the ability
 * for Super Admin to onboard one directly (mirrors what a Society Admin can
 * do for their own society via Society\DirectoryController::store()).
 * Admin-role accounts are excluded here and managed on their own dedicated
 * screen instead (Admin\SocietyAdminController).
 */
class SocietyUserController extends Controller
{
    public function __construct(protected TenantService $tenantService) {}

    public function index(Request $request, int $societyId): View
    {
        $society = Society::findOrFail($societyId);

        $this->tenantService->switchConnection($societyId);

        $users = User::whereDoesntHave('roles', fn ($query) => $query->where('name', 'admin'))
            ->with(['roles', 'residencies' => fn ($query) => $query->where('status', 'active')->with('flat.block')])
            ->orderBy('name')
            ->get();

        // Group by the user's primary active flat's block - falls back to
        // their first active residency if none is marked primary, and to
        // "Unassigned" for users with no flat at all.
        $grouped = $users->groupBy(function (User $user) {
            $residency = $user->residencies->firstWhere('is_primary', true) ?? $user->residencies->first();

            return $residency?->flat?->block?->name ?? 'Unassigned';
        })->sortKeys();

        return view('admin.societies.users.index', compact('society', 'grouped'));
    }

    public function create(int $societyId): View
    {
        $society = Society::findOrFail($societyId);

        $this->tenantService->switchConnection($societyId);

        $blocks = Block::active()->orderBy('name')->get();
        $flats = Flat::active()->with('block')->orderBy('flat_number')->get();

        return view('admin.societies.users.create', compact('society', 'blocks', 'flats'));
    }

    /**
     * Onboard a new resident: create their tenant account, assign the
     * 'user' role, and link them to a flat. Mirrors
     * Society\DirectoryController::store() — see that method's docblock.
     */
    public function store(StoreSocietyUserRequest $request, int $societyId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);

        $this->tenantService->switchConnection($societyId);

        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?: $this->placeholderEmail($validated['phone']),
            'phone' => $validated['phone'],
            'password' => Hash::make(Str::password(20)),
            'country' => 'India',
            'status' => 'active',
        ]);

        if ($residentRole = Role::where('name', 'resident')->first()) {
            $user->assignRole($residentRole);
        }

        FlatResident::create([
            'flat_id' => $validated['flat_id'],
            'user_id' => $user->id,
            'resident_type' => $validated['resident_type'],
            'moved_in_date' => now(),
            'status' => 'active',
            'is_primary' => $request->boolean('is_primary'),
        ]);

        return redirect()
            ->route('admin.societies.users.index', $society->id)
            ->with('success', "{$user->name} added successfully.");
    }

    public function edit(int $societyId, int $userId): View
    {
        $society = Society::findOrFail($societyId);

        $this->tenantService->switchConnection($societyId);

        $user = User::findOrFail($userId);
        $residency = $user->residencies()->orderByDesc('is_primary')->first();
        $blocks = Block::active()->orderBy('name')->get();
        $flats = Flat::active()->with('block')->orderBy('flat_number')->get();

        return view('admin.societies.users.edit', compact('society', 'user', 'residency', 'blocks', 'flats'));
    }

    /**
     * Update a resident's own fields plus their flat assignment. Users
     * created before flat assignment was tracked here (or edited by hand)
     * may have no FlatResident row yet, in which case one is created rather
     * than updated.
     */
    public function update(UpdateSocietyUserRequest $request, int $societyId, int $userId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);

        $this->tenantService->switchConnection($societyId);

        $user = User::findOrFail($userId);
        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?: $this->placeholderEmail($validated['phone']),
            'phone' => $validated['phone'],
        ]);

        $residency = $user->residencies()->orderByDesc('is_primary')->first();

        $residencyData = [
            'flat_id' => $validated['flat_id'],
            'resident_type' => $validated['resident_type'],
            'is_primary' => $request->boolean('is_primary'),
        ];

        if ($residency) {
            $residency->update($residencyData);
        } else {
            FlatResident::create([
                ...$residencyData,
                'user_id' => $user->id,
                'moved_in_date' => now(),
                'status' => 'active',
            ]);
        }

        return redirect()
            ->route('admin.societies.users.index', $society->id)
            ->with('success', "{$user->name} updated successfully.");
    }

    /**
     * users.email has a DB-level unique, non-nullable constraint, but the
     * resident app's OTP login is keyed on the flat's mobile_number (see
     * Api\V1\Auth\OtpAuthController), not this user record's email at all —
     * so email is optional in this form. Deterministic on phone (already
     * required + unique here) rather than random, so re-submitting the same
     * form twice can't collide.
     */
    private function placeholderEmail(string $phone): string
    {
        return "resident-{$phone}@placeholder.flatcare.local";
    }
}
