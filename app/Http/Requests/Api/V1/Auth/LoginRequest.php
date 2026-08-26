<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Models\Society;
use App\Models\Tenant\User as TenantUser;
use App\Services\Api\TenantAccountLocator;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Mobile login. Same tenant-resolution and rate-limiting approach as the
 * web society portal's SocietyLoginRequest, adapted to return values
 * instead of driving a session-based guard.
 */
class LoginRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_platform' => ['nullable', 'string', 'in:ios,android'],
        ];
    }

    /**
     * @return array{society: Society, user: TenantUser}
     *
     * @throws ValidationException
     */
    public function authenticate(TenantAccountLocator $locator): array
    {
        $this->ensureIsNotRateLimited();

        $found = $locator->findByCredentials($this->string('email')->value(), $this->string('password')->value());

        if (!$found) {
            RateLimiter::hit($this->throttleKey(), decaySeconds: 60);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if ($found['user']->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => 'This account is not active. Please contact your society administrator.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $found;
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        $maxAttempts = (int) config('auth.login_max_attempts', 5);

        if (!RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('email')).'|'.$this->ip()
        );
    }
}
