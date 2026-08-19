<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PlatformSetting extends Model
{
    protected $connection = 'main';

    protected $fillable = [
        'logo_path',
        'updated_by_super_admin_id',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'updated_by_super_admin_id');
    }

    /**
     * Get the singleton settings row, creating it if it doesn't exist yet.
     * Cached briefly since this is read on essentially every page (every
     * layout needs the logo URL) but changes only when a super admin
     * updates branding.
     */
    public static function current(): self
    {
        return Cache::remember('platform_settings.current', now()->addHour(), function () {
            return self::firstOrCreate(['id' => 1]);
        });
    }

    /**
     * Public URL for the current logo, or null if none has been set —
     * callers fall back to a text/icon brand mark in that case.
     */
    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public static function forgetCache(): void
    {
        Cache::forget('platform_settings.current');
    }
}
