<?php

namespace App\Http\Controllers\Api\V1\Guard;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\GuardVehicleResource;
use App\Models\Tenant\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Society-wide, read-only vehicle list for the gate app, so a guard can
 * check whether a vehicle at the gate is actually registered to a
 * resident. Resident\VehicleController is the resident-facing equivalent,
 * scoped to their own vehicles only.
 */
class VehicleController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::active()->with([
            'user.residencies' => fn ($q) => $q->active()->with('flat'),
        ]);

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('registration_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $vehicles = $query->orderBy('registration_number')->paginate(20);

        return $this->paginated(GuardVehicleResource::collection($vehicles), $vehicles);
    }
}
