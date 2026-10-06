<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A recurring per-flat charge (festival fund, lift AMC installment, ...)
 * that WaterBillingService folds into every monthly water-reading bill for
 * the flat while the billing month falls within [start_date, end_date].
 */
class WaterExtraCharge extends Model
{
    protected $connection = 'society';

    protected $fillable = [
        'flat_id',
        'title',
        'amount',
        'start_date',
        'end_date',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * Charges active at any point during [monthStart, monthEnd] that apply
     * to the given flat — either scoped to that flat specifically, or with
     * flat_id null, meaning it applies to every flat.
     */
    public function scopeActiveDuring(Builder $query, int $flatId, \Carbon\Carbon $monthStart, \Carbon\Carbon $monthEnd): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('flat_id')->orWhere('flat_id', $flatId))
            ->where('start_date', '<=', $monthEnd->toDateString())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $monthStart->toDateString()));
    }
}
