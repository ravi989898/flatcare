<?php

namespace App\Services;

use App\Models\Society;
use App\Models\Tenant\Flat;
use App\Models\Tenant\MaintenanceBill;
use App\Models\Tenant\WaterReading;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Monthly water readings -> maintenance bills, shared by the society admin
 * web panel (Society\WaterReadingController) and the admin screens in the
 * mobile app (Api\V1\Admin\WaterReadingController), so both bill exactly
 * the same way:
 *
 *   units  = current_reading - previous_reading
 *   amount = units * water_unit_rate + fixed_maintenance
 *
 * A reading can be corrected (re-saved) until its bill has a payment
 * against it; after that it is locked, because changing it would change
 * the amount of a bill someone already paid.
 */
class WaterBillingService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * One row per active flat (optionally one block) for the month: the
     * previous reading (carried over from the flat's last reading), this
     * month's reading and bill if already entered, and whether it can
     * still be changed.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function sheet(Carbon $month, ?int $blockId = null): Collection
    {
        $flats = Flat::active()
            ->with('block')
            ->when($blockId, fn ($query) => $query->where('block_id', $blockId))
            ->orderBy('block_id')
            ->orderBy('flat_number')
            ->get();

        $existing = WaterReading::with('bill.payments')
            ->where('reading_month', $month->toDateString())
            ->whereIn('flat_id', $flats->pluck('id'))
            ->get()
            ->keyBy('flat_id');

        return $flats->map(function (Flat $flat) use ($existing, $month) {
            $reading = $existing->get($flat->id);
            $prior = WaterReading::priorTo($flat->id, $month);
            $bill = $reading?->bill;

            return [
                'flat' => $flat,
                'previous_reading' => $reading?->previous_reading ?? $prior?->current_reading,
                'current_reading' => $reading?->current_reading,
                'has_history' => (bool) $prior,
                'reading' => $reading,
                'bill' => $bill,
                'locked' => $this->isLocked($reading),
            ];
        });
    }

    /**
     * Saves the entered readings and creates/updates each flat's bill for
     * the month. Everything is checked before anything is written, so a
     * mistake in one row never leaves the month half-saved. Residents are
     * notified only when their bill is first created, not on a correction.
     *
     * @param  array<int, array{flat_id: int|string, previous_reading?: mixed, current_reading?: mixed}>  $rows
     * @return int  how many flats were billed
     *
     * @throws DomainException  with a message to show the admin
     */
    public function record(Carbon $month, array $rows, ?Society $society, ?int $userId): int
    {
        $month = $month->copy()->startOfMonth();

        if ($month->isAfter(now()->startOfMonth())) {
            throw new DomainException("Readings can't be entered for a future month.");
        }

        $fixedMaintenance = (float) ($society?->fixed_maintenance ?? 0);
        $waterUnitRate = (float) ($society?->water_unit_rate ?? 0);
        $dueDate = $month->copy()->addMonthNoOverflow()->startOfMonth()->addDays(4);

        $toSave = [];

        foreach ($rows as $row) {
            if (! isset($row['current_reading']) || $row['current_reading'] === '') {
                continue; // this flat's reading wasn't entered this round — skip it
            }

            $flat = Flat::with('block')->findOrFail((int) $row['flat_id']);
            $current = (float) $row['current_reading'];
            $existing = WaterReading::with('bill.payments')
                ->where('flat_id', $flat->id)
                ->where('reading_month', $month->toDateString())
                ->first();

            if ($this->isLocked($existing)) {
                // Re-submitted unchanged (the whole block is sent back) - fine, nothing to do.
                if (abs((float) $existing->current_reading - $current) < 0.005) {
                    continue;
                }

                throw new DomainException("Flat {$flat->display_label}: this month's bill is already paid, so its reading can't be changed.");
            }

            $prior = WaterReading::priorTo($flat->id, $month);
            $previous = $prior?->current_reading ?? ($row['previous_reading'] ?? null);

            if ($previous === null || $previous === '') {
                throw new DomainException("Flat {$flat->display_label}: enter the previous reading - it's this flat's first reading.");
            }

            if ($current < (float) $previous) {
                throw new DomainException("Flat {$flat->display_label}: the current reading can't be lower than the previous one ({$previous}).");
            }

            $toSave[] = ['flat_id' => $flat->id, 'previous' => (float) $previous, 'current' => $current];
        }

        if ($toSave === []) {
            throw new DomainException("Enter at least one flat's current reading before saving.");
        }

        foreach ($toSave as $row) {
            DB::connection('society')->transaction(function () use ($row, $month, $userId, $fixedMaintenance, $waterUnitRate, $dueDate) {
                $reading = WaterReading::updateOrCreate(
                    ['flat_id' => $row['flat_id'], 'reading_month' => $month->toDateString()],
                    [
                        'previous_reading' => $row['previous'],
                        'current_reading' => $row['current'],
                        'recorded_by_user_id' => $userId,
                    ]
                );

                $units = $reading->units;
                $amount = round(($units * $waterUnitRate) + $fixedMaintenance, 2);

                $bill = MaintenanceBill::updateOrCreate(
                    ['water_reading_id' => $reading->id],
                    [
                        'flat_id' => $row['flat_id'],
                        'title' => $month->format('F Y').' Maintenance',
                        'amount' => $amount,
                        'due_date' => $dueDate,
                        'notes' => "Water: {$units} units × ₹{$waterUnitRate} + Fixed ₹{$fixedMaintenance}",
                        'created_by_user_id' => $userId,
                    ]
                );

                // Only on first creation — re-saving the same month's readings
                // (e.g. correcting a typo) shouldn't re-notify the resident.
                if ($bill->wasRecentlyCreated) {
                    $this->notifications->notifyFlats(
                        [$row['flat_id']],
                        'maintenance_due',
                        "{$bill->title} due on ".$dueDate->format('d M Y'),
                        '₹'.number_format($amount, 2),
                        ['bill_id' => $bill->id],
                    );
                }
            });
        }

        return count($toSave);
    }

    /** A reading whose bill already has a payment against it can't be changed. */
    private function isLocked(?WaterReading $reading): bool
    {
        return (bool) $reading?->bill?->payments->isNotEmpty();
    }
}
