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

        Block::create($validated);

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

        $block->update($validated);

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

        $block->delete();

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

    public function flatsStore(Request $request, int $societyId, int $blockId): RedirectResponse
    {
        $society = Society::findOrFail($societyId);
        $this->tenantService->switchConnection($societyId);

        $block = Block::findOrFail($blockId);
        $validated = $this->validateFlat($request);

        Flat::create([...$validated, 'block_id' => $block->id]);

        $block->increment('total_flats');

        return redirect()
            ->route('admin.societies.blocks.flats.index', [$societyId, $blockId])
            ->with('success', 'Flat created successfully.');
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

        $flat->update($validated);

        return redirect()
            ->route('admin.societies.blocks.flats.index', [$societyId, $blockId])
            ->with('success', 'Flat updated successfully.');
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
            'name' => 'required|string|max:255',
            'block_number' => 'required|string|max:255|unique:society.blocks,block_number' . ($ignoreId ? ",{$ignoreId}" : ''),
            'description' => 'nullable|string|max:255',
            'total_floors' => 'nullable|integer|min:0',
            'block_admin_contact' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,under_construction',
        ]);
    }

    private function validateFlat(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'flat_number' => 'required|string|max:255|unique:society.flats,flat_number' . ($ignoreId ? ",{$ignoreId}" : ''),
            'floor_number' => 'required|string|max:255',
            'flat_type' => 'required|in:1BHK,2BHK,3BHK,4BHK,Duplex,Penthouse,Other',
            'area_sqft' => 'nullable|numeric|min:0',
            'ownership_type' => 'required|in:owned,rented,vacant',
            'owner_name' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,under_construction,under_maintenance',
            'car_parking_slot' => 'nullable|string|max:255',
            'bike_parking_slot' => 'nullable|string|max:255',
        ]);
    }
}
