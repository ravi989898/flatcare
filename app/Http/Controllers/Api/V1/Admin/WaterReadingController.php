<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Society\StoreWaterReadingsRequest;
use App\Models\Tenant\Block;
use App\Models\Tenant\WaterReading;
use App\Services\WaterBillingService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Society admin in the mobile app: monthly water readings, which generate
 * each flat's maintenance bill. Same rules as the web panel - the work is
 * done by App\Services\WaterBillingService. Society Admin, or a role
 * granted "Water Readings" under App Permission (app.menu middleware in
 * routes/api.php).
 */
class WaterReadingController extends ApiController
{
    /** The month's blocks, with how many flats already have a reading. */
    public function blocks(Request $request): JsonResponse
    {
        $month = $this->month($request);

        $entered = WaterReading::where('reading_month', $month->toDateString())
            ->join('flats', 'flats.id', '=', 'water_readings.flat_id')
            ->selectRaw('flats.block_id, count(*) as total')
            ->groupBy('flats.block_id')
            ->pluck('total', 'flats.block_id');

        $blocks = Block::active()
            ->withCount(['flats' => fn ($query) => $query->active()])
            ->orderBy('name')
            ->get()
            ->map(fn (Block $block) => [
                'id' => $block->id,
                'name' => $block->name,
                'flats_count' => $block->flats_count,
                'entered_count' => (int) ($entered[$block->id] ?? 0),
            ]);

        return $this->ok([
            'month' => $month->format('Y-m'),
            'rates' => $this->rates($request),
            'blocks' => $blocks,
        ]);
    }

    /** One row per active flat of a block for the month. */
    public function index(Request $request, WaterBillingService $billing): JsonResponse
    {
        $month = $this->month($request);
        $blockId = $request->integer('block_id') ?: null;

        $rows = $billing->sheet($month, $blockId)->map(fn (array $row) => [
            'flat_id' => $row['flat']->id,
            'flat_number' => $row['flat']->flat_number,
            'flat_label' => $row['flat']->display_label,
            'previous_reading' => $this->number($row['previous_reading']),
            'current_reading' => $this->number($row['current_reading']),
            'has_history' => $row['has_history'],
            'units' => $row['reading']?->units,
            'bill_id' => $row['bill']?->id,
            'bill_amount' => $row['bill'] ? (float) $row['bill']->amount : null,
            'bill_status' => $row['bill']?->status,
            'locked' => $row['locked'],
        ])->values();

        return $this->ok([
            'month' => $month->format('Y-m'),
            'rates' => $this->rates($request),
            'rows' => $rows,
        ]);
    }

    public function store(StoreWaterReadingsRequest $request, WaterBillingService $billing): JsonResponse
    {
        $validated = $request->validated();

        try {
            $billed = $billing->record(
                Carbon::parse($validated['month'].'-01'),
                $validated['readings'],
                $request->attributes->get('api_society'),
                $this->user()->id,
            );
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok(
            ['billed' => $billed],
            "Readings saved and bills generated for {$billed} flat".($billed > 1 ? 's' : '').'.',
        );
    }

    private function month(Request $request): Carbon
    {
        $value = $request->string('month')->trim()->value();

        return preg_match('/^\d{4}-\d{2}$/', $value)
            ? Carbon::parse($value.'-01')->startOfMonth()
            : now()->startOfMonth();
    }

    /** @return array{fixed_maintenance: float, water_unit_rate: float} */
    private function rates(Request $request): array
    {
        $society = $request->attributes->get('api_society');

        return [
            'fixed_maintenance' => (float) ($society?->fixed_maintenance ?? 0),
            'water_unit_rate' => (float) ($society?->water_unit_rate ?? 0),
        ];
    }

    private function number(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
