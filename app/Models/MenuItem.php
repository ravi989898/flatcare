<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Catalog of Society-portal sidebar entries. Which roles see which items is
 * controlled by Super Admin under Settings -> Menu Settings (the
 * role_menu_item pivot's is_visible flag); SetSocietyContext reads that per
 * request to build the logged-in tenant user's sidebar.
 */
class MenuItem extends Model
{
    use HasFactory;

    protected $connection = 'main';

    protected $fillable = [
        'key',
        'label',
        'route_name',
        'icon',
        'display_order',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleDefinition::class, 'role_menu_item')
            ->withPivot('is_visible')
            ->withTimestamps();
    }

    /**
     * The routeIs() prefix used to highlight this item as active - the
     * route group (first two segments of the route name), e.g.
     * 'society.maintenance' for 'society.maintenance.index'.
     */
    public function getActivePatternAttribute(): string
    {
        return collect(explode('.', $this->route_name))->take(2)->implode('.');
    }
}
