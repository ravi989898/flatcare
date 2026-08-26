<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmergencyContact extends Model
{
    use SoftDeletes;

    protected $connection = 'society';

    protected $fillable = [
        'label',
        'phone',
        'type',
        'availability',
        'sort_order',
    ];
}
