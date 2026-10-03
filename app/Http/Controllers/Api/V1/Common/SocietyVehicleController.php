<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Tenant\Block;
use App\Models\Tenant\FlatResident;
use App\Models\Tenant\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Society-wide vehicle list for the app's Vehicles screen: one block at a
 * time, one card per resident (flat, owner/tenant, phone) with all of that
 * resident's active vehicles, plus a count per vehicle type for the block.
 * Phone numbers are shown in full, same as the society directory
 * (DirectoryResource). A resident's own vehicles are managed through
 * Resident\VehicleController.
 */
class SocietyVehicleController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $blocks = Block::active()->orderBy('name')->get(['id', 'name']);

        $blockId = $request->integer('block_id') ?: $blocks->first()?->id;
        $search = $request->string('search')->trim()->value();

        $residencies = $blockId === null ? collect() : FlatResident::active()
            ->whereHas('flat', fn ($q) => $q->where('block_id', $blockId))
            ->whereHas('user.vehicles', fn ($q) => $q->active())
            ->with([
                'flat.block',
                'user.vehicles' => fn ($q) => $q->active()->orderBy('registration_number'),
            ])
            ->get();

        // Type counts cover the whole block, before the search narrows the list.
        $counts = $residencies->flatMap(fn ($r) => $r->user->vehicles)
            ->unique('id')
            ->countBy(fn (Vehicle $v) => $this->typeLabel($v->vehicle_type))
            ->sortDesc();

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $compact = str_replace(' ', '', $needle);
            $residencies = $residencies->filter(fn (FlatResident $r) => str_contains(mb_strtolower($r->user->name ?? ''), $needle)
                || str_contains(mb_strtolower($r->flat->flat_number ?? ''), $needle)
                || str_contains((string) $r->user->phone, $search)
                || $r->user->vehicles->contains(fn (Vehicle $v) => str_contains(str_replace(' ', '', mb_strtolower($v->registration_number)), $compact)));
        }

        $items = $residencies
            ->sortBy(fn (FlatResident $r) => [$r->flat->flat_number, $r->is_primary ? 0 : 1])
            ->values()
            ->map(fn (FlatResident $r) => [
                'user_id' => $r->user_id,
                'name' => $r->user->name,
                'phone' => $r->user->phone,
                'flat_number' => $r->flat->flat_number,
                'block_name' => $r->flat->block?->name,
                'resident_type' => $r->resident_type,
                'vehicles' => $r->user->vehicles->map(fn (Vehicle $v) => [
                    'id' => $v->id,
                    'vehicle_type' => $v->vehicle_type,
                    'registration_number' => $v->registration_number,
                ])->values(),
            ]);

        return $this->ok([
            'blocks' => $blocks->map(fn (Block $b) => ['id' => $b->id, 'name' => $b->name])->values(),
            'block_id' => $blockId,
            'counts' => $counts->map(fn (int $count, string $type) => ['type' => $type, 'count' => $count])->values(),
            'residents' => $items,
        ]);
    }

    /** "car" / "CAR " -> "Car", so counts group the same type together. */
    private function typeLabel(?string $type): string
    {
        $type = trim((string) $type);

        return $type === '' ? 'Other' : mb_convert_case($type, MB_CASE_TITLE);
    }
}
