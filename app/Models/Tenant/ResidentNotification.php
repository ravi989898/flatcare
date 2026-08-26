<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The mobile app's Notifications feed. Named ResidentNotification (table
 * resident_notifications) rather than Notification/notifications to avoid
 * colliding with Laravel's own polymorphic notification system, which
 * isn't wired up anywhere in this codebase — see the migration's docblock.
 */
class ResidentNotification extends Model
{
    protected $connection = 'society';
    protected $table = 'resident_notifications';

    public const TYPES = ['maintenance_due', 'request_status', 'new_notice', 'visitor_arrived', 'event_reminder'];

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
