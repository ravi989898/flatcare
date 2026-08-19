<?php

namespace App\Http\Requests\Auth;

use App\Models\Society;
use App\Services\TenantService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Handles login for the society (tenant) portal. Unlike the super-admin
 * LoginRequest, this one must first resolve which tenant database to
 * authenticate against — via the society's public slug — before it can
 * attempt to authenticate the user at all.
 */
class SocietyLoginRequest extends FormRequest
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
            'society_code' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Resolve the society being signed into, then attempt authentication
     * against its tenant database.
     *
     * Note: FormRequest methods aren't resolved through the container the
     * way controller actions are, so TenantService can't be type-hinted as
     * a parameter here — it has to be pulled out manually.
     *
     * @throws ValidationException
     */
    public function authenticate(): Society
    {
        $tenantService = app(TenantService::class);

        $this->ensureIsNotRateLimited();

        $society = Society::where('slug', Str::slug($this->string('society_code')))->first();

        if (!$society || !$tenantService->validateSocietyAccessPeriod($society)) {
            RateLimiter::hit($this->throttleKey(), decaySeconds: 60);

            throw ValidationException::withMessages([
                'society_code' => 'We couldn\'t find an active society with that code.',
            ]);
        }

        $tenantService->setTenant($society);

        if (!Auth::guard('society')->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), decaySeconds: 60);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $user = Auth::guard('society')->user();

        if ($user->status !== 'active') {
            Auth::guard('society')->logout();

            throw ValidationException::withMessages([
                'email' => 'This account is not active. Please contact your society administrator.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $society;
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
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

    /**
     * Keyed by society code + email + IP so a single attacker can't lock out
     * a legitimate tenant's account from a shared IP.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('society_code')).'|'.Str::lower($this->string('email')).'|'.$this->ip()
        );
    }
}
