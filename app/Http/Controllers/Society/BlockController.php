<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Society\BlockRequest;
use App\Http\Requests\Society\StoreFlatsRequest;
use App\Http\Requests\Society\UpdateFlatRequest;
use App\Models\Tenant\Block;
use App\Models\Tenant\Flat;
use App\Models\Tenant\FlatResident;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Lets a Society Admin manage their own society's physical structure -
 * Blocks, then the Flats inside each block - without needing the platform
 * Super Admin (App\Http\Controllers\Admin\SocietyStructureController does
 * the same thing cross-tenant, for provisioning a new society). Same
 * behaviour, just scoped to "whichever tenant this session is signed into"
 * instead of taking a society id from the URL.
 */
class BlockController extends Controller
{
    public function blocksIndex(): View
    {
        $blocks = Block::withCount('flats')->orderBy('name')->paginate(15);

        return view('society.blocks.index', compact('blocks'));
    }

    public function blocksCreate(): View
    {
        return view('society.blocks.create');
    }

    public function blocksStore(BlockRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Block::create([...$validated, 'name' => $validated['block_number'], 'status' => 'active']);

        return redirect()
            ->route('society.blocks.index')
            ->with('success', 'Block created successfully.');
    }

    public function blocksEdit(int $blockId): View
    {
        $block = Block::findOrFail($blockId);

        return view('society.blocks.edit', compact('block'));
    }

    public function blocksUpdate(BlockRequest $request, int $blockId): RedirectResponse
    {
        $block = Block::findOrFail($blockId);
        $validated = $request->validated();

        $block->update([...$validated, 'name' => $validated['block_number']]);

        return redirect()
            ->route('society.blocks.index')
            ->with('success', 'Block updated successfully.');
    }

    public function blocksDestroy(int $blockId): RedirectResponse
    {
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
            ->route('society.blocks.index')
            ->with('success', 'Block deleted successfully.');
    }

    public function flatsIndex(int $blockId): View
    {
        $block = Block::findOrFail($blockId);
        $flats = Flat::where('block_id', $block->id)
            ->with(['residents' => fn ($query) => $query->active()->orderByDesc('is_primary')->with('user:id,name')])
            ->orderBy('flat_number')
            ->paginate(20);

        return view('society.blocks.flats.index', compact('block', 'flats'));
    }

    public function flatsCreate(int $blockId): View
    {
        $block = Block::findOrFail($blockId);

        return view('society.blocks.flats.create', compact('block'));
    }

    /**
     * Create one or many flats at once - the textarea on the Add Flat page
     * accepts a flat number per line (or comma-separated) so a block with
     * dozens of units doesn't need one form submission each.
     */
    public function flatsStore(StoreFlatsRequest $request, int $blockId): RedirectResponse
    {
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
                'mobile_number' => null,
                'status' => 'active',
            ]);

            // A restored flat should start resident-less like a brand-new
            // one - otherwise it keeps showing whoever lived there before
            // it was deleted (incl. on any other flat that reused the same
            // account via the phone-number lookup in syncResident()).
            FlatResident::where('flat_id', $flat->id)->active()->update([
                'status' => 'moved_out',
                'moved_out_date' => now(),
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
            ->route('society.blocks.flats.index', $blockId)
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

    public function flatsEdit(int $blockId, int $flatId): View
    {
        $block = Block::findOrFail($blockId);
        $flat = Flat::where('block_id', $block->id)->findOrFail($flatId);

        $residentUser = FlatResident::where('flat_id', $flat->id)
            ->active()
            ->orderByDesc('is_primary')
            ->with('user')
            ->first()
            ?->user;

        return view('society.blocks.flats.edit', compact('block', 'flat', 'residentUser'));
    }

    public function flatsUpdate(UpdateFlatRequest $request, int $blockId, int $flatId): RedirectResponse
    {
        $block = Block::findOrFail($blockId);
        $flat = Flat::where('block_id', $block->id)->findOrFail($flatId);
        $validated = $request->validated();

        $flat->update([
            'flat_number' => $validated['flat_number'],
            'mobile_number' => $validated['mobile_number'] ?: null,
            'floor_number' => $this->deriveFloorNumber($validated['flat_number']),
        ]);

        $warning = $this->syncResident($flat, trim((string) ($validated['resident_name'] ?? '')), trim((string) ($validated['resident_email'] ?? '')));

        $redirect = redirect()
            ->route('society.blocks.flats.index', $blockId)
            ->with('success', 'Flat updated successfully.');

        return $warning ? $redirect->with('warning', $warning) : $redirect;
    }

    /**
     * Writes Name/Email through to the flat's linked resident User, the
     * same account the FlatCare app's Profile screen edits (see
     * Api\V1\Resident\ProfileController::update()) — so this form and the
     * resident's own app stay pointed at one record instead of drifting.
     * Mirrors Api\V1\Auth\OtpAuthController::findOrCreateResident(): if no
     * resident is linked yet but a mobile number is set, provisions the
     * account now instead of waiting for the resident's first OTP login.
     */
    private function syncResident(Flat $flat, string $name, string $email): ?string
    {
        $residency = FlatResident::where('flat_id', $flat->id)->active()->orderByDesc('is_primary')->with('user')->first();

        if ($residency) {
            $updates = array_filter(['name' => $name ?: null, 'email' => $email ?: null]);

            if ($updates) {
                $residency->user->update($updates);
            }

            return null;
        }

        if (!$flat->mobile_number) {
            return null;
        }

        $user = User::where('phone', $flat->mobile_number)->first();

        if ($user && FlatResident::where('user_id', $user->id)->where('flat_id', '!=', $flat->id)->active()->exists()) {
            // That phone number's account is already the active resident of
            // a different flat - attaching it here too would make both
            // flats show the same resident name/email. Skip instead of
            // silently double-linking one account to two flats.
            return "Mobile number {$flat->mobile_number} is already linked to another flat's resident - resident not updated.";
        }

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

        return null;
    }

    /**
     * Toggle a flat between active and inactive. Flats can also carry
     * under_construction/under_maintenance status from other flows, but
     * this list only offers the active/inactive switch - toggling from
     * either of those simply lands on active.
     */
    public function flatsToggleStatus(int $blockId, int $flatId): RedirectResponse
    {
        $block = Block::findOrFail($blockId);
        $flat = Flat::where('block_id', $block->id)->findOrFail($flatId);

        $flat->update(['status' => $flat->status === 'active' ? 'inactive' : 'active']);

        return redirect()
            ->route('society.blocks.flats.index', $blockId)
            ->with('success', "Flat {$flat->flat_number} marked ".($flat->status === 'active' ? 'active' : 'inactive').'.');
    }

    public function flatsDestroy(int $blockId, int $flatId): RedirectResponse
    {
        $block = Block::findOrFail($blockId);
        $flat = Flat::where('block_id', $block->id)->findOrFail($flatId);
        $flat->delete();

        $block->decrement('total_flats');

        return redirect()
            ->route('society.blocks.flats.index', $blockId)
            ->with('success', 'Flat deleted successfully.');
    }
}
