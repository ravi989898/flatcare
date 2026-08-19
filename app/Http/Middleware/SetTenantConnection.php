<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetTenantConnection Middleware
 *
 * This middleware:
 * 1. Extracts tenant context from authenticated user
 * 2. Validates tenant access period and status
 * 3. Switches database connection to tenant database
 * 4. Validates module access
 *
 * Runs on all tenant-specific routes after authentication
 */
class SetTenantConnection
{
    public function __construct(
        private TenantService $tenantService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Only process authenticated requests
        if (!auth()->check()) {
            return $next($request);
        }

        try {
            $user = auth()->user();

            // Get tenant ID from user - DO NOT trust from request
            $tenantId = $this->getTenantIdFromUser($user);

            if (!$tenantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant context not found for this user',
                ], 403);
            }

            // Validate tenant access
            if (!$this->validateTenantAccess($tenantId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your society access has expired or is inactive',
                ], 403);
            }

            // Set tenant in service
            $this->tenantService->setTenant($tenantId);

            // Store tenant context in request for later use
            $request->merge([
                'tenant_id' => $tenantId,
                'society' => $this->tenantService->getCurrentSociety(),
            ]);

            // Set tenant ID in exception handler for logging
            app()->instance('current_tenant_id', $tenantId);

            Log::debug("Tenant context set", [
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'path' => $request->path(),
            ]);

            return $next($request);
        } catch (\Exception $e) {
            Log::error("Tenant context setup failed", [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to establish tenant context',
            ], 500);
        }
    }

    /**
     * Extract tenant ID from authenticated user
     * DO NOT trust tenant_id from request - derive from user context
     */
    private function getTenantIdFromUser($user): ?int
    {
        // For now, we'll assume users belong to a tenant
        // This will be refined when we implement full role/permission system
        // Likely stored in user table or session

        // Temporary: Extract from API token or session
        if ($token = auth()->getToken()) {
            // Get tenant from token claims if using JWT-like tokens
            // For Sanctum: stored in user model or token relation
            return $user->tenant_id ?? $user->society_id ?? null;
        }

        return null;
    }

    /**
     * Validate tenant access
     */
    private function validateTenantAccess(int $tenantId): bool
    {
        try {
            // Get society from main database
            $society = DB::connection('main')
                ->table('societies')
                ->find($tenantId);

            if (!$society) {
                Log::warning("Society not found", ['society_id' => $tenantId]);
                return false;
            }

            // Validate access period
            $today = today();
            if ($today < $society->start_date || $today > $society->end_date) {
                Log::warning("Society access period expired", [
                    'society_id' => $tenantId,
                    'start_date' => $society->start_date,
                    'end_date' => $society->end_date,
                ]);
                return false;
            }

            // Validate society status
            if ($society->status !== 'active') {
                Log::warning("Society is not active", [
                    'society_id' => $tenantId,
                    'status' => $society->status,
                ]);
                return false;
            }

            // Validate database connection exists
            $dbConnection = DB::connection('main')
                ->table('society_databases')
                ->where('society_id', $tenantId)
                ->where('status', 'active')
                ->first();

            if (!$dbConnection) {
                Log::warning("Society database not found", ['society_id' => $tenantId]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Tenant access validation failed", [
                'society_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
