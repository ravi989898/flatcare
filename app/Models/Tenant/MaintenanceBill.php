<?php

namespace App\Models\Tenant;

use App\Models\Society;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceBill extends Model
{
    use SoftDeletes;

    protected $connection = 'society';

    public const STATUSES = ['unpaid', 'partially_paid', 'paid', 'overdue'];

    protected $fillable = [
        'flat_id',
        'water_reading_id',
        'fee_type_id',
        'title',
        'amount',
        'due_date',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    protected $appends = ['paid_amount', 'balance', 'status'];

    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }

    public function waterReading(): BelongsTo
    {
        return $this->belongsTo(WaterReading::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'bill_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Extra Charges (function/event usage, hall booking, renovation fund,
     * transfer fee, ...) are regular maintenance_bills rows tagged with a
     * fee_type_id — these scopes keep the two lists (Payments vs. Extra
     * Charges) from mixing, since they're otherwise the same table.
     */
    public function scopeExtraCharges($query)
    {
        return $query->whereNotNull('fee_type_id');
    }

    public function scopeMaintenanceOnly($query)
    {
        return $query->whereNull('fee_type_id');
    }

    /**
     * Status, paid amount, and balance are computed from payments rather
     * than cached on the row — a maintenance bill for a single society has
     * a small enough payment history that recomputing on read is simpler
     * and can't drift out of sync with the ledger, which matters more here
     * than the write-time cost of a cached column would save.
     */
    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments->sum('amount');
    }

    /**
     * What's still owed, late fee included — the amount Pay Now charges,
     * the admin's "Record Payment" caps at, and Payment Status / reports
     * count as pending.
     */
    public function getBalanceAttribute(): float
    {
        return round($this->totalAmount() - $this->paid_amount, 2);
    }

    /** The bill's own amount plus any late fee — the "Total" on every view. */
    public function totalAmount(?Society $society = null): float
    {
        return round((float) $this->amount + $this->lateFeeAmount($society), 2);
    }

    /** The bill's own amount still unpaid, ignoring any late fee. */
    private function principalBalance(): float
    {
        return round((float) $this->amount - $this->paid_amount, 2);
    }

    public function getStatusAttribute(): string
    {
        if ($this->balance <= 0) {
            return 'paid';
        }

        if ($this->paid_amount > 0) {
            return 'partially_paid';
        }

        if ($this->due_date->isPast()) {
            return 'overdue';
        }

        return 'unpaid';
    }

    /**
     * How many days past due_date this bill is, or 0 if it's not overdue or
     * is already paid off — a paid bill doesn't keep accruing a late fee.
     */
    public function daysOverdue(): int
    {
        if (! $this->due_date || $this->principalBalance() <= 0 || ! $this->due_date->isPast()) {
            return 0;
        }

        return (int) $this->due_date->diffInDays(today());
    }

    /**
     * The society's fixed late_fee plus daily_late_fee for every day
     * overdue (so day 1 overdue already carries one day's charge) — see
     * the "Maintenance Billing" section of the society edit form. Falls
     * back to the current tenant's society when none is passed, same as
     * breakdownLines() below.
     *
     * Once the bill's own amount is paid off the fee stops accruing, and
     * whatever was paid on top of the amount is the late fee that was
     * collected — so a bill paid in full (fee included) still shows that
     * fee in its total instead of the fee vanishing from the receipt.
     */
    public function lateFeeAmount(?Society $society = null): float
    {
        $society ??= app(TenantService::class)->getCurrentSociety();

        if ($this->principalBalance() <= 0) {
            return max(0.0, -$this->principalBalance());
        }

        $daysOverdue = $this->daysOverdue();

        if (! $society || $daysOverdue < 1) {
            return 0.0;
        }

        $lateFee = (float) ($society->late_fee ?? 0);
        $dailyLateFee = (float) ($society->daily_late_fee ?? 0);

        return round($lateFee + ($dailyLateFee * $daysOverdue), 2);
    }

    /**
     * A bill generated from a water reading is split into Fixed
     * Maintenance, metered Water Charges and any recurring extra charges
     * (WaterExtraCharge) — see WaterBillingService, which computes the
     * bill's amount the same way. Fixed Maintenance is whatever is left of
     * the amount after water and extras, so the lines always add up to what
     * was billed even if the society's rates changed since. A manually-
     * raised bill has no such components, so it falls back to a single line
     * matching its title and total. Shared between BillResource (JSON) and
     * the PDF receipt view, so both surfaces show the identical breakdown.
     *
     * @return array<int, array{label: string, amount: float}>
     */
    public function breakdownLines(?Society $society): array
    {
        $lines = null;

        if ($this->waterReading && $society) {
            $reading = $this->waterReading;
            $units = (float) $reading->units;
            $waterCharge = round($units * (float) $society->water_unit_rate, 2);
            $extras = $this->extraChargeLines();
            $extraTotal = round(array_sum(array_column($extras, 'amount')), 2);
            $fixed = round((float) $this->amount - $waterCharge - $extraTotal, 2);

            $lines = [];
            if ($fixed != 0) {
                $lines[] = ['label' => 'Fixed Maintenance', 'amount' => $fixed];
            }
            if ($society->water_unit_rate > 0) {
                $lines[] = ['label' => "Water Charges ({$units} units)", 'amount' => $waterCharge];
            }
            array_push($lines, ...$extras);
        }

        if ($lines === null || $lines === []) {
            $lines = [['label' => $this->title, 'amount' => (float) $this->amount]];
        }

        $lateFee = $this->lateFeeAmount($society);
        if ($lateFee > 0) {
            $days = $this->daysOverdue();
            $lines[] = [
                'label' => $days > 0 ? "Late Fee ({$days} ".($days === 1 ? 'day' : 'days').' overdue)' : 'Late Fee',
                'amount' => $lateFee,
            ];
        }

        return $lines;
    }

    /**
     * The reading's extra_amount snapshot, itemised by the WaterExtraCharge
     * rows active in its month when those still add up to the snapshot;
     * otherwise (a charge was edited or removed after billing) one
     * "Extra Charges" line for the snapshot itself.
     *
     * @return array<int, array{label: string, amount: float}>
     */
    private function extraChargeLines(): array
    {
        $reading = $this->waterReading;
        $snapshot = round((float) $reading->extra_amount, 2);

        if ($snapshot <= 0) {
            return [];
        }

        $month = $reading->reading_month;
        $charges = WaterExtraCharge::activeDuring($this->flat_id, $month->copy()->startOfMonth(), $month->copy()->endOfMonth())
            ->orderBy('title')
            ->get(['title', 'amount']);

        if (abs((float) $charges->sum('amount') - $snapshot) < 0.01) {
            return $charges->map(fn (WaterExtraCharge $c) => ['label' => $c->title ?: 'Extra Charge', 'amount' => (float) $c->amount])->all();
        }

        return [['label' => 'Extra Charges', 'amount' => $snapshot]];
    }
}
