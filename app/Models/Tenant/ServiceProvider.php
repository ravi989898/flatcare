<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceProvider extends Model
{
    use SoftDeletes;

    protected $connection = 'society';

    protected $fillable = [
        'name',
        'service_type',
        'phone',
        'notes',
        'sort_order',
    ];
}
