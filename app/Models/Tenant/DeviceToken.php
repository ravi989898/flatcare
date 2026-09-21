<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One FCM registration token for one of a user's devices. The token is
 * encrypted at rest (it is a credential for pushing to that device) and
 * looked up by its SHA-256 `token_hash`. See the create_device_tokens
 * migration.
 */
class DeviceToken extends Model
{
    protected $connection = 'society';

    protected $fillable = [
        'user_id',
        'token',
        'token_hash',
        'platform',
        'device_name',
        'is_active',
        'last_used_at',
        'deactivated_at',
        'deactivation_reason',
    ];

    protected $hidden = ['token', 'token_hash'];

    protected $casts = [
        'token' => 'encrypted',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function deactivate(string $reason): void
    {
        $this->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => mb_substr($reason, 0, 255),
        ]);
    }
}
