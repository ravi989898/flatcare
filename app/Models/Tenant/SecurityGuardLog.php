<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per (guard, shift) duty period — the history of who was on Day/
 * Night duty and when. ended_at is null while the period is still current.
 */
class SecurityGuardLog extends Model
{
    protected $connection = 'society';

    protected $fillable = [
        'security_guard_id',
        'shift',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function securityGuard(): BelongsTo
    {
        return $this->belongsTo(SecurityGuard::class, 'security_guard_id');
    }
}
