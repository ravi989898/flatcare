<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $connection = 'main';

    protected $fillable = [
        'super_admin_id',
        'society_id',
        'action',
        'module',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public $timestamps = true;

    // Audit logs should be immutable (only created, never updated)
    protected static function booted(): void
    {
        static::updating(function (Model $model) {
            // Prevent updates to audit logs
            return false;
        });

        static::deleting(function (Model $model) {
            // Prevent deletion for normal users (can be deleted by admin with special permission)
            return false;
        });
    }

    /**
     * Relationships
     */
    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class)->withTrashed();
    }

    public function society(): BelongsTo
    {
        return $this->belongsTo(Society::class)->withTrashed();
    }

    /**
     * Scopes
     */
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeByModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function scopeBySuperAdmin(Builder $query, int $superAdminId): Builder
    {
        return $query->where('super_admin_id', $superAdminId);
    }

    public function scopeBySociety(Builder $query, int $societyId): Builder
    {
        return $query->where('society_id', $societyId);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Methods
     */
    public static function log(
        ?SuperAdmin $superAdmin,
        ?Society $society,
        string $action,
        string $module,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): self {
        return self::create([
            'super_admin_id' => $superAdmin?->id,
            'society_id' => $society?->id,
            'action' => $action,
            'module' => $module,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get human-readable change summary
     */
    public function getChangeSummary(): array
    {
        $changes = [];

        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }

        return $changes;
    }
}
