<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceRequest extends Model
{
    use SoftDeletes;

    protected $connection = 'society';

    public const STATUSES = ['open', 'in_progress', 'resolved', 'closed', 'cancelled'];
    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];
    public const CATEGORIES = [
        'plumbing', 'electrical', 'carpentry', 'painting',
        'cleaning', 'security', 'lift', 'common_area', 'civil_work', 'other',
    ];

    protected $fillable = [
        'block_id',
        'flat_id',
        'category',
        'title',
        'description',
        'priority',
        'status',
        'raised_by_name',
        'raised_by_phone',
        'raised_by_user_id',
        'assigned_to_user_id',
        'resolution_notes',
        'resolved_at',
        'closed_at',
        'history',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'history' => 'array',
    ];

    /**
     * Relationships
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Scopes
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopePriority(Builder $query, ?string $priority): Builder
    {
        return $priority ? $query->where('priority', $priority) : $query;
    }

    /**
     * Append an entry to the request's audit trail and persist it. Kept as
     * a JSON column rather than a separate table — see the migration's
     * docblock for why that's an intentional trade-off, not a shortcut.
     */
    public function logHistory(string $action, ?string $note, ?int $byUserId): void
    {
        $history = $this->history ?? [];

        $history[] = [
            'at' => now()->toIso8601String(),
            'action' => $action,
            'note' => $note,
            'by_user_id' => $byUserId,
        ];

        $this->update(['history' => $history]);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress'], true);
    }
}
