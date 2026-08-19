<?php

namespace App\Http\Middleware;

use App\Models\Society;
use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetSocietyContext
 *
 * Web counterpart to SetTenantConnection (which is API/Sanctum-oriented).
 * The society portal identifies its tenant by an id stashed in the session
 * at login time (see SocietyAuthController::login).
 *
 * This middleware ALSO performs the 'society' guard's auth check itself,
 * rather than being paired with Laravel's generic 'auth:society' middleware.
 * Laravel sorts every request's middleware (route + global) by a fixed
 * priority list before running them, and \Illuminate\Auth\Middleware\
 * Authenticate sits in that list — so declaring 'auth:society' after this
 * middleware on a route is not reliable: Authenticate can still end up
 * running first and query the tenant User model before the 'society'
 * connection has been pointed at the right database. Doing both jobs in one
 * middleware makes the ordering a plain fact of the code instead of
 * something the framework's sort has to be trusted to preserve.
 */
class SetSocietyContext
{
    public function __construct(
        private TenantService $tenantService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $societyId = $request->session()->get('tenant_society_id');

        if (!$societyId) {
            return redirect()->route('society.login')
                ->with('error', 'Please sign in to continue.');
        }

        $society = Society::find($societyId);

        if (!$society || !$this->tenantService->validateSocietyAccessPeriod($society)) {
            $request->session()->forget('tenant_society_id');

            return redirect()->route('society.login')
                ->with('error', 'Your society access has expired or is inactive. Please contact your platform administrator.');
        }

        $this->tenantService->setTenant($society);

        if (!Auth::guard('society')->check()) {
            return redirect()->route('society.login');
        }

        // Make the current society available to controllers/views without
        // every one of them having to re-resolve it from the session.
        $request->attributes->set('society', $society);
        view()->share('currentSociety', $society);

        return $next($request);
    }
}
