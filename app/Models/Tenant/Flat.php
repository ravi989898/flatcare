<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Flat extends Model
{
    use SoftDeletes;

    protected $connection = 'society';

    protected $fillable = [
        'block_id',
        'flat_number',
        'floor_number',
        'flat_type',
        'area_sqft',
        'ownership_type',
        'owner_name',
        'status',
        'car_parking_slot',
        'bike_parking_slot',
        'amenities',
        'metadata',
    ];

    protected $casts = [
        'amenities' => 'array',
        'metadata' => 'array',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function residents(): HasMany
    {
        return $this->hasMany(FlatResident::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->block ? "{$this->block->name} / {$this->flat_number}" : $this->flat_number;
    }
}
