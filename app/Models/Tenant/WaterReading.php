<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WaterReading extends Model
{
    protected $connection = 'society';

    protected $fillable = [
        'flat_id',
        'reading_month',
        'previous_reading',
        'current_reading',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'reading_month' => 'date',
        'previous_reading' => 'decimal:2',
        'current_reading' => 'decimal:2',
    ];

    protected $appends = ['units'];

    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function bill(): HasOne
    {
        return $this->hasOne(MaintenanceBill::class);
    }

    public function getUnitsAttribute(): float
    {
        return round((float) $this->current_reading - (float) $this->previous_reading, 2);
    }

    /**
     * The most recent reading recorded for a flat before the given month —
     * its current_reading becomes next month's previous_reading, so the
     * admin never has to look it up or re-enter it by hand.
     */
    public static function priorTo(int $flatId, \Carbon\Carbon $month): ?self
    {
        return static::where('flat_id', $flatId)
            ->where('reading_month', '<', $month->toDateString())
            ->latest('reading_month')
            ->first();
    }
}
