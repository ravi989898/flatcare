<?php

namespace App\Http\Middleware;

use App\Models\Tenant\User as TenantUser;
use App\Services\Api\ApiTokenService;
use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer-token auth for /api/v1/*. Mirrors what SetSocietyContext does for
 * the session-based web portal, but resolves the tenant from the token
 * instead of the session — see api_tokens migration for why this isn't
 * plain Sanctum.
 *
 * On success this leaves the 'society' DB connection pointed at the
 * caller's tenant database and the 'society' auth guard populated with
 * their Tenant\User, so controllers can keep using
 * Auth::guard('society')->user() / ->id() exactly like the web controllers
 * already do.
 */
class AuthenticateApiToken
{
    public function __construct(
        private ApiTokenService $tokenService,
        private TenantService $tenantService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (!$plainTextToken) {
            return $this->unauthorized('Authentication token missing.');
        }

        $token = $this->tokenService->resolve($plainTextToken);

        if (!$token) {
            return $this->unauthorized('Authentication token is invalid or has expired.');
        }

        $society = $token->society;

        if (!$society || !$this->tenantService->validateSocietyAccessPeriod($society)) {
            return $this->forbidden('Your society access has expired or is inactive.');
        }

        $this->tenantService->setTenant($society);

        $user = TenantUser::find($token->tenant_user_id);

        if (!$user) {
            return $this->unauthorized('Account not found.');
        }

        if ($user->status !== 'active') {
            return $this->forbidden('This account is not active. Please contact your society administrator.');
        }

        Auth::guard('society')->setUser($user);

        $request->attributes->set('api_society', $society);
        $request->attributes->set('api_token', $token);

        $this->tokenService->touch($token);

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json(['success' => false, 'message' => $message], 401);
    }

    private function forbidden(string $message): Response
    {
        return response()->json(['success' => false, 'message' => $message], 403);
    }
}
