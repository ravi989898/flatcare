<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Catalog of Super Admin dashboard widgets (stat cards + the revenue
 * chart). Which roles see which widget is controlled under Settings ->
 * Dashboard Widgets (role_dashboard_widget.is_visible); DashboardController
 * reads that for the logged-in user's role to decide what to render.
 */
class DashboardWidget extends Model
{
    use HasFactory;

    protected $connection = 'main';

    protected $fillable = [
        'key',
        'label',
        'display_order',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleDefinition::class, 'role_dashboard_widget')
            ->withPivot('is_visible')
            ->withTimestamps();
    }
}
