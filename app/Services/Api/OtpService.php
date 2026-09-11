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
        Log::info('OTP requested (stub - no SMS sent)', [
            'mobile_number' => $mobileNumber,
            'code' => config('flatcare.otp_default_code'),
        ]);
    }

    public function verify(string $mobileNumber, string $code): bool
    {
        return hash_equals((string) config('flatcare.otp_default_code'), $code);
    }
}
