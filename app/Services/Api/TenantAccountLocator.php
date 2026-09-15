<?php

namespace App\Services\Api;

use App\Models\Society;
use App\Models\Tenant\Flat;
use App\Models\Tenant\SecurityGuard;
use App\Models\Tenant\User as TenantUser;
use App\Services\TenantService;
use Illuminate\Support\Facades\Hash;

/**
 * Resolves which society a mobile-app login belongs to, given only an email
 * or mobile number (there's no "society code" field on any of the app's
 * login screens). Same "try every active society in turn" strategy already
 * used by App\Http\Requests\Auth\SocietyLoginRequest for the web society
 * portal — its docblock already flags this as fine for a modest number of
 * societies, and that's still true here.
 */
class TenantAccountLocator
{
    public function __construct(
        private TenantService $tenantService,
    ) {}

    /**
     * Find the society + tenant user for a valid email/password pair.
     *
     * @return array{society: Society, user: TenantUser}|null
     */
    public function findByCredentials(string $email, string $password): ?array
    {
        foreach ($this->activeSocieties() as $society) {
            $this->tenantService->setTenant($society);

            $user = TenantUser::where('email', $email)->first();

            if ($user && Hash::check($password, $user->password)) {
                return ['society' => $society, 'user' => $user];
            }
        }

        return null;
    }

    /**
     * Find the society + tenant user for an email alone (forgot-password —
     * no password to check yet). Returns the first active society whose
     * tenant database has a matching account.
     *
     * @return array{society: Society, user: TenantUser}|null
     */
    public function findByEmail(string $email): ?array
    {
        foreach ($this->activeSocieties() as $society) {
            $this->tenantService->setTenant($society);

            $user = TenantUser::where('email', $email)->first();

            if ($user) {
                return ['society' => $society, 'user' => $user];
            }
        }

        return null;
    }

    /**
     * Find the society + flat for a resident app OTP login (see
     * OtpAuthController) - a flat's mobile_number, not a user account,
     * since a resident may not have one yet when they first log in.
     *
     * @return array{society: Society, flat: Flat}|null
     */
    public function findFlatByMobileNumber(string $mobileNumber): ?array
    {
        foreach ($this->activeSocieties() as $society) {
            $this->tenantService->setTenant($society);

            $flat = Flat::where('mobile_number', $mobileNumber)->first();

            if ($flat) {
                return ['society' => $society, 'flat' => $flat];
            }
        }

        return null;
    }

    /**
     * Find the society + roster entry for a security guard app OTP login
     * (see OtpAuthController) - a guard's phone on the SecurityGuard roster
     * (App\Http\Controllers\Society\SecurityGuardController::store), not a
     * user account, since a guard may not have logged in before. Only an
     * active guard can log in - one a society admin deactivated shouldn't
     * still be able to get into the gate app.
     *
     * @return array{society: Society, guard: SecurityGuard}|null
     */
    public function findSecurityGuardByMobileNumber(string $mobileNumber): ?array
    {
        foreach ($this->activeSocieties() as $society) {
            $this->tenantService->setTenant($society);

            $guard = SecurityGuard::where('phone', $mobileNumber)->where('status', 'active')->first();

            if ($guard) {
                return ['society' => $society, 'guard' => $guard];
            }
        }

        return null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Society>
     */
    private function activeSocieties()
    {
        return Society::where('status', 'active')->get()
            ->filter(fn (Society $society) => $this->tenantService->validateSocietyAccessPeriod($society));
    }
}
