<?php

namespace App\Models\Tenant;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model implements AuthenticatableContract
{
    use Authenticatable, SoftDeletes;

    protected $table = 'users';
    protected $connection = 'society';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'profile_photo_path',
        'gender',
        'date_of_birth',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'status',
        'committee_position',
        'committee_order',
        'blocked_reason',
        'blocked_at',
        'failed_login_attempts',
        'locked_until',
        'last_login_at',
        'last_login_ip',
        'metadata',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'blocked_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
        'metadata' => 'json',
    ];

    /**
     * Get the user's flat residencies (a resident can appear across
     * multiple flats/blocks over time, e.g. after moving).
     */
    public function residencies(): HasMany
    {
        return $this->hasMany(FlatResident::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get user's roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->withTimestamps();
    }

    /**
     * Get user's permissions through roles
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user', 'user_id', 'permission_id')
            ->withTimestamps();
    }

    /**
     * Get direct permissions
     */
    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user', 'user_id', 'permission_id')
            ->withTimestamps();
    }

    /**
     * Get all permissions (roles + direct)
     */
    public function allPermissions()
    {
        $rolePermissions = $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');

        $directPermissions = $this->directPermissions()->get();

        return $rolePermissions->merge($directPermissions)->unique('id');
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(string $permission): bool
    {
        // Check direct permissions
        if ($this->directPermissions()->where('name', $permission)->exists()) {
            return true;
        }

        // Check role permissions
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
            })
            ->exists();
    }

    /**
     * Check if user has role
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Assign role
     */
    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        return $this->roles()->attach($role);
    }

    /**
     * Remove role
     */
    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        return $this->roles()->detach($role);
    }

    /**
     * Grant permission
     */
    public function grantPermission($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        return $this->directPermissions()->attach($permission);
    }

    /**
     * Revoke permission
     */
    public function revokePermission($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        return $this->directPermissions()->detach($permission);
    }

    /**
     * Scope: active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: admins
     */
    public function scopeAdmins($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        });
    }

    /**
     * Scope: residents with a committee title set (see the
     * committee_position/committee_order columns' migration docblock),
     * ordered so e.g. Chairman lists before Secretary.
     */
    public function scopeCommitteeMembers($query)
    {
        return $query->whereNotNull('committee_position')->orderBy('committee_order');
    }
}
