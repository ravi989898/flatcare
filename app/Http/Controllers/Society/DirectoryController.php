<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Block;
use App\Models\Tenant\Flat;
use App\Models\Tenant\FlatResident;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DirectoryController extends Controller
{
    /**
     * Searchable resident directory, one row per active residency (a
     * resident can show up more than once if they occupy multiple flats).
     */
    public function index(Request $request): View
    {
        $query = FlatResident::query()->active()->with(['user', 'flat.block']);

        if ($blockId = $request->string('block_id')->trim()->value()) {
            $query->whereHas('flat', fn ($q) => $q->where('block_id', $blockId));
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('flat', function ($f) use ($search) {
                    $f->where('flat_number', 'like', "%{$search}%");
                });
            });
        }

        $residencies = $query->orderByDesc('is_primary')->latest()->paginate(15)->withQueryString();
        $blocks = Block::active()->orderBy('name')->get();
        $residentCount = FlatResident::active()->count();

        return view('society.directory.index', compact('residencies', 'blocks', 'residentCount'));
    }

    /**
     * Show the add-resident form.
     */
    public function create(): View
    {
        $flats = Flat::active()->with('block')->orderBy('flat_number')->get();

        return view('society.directory.create', compact('flats'));
    }

    /**
     * Onboard a new resident: create their tenant account, assign the
     * 'user' role, and link them to a flat. There's no resident-facing
     * portal yet, so the generated password isn't handed out anywhere —
     * this just gets the resident into the system so other modules
     * (visitors, complaints, maintenance) can reference them.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'flat_id' => 'required|exists:flats,id',
            'resident_type' => 'required|in:owner,tenant,occupant',
            'is_primary' => 'boolean',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
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
            ->route('society.directory.show', $user->id)
            ->with('success', "{$user->name} added to the directory.");
    }

    /**
     * Resident profile: contact info, flat(s), family members, vehicles.
     */
    public function show(int $userId): View
    {
        $resident = User::with(['residencies.flat.block', 'familyMembers', 'vehicles', 'roles'])->findOrFail($userId);

        return view('society.directory.show', compact('resident'));
    }

    /**
     * Set/clear a resident's Committee Members listing (mockup's Community
     * tab) — see the committee_position/committee_order columns' migration
     * docblock for why this is two plain fields rather than an RBAC role.
     */
    public function updateCommittee(Request $request, int $userId): RedirectResponse
    {
        $resident = User::findOrFail($userId);

        $validated = $request->validate([
            'committee_position' => 'nullable|string|max:100',
            'committee_order' => 'nullable|integer|min:0',
        ]);

        $resident->update([
            'committee_position' => $validated['committee_position'] ?: null,
            'committee_order' => $validated['committee_order'] ?? 0,
        ]);

        return redirect()
            ->route('society.directory.show', $resident->id)
            ->with('success', 'Committee details updated.');
    }
}
