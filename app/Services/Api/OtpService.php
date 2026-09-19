<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Log;

/**
 * Stub OTP delivery/verification — see config/flatcare.php's otp_default_code
 * docblock. Every mobile number's code is the same fixed value until a real
 * SMS gateway is wired in here; callers (OtpVerifyRequest) never need to
 * change when that happens, only this class's two methods do.
 */
class OtpService
{
    public function send(string $mobileNumber): void
    {
        // No SMS provider yet - log it so the flow is visible in dev/staging
        // instead of silently doing nothing.
        //
        // The code itself is only written to the log on a local dev machine
        // (so a developer can see it); in any other environment a log file
        // must never contain a working credential, and only the last four
        // digits of the number are recorded.
        Log::info('OTP requested (stub - no SMS sent)', [
            'mobile_number' => app()->environment('local') ? $mobileNumber : '******'.substr($mobileNumber, -4),
        ] + (app()->environment('local') ? ['code' => config('flatcare.otp_default_code')] : []));
    }

    public function verify(string $mobileNumber, string $code): bool
    {
        return hash_equals((string) config('flatcare.otp_default_code'), $code);
    }
}
