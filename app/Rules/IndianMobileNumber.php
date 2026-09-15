<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Shared shape check for every mobile-number field in the app. Matters
 * beyond plain "looks like a phone number" for the `flats.mobile_number`
 * field specifically, since that value is also the OTP-login identifier
 * (Api\V1\Auth\OtpAuthController) — a malformed number there silently
 * locks a resident out of the mobile app.
 */
class IndianMobileNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^[6-9]\d{9}$/', $value)) {
            $fail('The :attribute must be a valid 10-digit mobile number.');
        }
    }
}
