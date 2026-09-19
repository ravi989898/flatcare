<?php

namespace App\Http\Requests\Auth;

use App\Support\SecurityLog;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), decaySeconds: 60);
            RateLimiter::hit($this->identifierKey(), decaySeconds: 900);
            SecurityLog::warning('auth.web_login_failed', ['email' => $this->string('email')->value()]);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Please contact an administrator.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->identifierKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $maxAttempts = (int) config('auth.login_max_attempts', 5);

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)
            && ! RateLimiter::tooManyAttempts($this->identifierKey(), self::MAX_FAILURES_PER_ACCOUNT)) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(RateLimiter::availableIn($this->throttleKey()), RateLimiter::availableIn($this->identifierKey()));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     * Keyed by email + IP so a single attacker can't lock out a victim's
     * account from a shared IP, while still limiting brute force per pair.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
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
        return Str::transliterate('id|'.Str::lower($this->string('email')));
    }
}
