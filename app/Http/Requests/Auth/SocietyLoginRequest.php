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
 * authenticate against before it can attempt to authenticate the user at
 * all. There's no "Society Code" field on the form for the user to supply
 * that with, so it's resolved by trying the email/password against each
 * active society's tenant database in turn until one accepts it.
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
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Try the submitted credentials against every active, in-period
     * society's tenant database until one authenticates. Fine for a modest
     * number of societies; if that list grows large this should be
     * replaced with a main-database email -> society lookup table instead
     * of a per-society scan.
     *
     * @throws ValidationException
     */
    public function authenticate(): Society
    {
        $tenantService = app(TenantService::class);

        $this->ensureIsNotRateLimited();

        $societies = Society::where('status', 'active')->get()
            ->filter(fn (Society $society) => $tenantService->validateSocietyAccessPeriod($society));

        foreach ($societies as $society) {
            $tenantService->setTenant($society);

            if (!Auth::guard('society')->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
                continue;
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

        RateLimiter::hit($this->throttleKey(), decaySeconds: 60);

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
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
     * Keyed by email + IP so a single attacker can't lock out a legitimate
     * account from a shared IP.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('email')).'|'.$this->ip()
        );
    }
}
