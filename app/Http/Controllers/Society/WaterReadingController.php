<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Block;
use App\Models\Tenant\Flat;
use App\Models\Tenant\MaintenanceBill;
use App\Models\Tenant\WaterReading;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Monthly water-reading entry that drives maintenance billing.
 *
 * The super admin sets each society's fixed maintenance and per-unit water
 * rate (Society::$fixed_maintenance / $water_unit_rate, main database). The
 * society admin only enters this month's meter reading per flat here; the
 * bill amount is derived rather than typed in:
 *
 *   units = current_reading - previous_reading
 *   amount = units * water_unit_rate + fixed_maintenance
 *
 * This runs alongside, not instead of, the manual "Raise Bill" flow in
 * PaymentController — that one stays for one-off charges (penalties,
 * special collections) that aren't tied to a meter reading.
 */
class WaterReadingController extends Controller
{
    public function index(Request $request): View
    {
        $readings = WaterReading::with(['flat.block', 'bill'])
            ->latest('reading_month')
            ->orderBy('flat_id')
            ->paginate(20);

        return view('society.water-readings.index', compact('readings'));
    }

    /**
     * Two steps on one route: pick a block first (readings are entered a
     * block at a time rather than the whole society in one long table),
     * then — once ?block_id is present — one row per active flat in that
     * block, prefilled with the previous reading pulled from that flat's
     * most recent prior entry. Flats with no prior history get an editable
     * "previous reading" input instead — the one-time baseline the admin
     * enters by hand.
     */
    public function create(Request $request): View
    {
        $month = $request->filled('month')
            ? Carbon::parse($request->string('month')->trim()->value() . '-01')
            : now()->startOfMonth();

        $blocks = Block::active()->withCount(['flats' => fn ($q) => $q->active()])->orderBy('name')->get();

        $blockId = $request->integer('block_id') ?: null;

        if (!$blockId) {
            return view('society.water-readings.create', [
                'month' => $month,
                'blocks' => $blocks,
                'block' => null,
                'rows' => null,
            ]);
        }

        $block = Block::active()->findOrFail($blockId);

        $flats = Flat::active()->where('block_id', $block->id)->orderBy('flat_number')->get();
        $existing = WaterReading::where('reading_month', $month->toDateString())
            ->whereIn('flat_id', $flats->pluck('id'))
            ->get()
            ->keyBy('flat_id');

        $rows = $flats->map(function (Flat $flat) use ($existing, $month) {
            $current = $existing->get($flat->id);
            $prior = WaterReading::priorTo($flat->id, $month);

            return [
                'flat' => $flat,
                'previous_reading' => $current?->previous_reading ?? $prior?->current_reading,
                'current_reading' => $current?->current_reading,
                'has_history' => (bool) $prior,
            ];
        });

        return view('society.water-readings.create', [
            'month' => $month,
            'blocks' => $blocks,
            'block' => $block,
            'rows' => $rows,
            'society' => $request->attributes->get('society'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'block_id' => 'nullable|integer|exists:blocks,id',
            'readings' => 'required|array',
            'readings.*.flat_id' => 'required|integer|exists:flats,id',
            'readings.*.previous_reading' => 'nullable|numeric|min:0',
            'readings.*.current_reading' => 'nullable|numeric|min:0',
        ]);

        $month = Carbon::parse($validated['month'] . '-01')->startOfMonth();
        $dueDate = $month->copy()->addMonthNoOverflow()->startOfMonth()->addDays(4);

        $society = $request->attributes->get('society');
        $fixedMaintenance = (float) ($society->fixed_maintenance ?? 0);
        $waterUnitRate = (float) ($society->water_unit_rate ?? 0);

        $userId = Auth::guard('society')->id();
        $billed = 0;

        foreach ($validated['readings'] as $row) {
            if (!isset($row['current_reading']) || $row['current_reading'] === '') {
                continue; // this flat's reading wasn't entered this round — skip it
            }

            $prior = WaterReading::priorTo((int) $row['flat_id'], $month);
            $previousReading = $prior?->current_reading ?? $row['previous_reading'] ?? null;

            if ($previousReading === null) {
                return back()->withInput()->with('error', 'Every flat needs a previous reading the first time it\'s billed — fill in the "Previous" column for any flat missing one.');
            }

            if ((float) $row['current_reading'] < (float) $previousReading) {
                return back()->withInput()->with('error', 'A current reading can\'t be lower than the previous one.');
            }

            DB::connection('society')->transaction(function () use ($row, $month, $previousReading, $userId, $fixedMaintenance, $waterUnitRate, $dueDate, &$billed) {
                $reading = WaterReading::updateOrCreate(
                    ['flat_id' => $row['flat_id'], 'reading_month' => $month->toDateString()],
                    [
                        'previous_reading' => $previousReading,
                        'current_reading' => $row['current_reading'],
                        'recorded_by_user_id' => $userId,
                    ]
                );

                $units = $reading->units;
                $amount = round(($units * $waterUnitRate) + $fixedMaintenance, 2);

                MaintenanceBill::updateOrCreate(
                    ['water_reading_id' => $reading->id],
                    [
                        'flat_id' => $row['flat_id'],
                        'title' => $month->format('F Y') . ' Maintenance',
                        'amount' => $amount,
                        'due_date' => $dueDate,
                        'notes' => "Water: {$units} units × ₹{$waterUnitRate} + Fixed ₹{$fixedMaintenance}",
                        'created_by_user_id' => $userId,
                    ]
                );

                $billed++;
            });
        }

        if ($billed === 0) {
            return back()->withInput()->with('error', 'Enter at least one flat\'s current reading before saving.');
        }

        return redirect()
            ->route('society.water-readings.create', ['month' => $validated['month']])
            ->with('success', "Readings saved and bills generated for {$billed} flat" . ($billed > 1 ? 's' : '') . '.');
    }
}
