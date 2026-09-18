<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

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

    /**
     * The sidebar entries the given role is allowed to see, per Settings ->
     * Menu Settings (role_menu_item.is_visible). Falls back to just
     * Dashboard if the role has no role_menu_item rows at all. Shared by
     * SetSocietyContext (sidebar rendering) and EnsureMenuItemVisible
     * (route access) so both read the exact same cached answer.
     */
    public static function visibleForRole(?string $roleName): Collection
    {
        return Cache::remember(
            'society.menu_items.role.' . ($roleName ?? '__none__'),
            now()->addHours(24),
            function () use ($roleName) {
                $items = static::query()
                    ->when($roleName, function ($query) use ($roleName) {
                        $query->whereHas('roles', function ($q) use ($roleName) {
                            $q->where('name', $roleName)->where('role_menu_item.is_visible', true);
                        });
                    }, fn ($query) => $query->whereRaw('1 = 0'))
                    ->orderBy('display_order')
                    ->get();

                return $items->isEmpty() ? static::where('key', 'dashboard')->get() : $items;
            }
        );
    }
}
