<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visitor extends Model
{
    use SoftDeletes;

    protected $connection = 'society';

    public const STATUSES = ['pending', 'approved', 'checked_in', 'checked_out', 'denied'];

    /**
     * The workflow names (PENDING -> APPROVED -> ENTERED -> EXITED, or
     * REJECTED) for the stored statuses — see the approval-workflow
     * migration for why the database keeps checked_in/checked_out/denied.
     */
    public const STATUS_LABELS = [
        'pending' => 'PENDING',
        'approved' => 'APPROVED',
        'checked_in' => 'ENTERED',
        'checked_out' => 'EXITED',
        'denied' => 'REJECTED',
    ];

    /** Statuses the gate still has to act on or keep an eye on. */
    public const ACTIVE_STATUSES = ['pending', 'approved', 'checked_in'];
    public const PURPOSES = ['guest', 'delivery', 'cab', 'service', 'other'];
    public const ENTRY_KINDS = ['gate_pass', 'pre_approval'];

    protected $fillable = [
        'flat_id',
        'block_id',
        'visitor_name',
        'visitor_phone',
        'visitor_email',
        'purpose',
        'vehicle_number',
        'status',
        'check_in_at',
        'check_out_at',
        'expected_at',
        'valid_until',
        'pass_code',
        'entry_kind',
        'checked_in_by',
        'checked_out_by',
        'invited_by_user_id',
        'notes',
        'photo_path',
        'gate_keeper_id',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'expected_at' => 'datetime',
        'valid_until' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
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

    public function gateKeeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gate_keeper_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(VisitorStatusHistory::class);
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

    public function scopeActiveRequests(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? strtoupper($this->status);
    }

    /**
     * A guard-raised entry request (as opposed to a resident's own
     * self-invite, which always has invited_by_user_id set).
     */
    public function isGuardRequest(): bool
    {
        return $this->invited_by_user_id === null;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
