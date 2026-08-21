<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Facades\Auth;

/**
 * Base for every /api/v1 controller: the standard {success, message, data,
 * pagination} envelope from ARCHITECTURE.md §7.2, plus a shortcut to the
 * authenticated tenant user that AuthenticateApiToken populated on the
 * 'society' guard.
 */
abstract class ApiController extends Controller
{
    protected function user(): TenantUser
    {
        /** @var TenantUser $user */
        $user = Auth::guard('society')->user();

        return $user;
    }

    /**
     * The flat id(s) the authenticated resident actively occupies. Every
     * flat-scoped list (maintenance requests, bills, complaints, visitors)
     * is filtered through this rather than trusting a client-supplied
     * flat_id — ARCHITECTURE.md §17: "Never trust client input for IDs".
     *
     * @return array<int, int>
     */
    protected function myFlatIds(): array
    {
        return $this->user()->residencies()->active()->pluck('flat_id')->all();
    }

    protected function ok(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Same envelope as ok(), plus the pagination block from
     * ARCHITECTURE.md §7.2. $items is the resource collection to serialize
     * (e.g. MaintenanceRequestResource::collection($paginator)); $paginator
     * is the LengthAwarePaginator it was built from, kept separate so the
     * page metadata survives being wrapped in a Resource collection.
     */
    protected function paginated(mixed $items, AbstractPaginator $paginator, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => method_exists($paginator, 'total') ? $paginator->total() : null,
                'last_page' => method_exists($paginator, 'lastPage') ? $paginator->lastPage() : null,
            ],
        ]);
    }

    protected function fail(string $message, int $status = 422, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
