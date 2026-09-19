<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyHelper extends Model
{
    use SoftDeletes;

    protected $connection = 'society';

    public const TYPES = ['maid', 'cook', 'driver', 'nanny', 'gardener', 'other'];

    protected $fillable = [
        'flat_id',
        'added_by_user_id',
        'name',
        'phone',
        'helper_type',
        'photo_path',
    ];

    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }
}
