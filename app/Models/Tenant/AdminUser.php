<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminUser extends Model
{
    protected $table = 'admin_users';

    protected $fillable = [
        'user_id',
        'society_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'status',
        'last_login_at',
        'last_login_ip',
        'two_factor_enabled',
        'metadata',
    ];

    protected $casts = [
        'two_factor_enabled' => 'boolean',
        'last_login_at' => 'datetime',
        'metadata' => 'json',
    ];

    /**
     * Get the tenant user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get user's roles
     */
    public function getRoles()
    {
        return $this->user()->roles ?? collect([]);
    }

    /**
     * Check if admin has permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->user()?->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
            })
            ->exists() ?? false;
    }

    /**
     * Scope: active admins
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: inactive admins
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}
