<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Resident\VehicleRequest;
use App\Http\Resources\Api\V1\VehicleResource;
use App\Models\Tenant\Vehicle;
use Illuminate\Http\JsonResponse;

class VehicleController extends ApiController
{
    public function index(): JsonResponse
    {
        $vehicles = Vehicle::where('user_id', $this->user()->id)->active()->get();

        return $this->ok(VehicleResource::collection($vehicles));
    }

    public function store(VehicleRequest $request): JsonResponse
    {
        $vehicle = Vehicle::create([
            ...$request->validated(),
            'user_id' => $this->user()->id,
            'status' => 'active',
        ]);

        return $this->ok(new VehicleResource($vehicle), 'Vehicle added.', 201);
    }

    public function update(VehicleRequest $request, int $id): JsonResponse
    {
        $vehicle = Vehicle::where('user_id', $this->user()->id)->findOrFail($id);
        $vehicle->update($request->validated());

        return $this->ok(new VehicleResource($vehicle), 'Vehicle updated.');
    }

    public function destroy(int $id): JsonResponse
    {
        $vehicle = Vehicle::where('user_id', $this->user()->id)->findOrFail($id);
        $vehicle->delete();

        return $this->ok(null, 'Vehicle removed.');
    }
}
