<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Society;
use App\Models\Tenant\Block;
use App\Models\Tenant\Flat;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function blocksIndex(int $societyId): View
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $blocks = Block::withCount('flats')->orderBy('name')->paginate(15);

        return view('admin.societies.blocks.index', compact('society', 'blocks'));
    }

    public function blocksCreate(int $societyId): View
    {
        $society = Society::findOrFail($societyId);

        return view('admin.societies.blocks.create', compact('society'));
    }

    public function blocksStore(Request $request, int $societyId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $validated = $this->validateBlock($request);

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

    public function blocksUpdate(Request $request, int $societyId, int $blockId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);
        $validated = $this->validateBlock($request, $block->id);

        $block->update([...$validated, 'name' => $validated['block_number']]);

        return redirect()
            ->route('admin.societies.blocks.index', $societyId)
            ->with('success', 'Block updated successfully.');
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
    public function flatsIndex(int $societyId, int $blockId): View
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);
        $flats = Flat::where('block_id', $block->id)->orderBy('flat_number')->paginate(20);

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
    public function flatsStore(Request $request, int $societyId, int $blockId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);

        $request->validate([
            'flat_numbers' => 'required|string',
        ]);

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

        $message = "{$toCreate->count()} flat" . ($toCreate->count() === 1 ? '' : 's') . ' created.';
        if ($restorable->isNotEmpty()) {
            $message .= " {$restorable->count()} previously-deleted flat" . ($restorable->count() === 1 ? '' : 's') . ' restored: ' . $restorable->keys()->implode(', ') . '.';
        }
        if ($active->isNotEmpty()) {
            $message .= ' Already existed, skipped: ' . $active->implode(', ') . '.';
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

        return view('admin.societies.blocks.flats.edit', compact('society', 'block', 'flat'));
    }

    public function flatsUpdate(Request $request, int $societyId, int $blockId, int $flatId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);
        $flat = Flat::where('block_id', $block->id)->findOrFail($flatId);
        $validated = $this->validateFlat($request, $flat->id);

        $flat->update([
            'flat_number' => $validated['flat_number'],
            'floor_number' => $this->deriveFloorNumber($validated['flat_number']),
        ]);

        return redirect()
            ->route('admin.societies.blocks.flats.index', [$societyId, $blockId])
            ->with('success', 'Flat updated successfully.');
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
            ->with('success', "Flat {$flat->flat_number} marked " . ($flat->status === 'active' ? 'active' : 'inactive') . '.');
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

    private function validateBlock(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'block_number' => 'required|string|max:255|unique:society.blocks,block_number' . ($ignoreId ? ",{$ignoreId}" : ''),
        ]);
    }

    private function validateFlat(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'flat_number' => 'required|string|max:255|unique:society.flats,flat_number' . ($ignoreId ? ",{$ignoreId}" : ''),
        ]);
    }
}
