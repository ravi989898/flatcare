<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $connection = 'society';

    public const CATEGORIES = ['cultural', 'sports', 'meeting', 'festival', 'other'];

    protected $fillable = [
        'title',
        'description',
        'category',
        'location',
        'start_at',
        'end_at',
        'status',
        'posted_by_user_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_user_id');
    }

    /**
     * Scopes
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('start_at', '>=', now());
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('start_at', '<', now());
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function isPast(): bool
    {
        return $this->start_at->isPast();
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
