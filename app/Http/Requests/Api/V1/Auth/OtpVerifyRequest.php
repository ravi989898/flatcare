<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Models\Society;
use App\Models\Tenant\Flat;
use App\Models\Tenant\SecurityGuard;
use App\Services\Api\OtpService;
use App\Services\Api\TenantAccountLocator;
use App\Support\SecurityLog;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Resident/gate-security app OTP login. Same rate-limiting shape as
 * LoginRequest, but resolves a Flat or a SecurityGuard by mobile_number
 * instead of a User by email - the OTP controller is the one that
 * finds-or-creates the actual account once this confirms the number and
 * code check out.
 */
class OtpVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mobile_number' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'string', 'max:10'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_platform' => ['nullable', 'string', 'in:ios,android'],
        ];
    }

    /**
     * @return array{society: Society, flat: Flat|null, guard: SecurityGuard|null}
     *
     * @throws ValidationException
     */
    public function authenticate(OtpService $otp, TenantAccountLocator $locator): array
    {
        $this->ensureIsNotRateLimited();

        $mobileNumber = $this->string('mobile_number')->value();

        if (!$otp->verify($mobileNumber, $this->string('otp')->value())) {
            RateLimiter::hit($this->throttleKey(), decaySeconds: 60);
            RateLimiter::hit($this->identifierKey(), decaySeconds: 900);
            SecurityLog::warning('auth.otp_verify_failed', ['mobile' => substr($mobileNumber, -4)]);

            throw ValidationException::withMessages([
                'otp' => 'That code is incorrect or has expired.',
            ]);
        }

        // A resident's flat takes precedence over a guard roster entry on
        // the off chance the same number was ever registered as both.
        $residentFound = $locator->findFlatByMobileNumber($mobileNumber);
        $guardFound = $residentFound ? null : $locator->findSecurityGuardByMobileNumber($mobileNumber);

        $found = match (true) {
            (bool) $residentFound => ['society' => $residentFound['society'], 'flat' => $residentFound['flat'], 'guard' => null],
            (bool) $guardFound => ['society' => $guardFound['society'], 'flat' => null, 'guard' => $guardFound['guard']],
            default => null,
        };

        if (!$found) {
            RateLimiter::hit($this->throttleKey(), decaySeconds: 60);
            RateLimiter::hit($this->identifierKey(), decaySeconds: 900);
            SecurityLog::warning('auth.otp_unknown_number', ['mobile' => substr($mobileNumber, -4)]);

            throw ValidationException::withMessages([
                'mobile_number' => 'No flat is registered with this mobile number. Contact your society office.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->identifierKey());

        return $found;
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        $maxAttempts = (int) config('auth.login_max_attempts', 5);

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)
            && ! RateLimiter::tooManyAttempts($this->identifierKey(), self::MAX_FAILURES_PER_ACCOUNT)) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(RateLimiter::availableIn($this->throttleKey()), RateLimiter::availableIn($this->identifierKey()));

        throw ValidationException::withMessages([
            'otp' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(
            'otp|'.$this->string('mobile_number').'|'.$this->ip()
        );
    }

    /** Failed attempts allowed per account in 15 minutes, whatever the source IP. */
    private const MAX_FAILURES_PER_ACCOUNT = 20;

    /**
     * Failure counter keyed by the account alone (no IP). The per-IP key above
     * stops one client; this one stops a distributed / IP-rotating (e.g. spoofed
     * X-Forwarded-For) brute force against a single account.
     */
    private function identifierKey(): string
    {
        return Str::transliterate('otpid|'.$this->string('mobile_number'));
    }
}
