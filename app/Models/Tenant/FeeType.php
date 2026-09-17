<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeType extends Model
{
    use SoftDeletes;

    protected $connection = 'society';

    protected $fillable = [
        'name',
        'default_amount',
        'sort_order',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
    ];

    public function bills(): HasMany
    {
        return $this->hasMany(MaintenanceBill::class);
    }
}
