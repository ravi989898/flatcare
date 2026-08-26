<?php

namespace App\Services\Api;

use App\Models\Society;
use App\Models\Tenant\User as TenantUser;
use App\Services\TenantService;
use Illuminate\Support\Facades\Hash;

/**
 * Resolves which society a mobile-app account belongs to, given only an
 * email (there's no "society code" field on the login/forgot-password
 * screens). Same "try every active society in turn" strategy already used
 * by App\Http\Requests\Auth\SocietyLoginRequest for the web society portal
 * — its docblock already flags this as fine for a modest number of
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
     * @return \Illuminate\Support\Collection<int, Society>
     */
    private function activeSocieties()
    {
        return Society::where('status', 'active')->get()
            ->filter(fn (Society $society) => $this->tenantService->validateSocietyAccessPeriod($society));
    }
}
