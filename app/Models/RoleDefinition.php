<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * The platform-wide role catalog Super Admin manages under Settings ->
 * Roles. New societies are provisioned with a copy of these rows
 * (TenantRoleSeeder reads from here); Admin\RoleController::sync() can push
 * edits into already-provisioned societies' own `roles` tables, matched by
 * `name`. Permissions stay per-tenant - this table only carries the
 * name/description/priority template plus which society-portal menu items
 * a role can see (see menuItems()).
 */
class RoleDefinition extends Model
{
    use HasFactory;

    protected $connection = 'main';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system_role',
        'priority',
    ];

    protected $casts = [
        'is_system_role' => 'boolean',
    ];

    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'role_menu_item')
            ->withPivot('is_visible')
            ->withTimestamps();
    }

    public function dashboardWidgets(): BelongsToMany
    {
        return $this->belongsToMany(DashboardWidget::class, 'role_dashboard_widget')
            ->withPivot('is_visible')
            ->withTimestamps();
    }

    public function scopeSystemRoles($query)
    {
        return $query->where('is_system_role', true);
    }

    public function scopeCustomRoles($query)
    {
        return $query->where('is_system_role', false);
    }
}
