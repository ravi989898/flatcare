<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlatResident extends Model
{
    use SoftDeletes;

    protected $connection = 'society';

    protected $fillable = [
        'flat_id',
        'user_id',
        'resident_type',
        'moved_in_date',
        'moved_out_date',
        'status',
        'is_primary',
    ];

    protected $casts = [
        'moved_in_date' => 'date',
        'moved_out_date' => 'date',
        'is_primary' => 'boolean',
    ];

    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
