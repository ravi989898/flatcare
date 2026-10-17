<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlockRequest;
use App\Http\Requests\Admin\StoreFlatsRequest;
use App\Http\Requests\Admin\UpdateFlatRequest;
use App\Models\Society;
use App\Models\Tenant\Block;
use App\Models\Tenant\Flat;
use App\Models\Tenant\FlatResident;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use App\Services\TenantService;
use App\Support\ListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Lets Super Admin build out a society's physical structure - its Blocks,
 * then the Flats inside each block - from the platform admin panel, ahead
 * of the society admin populating residents/billing against them.
 */
class SocietyStructureController extends Controller
{
    public function __construct(protected TenantService $tenantService) {}

    /**
     * List blocks for a society.
     */
    public function blocksIndex(Request $request, int $societyId): View
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $query = Block::withCount('flats');

        ListQuery::applySearch($query, $request, ['name', 'block_number']);

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        ListQuery::applySort($query, $request, ['name' => 'name', 'flats' => 'flats_count', 'status' => 'status'], 'name', 'asc');

        $blocks = $query->paginate(ListQuery::perPage($request, 25))->withQueryString();

        return view('admin.societies.blocks.index', compact('society', 'blocks'));
    }

    public function blocksCreate(int $societyId): View
    {
        $society = Society::findOrFail($societyId);

        return view('admin.societies.blocks.create', compact('society'));
    }

    public function blocksStore(BlockRequest $request, int $societyId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $validated = $request->validated();

        Block::create([...$validated, 'name' => $validated['block_number'], 'status' => 'active']);

        return redirect()
            ->route('admin.societies.blocks.index', $societyId)
            ->with('success', 'Block created successfully.');
    }

    public function blocksEdit(int $societyId, int $blockId): View
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);

        return view('admin.societies.blocks.edit', compact('society', 'block'));
    }

    public function blocksUpdate(BlockRequest $request, int $societyId, int $blockId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);
        $validated = $request->validated();

        $block->update([...$validated, 'name' => $validated['block_number']]);

        return redirect()
            ->route('admin.societies.blocks.index', $societyId)
            ->with('success', 'Block updated successfully.');
    }

    /**
     * Toggle a block between active/inactive. A block can also carry
     * under_construction status from the create/edit form, but this list
     * only offers the active/inactive switch - toggling from under
     * construction simply lands on active.
     */
    public function blocksToggleStatus(int $societyId, int $blockId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);
        $block->update(['status' => $block->status === 'active' ? 'inactive' : 'active']);

        return redirect()
            ->route('admin.societies.blocks.index', $societyId)
            ->with('success', "Block {$block->block_number} marked ".($block->status === 'active' ? 'active' : 'inactive').'.');
    }

    public function blocksDestroy(int $societyId, int $blockId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::withCount('flats')->findOrFail($blockId);

        if ($block->flats_count > 0) {
            return back()->with('error', "\"{$block->name}\" still has {$block->flats_count} flat(s) - remove those first.");
        }

        // Hard delete, not soft: block_number has a DB-level unique
        // constraint that isn't deleted_at-aware, so a soft-deleted block
        // would permanently block reusing its number. Safe here because
        // the flats_count check above guarantees nothing references it.
        $block->forceDelete();

        return redirect()
            ->route('admin.societies.blocks.index', $societyId)
            ->with('success', 'Block deleted successfully.');
    }

    /**
     * List flats within one block.
     */
    public function flatsIndex(Request $request, int $societyId, int $blockId): View
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);
        $query = Flat::where('block_id', $block->id);

        ListQuery::applySearch($query, $request, ['flat_number', 'owner_name', 'mobile_number']);

        foreach (['status', 'ownership_type'] as $filter) {
            if ($value = $request->string($filter)->trim()->value()) {
                $query->where($filter, $value);
            }
        }

        ListQuery::applySort($query, $request, [
            'flat' => fn ($q, $dir) => $q->orderByRaw('LENGTH(flat_number) '.$dir)->orderBy('flat_number', $dir),
            'floor' => fn ($q, $dir) => $q->orderByRaw('CAST(floor_number AS SIGNED) '.$dir),
            'owner' => 'owner_name',
            'status' => 'status',
        ], 'flat', 'asc');

        $flats = $query->paginate(ListQuery::perPage($request, 25))->withQueryString();

        return view('admin.societies.blocks.flats.index', compact('society', 'block', 'flats'));
    }

    public function flatsCreate(int $societyId, int $blockId): View
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);

        return view('admin.societies.blocks.flats.create', compact('society', 'block'));
    }

    /**
     * Create one or many flats at once - the textarea on the Add Flat page
     * accepts a flat number per line (or comma-separated) so a block with
     * dozens of units doesn't need one form submission each.
     */
    public function flatsStore(StoreFlatsRequest $request, int $societyId, int $blockId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);

        $numbers = collect(preg_split('/[\r\n,]+/', $request->string('flat_numbers')->value()))
            ->map(fn ($number) => trim($number))
            ->filter()
            ->unique();

        if ($numbers->isEmpty()) {
            return back()->withInput()->with('error', 'Enter at least one flat number.');
        }

        // flat_number carries a DB-level unique constraint that isn't
        // deleted_at-aware, so a previously-deleted flat with this number
        // still occupies it. withTrashed() so those show up here instead
        // of being silently missed and then crashing on insert below.
        $matches = Flat::withTrashed()->whereIn('flat_number', $numbers)->get()->keyBy('flat_number');
        $active = $matches->reject(fn (Flat $flat) => $flat->trashed())->keys();
        $restorable = $matches->filter(fn (Flat $flat) => $flat->trashed());
        $toCreate = $numbers->diff($matches->keys());

        foreach ($toCreate as $flatNumber) {
            Flat::create([
                'block_id' => $block->id,
                'flat_number' => $flatNumber,
                'floor_number' => $this->deriveFloorNumber($flatNumber),
                'flat_type' => '2BHK',
                'ownership_type' => 'vacant',
                'status' => 'active',
            ]);
        }

        foreach ($restorable as $flat) {
            $flat->restore();
            $flat->update([
                'block_id' => $block->id,
                'floor_number' => $this->deriveFloorNumber($flat->flat_number),
                'ownership_type' => 'vacant',
                'owner_name' => null,
                'status' => 'active',
            ]);
        }

        $block->increment('total_flats', $toCreate->count() + $restorable->count());

        $message = "{$toCreate->count()} flat".($toCreate->count() === 1 ? '' : 's').' created.';
        if ($restorable->isNotEmpty()) {
            $message .= " {$restorable->count()} previously-deleted flat".($restorable->count() === 1 ? '' : 's').' restored: '.$restorable->keys()->implode(', ').'.';
        }
        if ($active->isNotEmpty()) {
            $message .= ' Already existed, skipped: '.$active->implode(', ').'.';
        }

        return redirect()
            ->route('admin.societies.blocks.flats.index', [$societyId, $blockId])
            ->with('success', $message);
    }

    /**
     * Best-effort floor guess from a flat number's trailing digits (e.g.
     * "A-201" -> floor 2, "305" -> floor 3) so the required floor_number
     * column has something sensible without asking for it up front. Falls
     * back to floor 1 for numbers that don't fit that pattern.
     */
    private function deriveFloorNumber(string $flatNumber): string
    {
        if (preg_match('/(\d+)$/', $flatNumber, $match) && strlen($match[1]) >= 3) {
            return (string) (int) substr($match[1], 0, -2);
        }

        return '1';
    }

    public function flatsEdit(int $societyId, int $blockId, int $flatId): View
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);
        $flat = Flat::where('block_id', $block->id)->findOrFail($flatId);

        $residentUser = FlatResident::where('flat_id', $flat->id)
            ->active()
            ->orderByDesc('is_primary')
            ->with('user')
            ->first()
            ?->user;

        return view('admin.societies.blocks.flats.edit', compact('society', 'block', 'flat', 'residentUser'));
    }

    public function flatsUpdate(UpdateFlatRequest $request, int $societyId, int $blockId, int $flatId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);
        $flat = Flat::where('block_id', $block->id)->findOrFail($flatId);
        $validated = $request->validated();

        $flat->update([
            'flat_number' => $validated['flat_number'],
            'mobile_number' => $validated['mobile_number'] ?: null,
            'floor_number' => $this->deriveFloorNumber($validated['flat_number']),
        ]);

        $this->syncResident($flat, trim((string) ($validated['resident_name'] ?? '')), trim((string) ($validated['resident_email'] ?? '')));

        return redirect()
            ->route('admin.societies.blocks.flats.index', [$societyId, $blockId])
            ->with('success', 'Flat updated successfully.');
    }

    /**
     * Writes Name/Email through to the flat's linked resident User, the
     * same account the FlatCare app's Profile screen edits (see
     * Api\V1\Resident\ProfileController::update()) — so this form and the
     * resident's own app stay pointed at one record instead of drifting.
     * Mirrors Api\V1\Auth\OtpAuthController::findOrCreateResident(): if no
     * resident is linked yet but a mobile number is set, provisions the
     * account now instead of waiting for the resident's first OTP login.
     * Same logic as Society\BlockController::syncResident() — this is the
     * Super Admin's cross-tenant equivalent.
     */
    private function syncResident(Flat $flat, string $name, string $email): void
    {
        $residency = FlatResident::where('flat_id', $flat->id)->active()->orderByDesc('is_primary')->with('user')->first();

        if ($residency) {
            $updates = array_filter(['name' => $name ?: null, 'email' => $email ?: null]);

            if ($updates) {
                $residency->user->update($updates);
            }

            return;
        }

        if (!$flat->mobile_number) {
            return;
        }

        $user = User::where('phone', $flat->mobile_number)->first();

        if (!$user) {
            $user = User::create([
                'name' => $name ?: "Resident of {$flat->flat_number}",
                // users.email is required + unique — same placeholder scheme
                // as findOrCreateResident() when the admin leaves it blank.
                'email' => $email ?: Str::uuid().'@resident.flatcare.local',
                'phone' => $flat->mobile_number,
                'password' => Hash::make(Str::password(20)),
                'country' => 'India',
                'status' => 'active',
            ]);

            if ($residentRole = Role::where('name', 'resident')->first()) {
                $user->assignRole($residentRole);
            }
        } elseif ($name || $email) {
            $user->update(array_filter(['name' => $name ?: null, 'email' => $email ?: null]));
        }

        FlatResident::create([
            'flat_id' => $flat->id,
            'user_id' => $user->id,
            'resident_type' => 'owner',
            'moved_in_date' => now(),
            'status' => 'active',
            'is_primary' => !FlatResident::where('flat_id', $flat->id)->active()->exists(),
        ]);
    }

    /**
     * Toggle a flat between active and inactive. Flats can also carry
     * under_construction/under_maintenance status from other flows, but
     * this list only offers the active/inactive switch - toggling from
     * either of those simply lands on active.
     */
    public function flatsToggleStatus(int $societyId, int $blockId, int $flatId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);
        $flat = Flat::where('block_id', $block->id)->findOrFail($flatId);

        $flat->update(['status' => $flat->status === 'active' ? 'inactive' : 'active']);

        return redirect()
            ->route('admin.societies.blocks.flats.index', [$societyId, $blockId])
            ->with('success', "Flat {$flat->flat_number} marked ".($flat->status === 'active' ? 'active' : 'inactive').'.');
    }

    public function flatsDestroy(int $societyId, int $blockId, int $flatId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);
        $flat = Flat::where('block_id', $block->id)->findOrFail($flatId);
        $flat->delete();

        $block->decrement('total_flats');

        return redirect()
            ->route('admin.societies.blocks.flats.index', [$societyId, $blockId])
            ->with('success', 'Flat deleted successfully.');
    }
}
