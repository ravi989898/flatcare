<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityGuard extends Model
{
    protected $connection = 'society';

    protected $fillable = [
        'name',
        'phone',
        'shift',
        'aadhar_last4',
        'photo_path',
        'status',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(SecurityGuardLog::class);
    }
}
