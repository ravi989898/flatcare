<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuperAdmin extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'main';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'super_admin_permissions',
            'super_admin_id',
            'permission_id'
        );
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Methods
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()
            ->where('name', $permissionName)
            ->exists();
    }

    public function can(string $permissionName): bool
    {
        return $this->hasPermission($permissionName);
    }

    public function cannot(string $permissionName): bool
    {
        return !$this->can($permissionName);
    }
}
