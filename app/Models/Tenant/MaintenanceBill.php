<?php

namespace App\Models\Tenant;

use App\Models\Society;
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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'bill_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
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

    public function getBalanceAttribute(): float
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
     * A bill generated from a water reading is genuinely two line items —
     * the society's fixed maintenance rate plus metered water usage (see
     * WaterReadingController::store(), which computes the bill's amount the
     * same way). A manually-raised bill has no such components, so it falls
     * back to a single line matching its title and total. Shared between
     * BillResource (JSON) and the PDF receipt view, so both surfaces show
     * the identical breakdown.
     *
     * @return array<int, array{label: string, amount: float}>
     */
    public function breakdownLines(?Society $society): array
    {
        if ($this->waterReading && $society) {
            $fixed = (float) $society->fixed_maintenance;
            $units = (float) $this->waterReading->units;
            $waterCharge = round($units * (float) $society->water_unit_rate, 2);

            $lines = [];
            if ($fixed > 0) {
                $lines[] = ['label' => 'Fixed Maintenance', 'amount' => $fixed];
            }
            if ($society->water_unit_rate > 0) {
                $lines[] = ['label' => "Water Charges ({$units} units)", 'amount' => $waterCharge];
            }

            if ($lines !== []) {
                return $lines;
            }
        }

        return [['label' => $this->title, 'amount' => (float) $this->amount]];
    }
}
