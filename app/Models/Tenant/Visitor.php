<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visitor extends Model
{
    use SoftDeletes;

    protected $connection = 'society';

    public const STATUSES = ['pending', 'checked_in', 'checked_out', 'denied'];
    public const PURPOSES = ['guest', 'delivery', 'cab', 'service', 'other'];

    protected $fillable = [
        'flat_id',
        'visitor_name',
        'visitor_phone',
        'purpose',
        'vehicle_number',
        'status',
        'check_in_at',
        'check_out_at',
        'expected_at',
        'pass_code',
        'checked_in_by',
        'checked_out_by',
        'invited_by_user_id',
        'notes',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'expected_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * Scopes
     */
    public function scopeCurrentlyIn(Builder $query): Builder
    {
        return $query->where('status', 'checked_in');
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function isCheckedIn(): bool
    {
        return $this->status === 'checked_in';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
