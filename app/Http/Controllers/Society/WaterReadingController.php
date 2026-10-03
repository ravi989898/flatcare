<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Society\StoreWaterReadingsRequest;
use App\Models\Tenant\Block;
use App\Models\Tenant\Flat;
use App\Models\Tenant\WaterReading;
use App\Services\WaterBillingService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $readings = WaterReading::with(['flat.block', 'bill.payments'])
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
            ? Carbon::parse($request->string('month')->trim()->value().'-01')
            : now()->startOfMonth();

        $blocks = Block::active()->withCount(['flats' => fn ($q) => $q->active()])->orderBy('name')->get();

        $blockId = $request->integer('block_id') ?: null;

        if (! $blockId) {
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

    public function store(StoreWaterReadingsRequest $request, WaterBillingService $billing): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $billed = $billing->record(
                Carbon::parse($validated['month'].'-01'),
                $validated['readings'],
                $request->attributes->get('society'),
                Auth::guard('society')->id(),
            );
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('society.water-readings.create', ['month' => $validated['month']])
            ->with('success', "Readings saved and bills generated for {$billed} flat".($billed > 1 ? 's' : '').'.');
    }

    /**
     * Correct a single flat's reading in place from the list page's Edit
     * modal, rather than sending the admin back through the whole block's
     * entry form. The previous reading is never editable here - only
     * current_reading - so it's read straight off the existing row rather
     * than trusted from the request.
     */
    public function update(Request $request, int $readingId, WaterBillingService $billing): RedirectResponse
    {
        $validated = $request->validate([
            'current_reading' => ['required', 'numeric', 'min:0'],
        ]);

        $reading = WaterReading::with(['flat', 'bill.payments'])->findOrFail($readingId);

        if ($reading->bill?->payments->isNotEmpty()) {
            return back()->with('error', "This month's bill is already paid, so its reading can't be changed.");
        }

        try {
            $billing->record(
                $reading->reading_month,
                [[
                    'flat_id' => $reading->flat_id,
                    'previous_reading' => $reading->previous_reading,
                    'current_reading' => $validated['current_reading'],
                ]],
                $request->attributes->get('society'),
                Auth::guard('society')->id(),
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('society.water-readings.index')
            ->with('success', "Flat {$reading->flat->flat_number}'s reading updated.");
    }
}
