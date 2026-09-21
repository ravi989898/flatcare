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
        'icon_path',
        'contact_email',
        'contact_phone_1',
        'contact_phone_2',
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

    /**
     * Public URL for the source image behind the generated favicon files —
     * shown only as a preview in the branding settings page. The favicon
     * itself is always served from the static public/favicon* files (see
     * FaviconGenerator), never from this URL directly.
     */
    public function iconUrl(): ?string
    {
        return $this->icon_path ? Storage::disk('public')->url($this->icon_path) : null;
    }

    /**
     * Public-site contact details in the same shape as config('seo.contact'),
     * with the super admin's saved values overriding the config defaults.
     * 'whatsapp' holds wa.me-ready digits (10-digit Indian numbers get 91).
     */
    public static function contact(): array
    {
        $contact = config('seo.contact');
        $settings = self::current();

        $phones = array_values(array_filter([$settings->contact_phone_1, $settings->contact_phone_2]));

        if ($phones) {
            $contact['phones'] = $phones;
            $contact['whatsapp'] = array_map(function (string $phone) {
                $digits = preg_replace('/\D+/', '', $phone);

                return strlen($digits) === 10 ? '91'.$digits : $digits;
            }, $phones);
        }

        if ($settings->contact_email) {
            $contact['email'] = $settings->contact_email;
        }

        return $contact;
    }

    public static function forgetCache(): void
    {
        Cache::forget('platform_settings.current');
    }
}
