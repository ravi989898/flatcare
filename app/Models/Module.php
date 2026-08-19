<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasFactory;

    protected $connection = 'main';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'icon',
        'color',
        'display_order',
        'is_core',
        'is_active',
    ];

    protected $casts = [
        'is_core' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    public function societyModules(): HasMany
    {
        return $this->hasMany(SocietyModule::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCore($query)
    {
        return $query->where('is_core', true);
    }

    /**
     * Methods
     */
    public function isCore(): bool
    {
        return $this->is_core;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}
