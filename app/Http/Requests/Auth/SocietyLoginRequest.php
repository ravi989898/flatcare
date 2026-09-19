<?php

namespace App\Http\Requests\Auth;

use App\Models\Society;
use App\Services\TenantService;
use App\Support\SecurityLog;
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
    /**
     * Pin failed-validation redirects to the society login page explicitly,
     * rather than trusting url()->previous() (FormRequest's default) — that
     * falls back to whatever page the session last recorded as "previous",
     * which can be stale (e.g. the super-admin /login page) if the browser
     * served this page from cache instead of a fresh navigation.
     */
    protected $redirectRoute = 'society.login';

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
        RateLimiter::clear($this->identifierKey());

            return $society;
        }

        RateLimiter::hit($this->throttleKey(), decaySeconds: 60);
            RateLimiter::hit($this->identifierKey(), decaySeconds: 900);
            SecurityLog::warning('auth.society_login_failed', ['email' => $this->string('email')->value()]);

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
     * Keyed by email + IP so a single attacker can't lock out a legitimate
     * account from a shared IP.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('email')).'|'.$this->ip()
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
        return Str::transliterate('id|'.Str::lower($this->string('email')));
    }
}
