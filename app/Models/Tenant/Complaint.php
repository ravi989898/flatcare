<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use SoftDeletes;

    protected $connection = 'society';

    public const STATUSES = ['open', 'in_review', 'resolved', 'closed', 'rejected'];
    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];
    public const CATEGORIES = [
        'noise', 'parking', 'security', 'staff_behavior',
        'cleanliness', 'rule_violation', 'other',
    ];

    protected $fillable = [
        'flat_id',
        'category',
        'subject',
        'description',
        'against',
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
        return $query->whereIn('status', ['open', 'in_review']);
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
     * Append an entry to the complaint's audit trail and persist it — same
     * JSON-column trade-off as MaintenanceRequest::logHistory().
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
        return in_array($this->status, ['open', 'in_review'], true);
    }
}
