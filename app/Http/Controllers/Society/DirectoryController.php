<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Society\StoreResidentRequest;
use App\Http\Requests\Society\UpdateResidentCommitteeRequest;
use App\Models\Tenant\Block;
use App\Models\Tenant\Flat;
use App\Models\Tenant\FlatResident;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use App\Support\ListQuery;
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

        ListQuery::applySearch($query, $request, ['user.name', 'user.phone', 'user.email', 'flat.flat_number']);

        if (in_array($type = $request->string('resident_type')->value(), ['owner', 'tenant', 'occupant'], true)) {
            $query->where('resident_type', $type);
        }

        $userName = User::select('name')->whereColumn('users.id', 'flat_residents.user_id');
        $flatNumber = Flat::select('flat_number')->whereColumn('flats.id', 'flat_residents.flat_id');

        ListQuery::applySort($query, $request, [
            'recent' => fn ($q, $dir) => $q->orderByDesc('is_primary')->orderBy('created_at', $dir),
            'name' => fn ($q, $dir) => $q->orderBy($userName, $dir),
            'flat' => fn ($q, $dir) => $q->orderBy($flatNumber, $dir),
            'moved_in' => 'moved_in_date',
        ], 'recent');

        $residencies = $query->paginate(ListQuery::perPage($request, 25))->withQueryString();
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
    public function store(StoreResidentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
    public function updateCommittee(UpdateResidentCommitteeRequest $request, int $userId): RedirectResponse
    {
        $resident = User::findOrFail($userId);

        $validated = $request->validated();

        $resident->update([
            'committee_position' => $validated['committee_position'] ?: null,
            'committee_order' => $validated['committee_order'] ?? 0,
        ]);

        return redirect()
            ->route('society.directory.show', $resident->id)
            ->with('success', 'Committee details updated.');
    }
}
