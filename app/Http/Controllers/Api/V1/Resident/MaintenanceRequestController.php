<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Resident\StoreMaintenanceRequestRequest;
use App\Http\Resources\Api\V1\MaintenanceRequestResource;
use App\Models\Tenant\MaintenanceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceRequestController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $requests = MaintenanceRequest::with('flat.block')
            ->whereIn('flat_id', $this->myFlatIds())
            ->status($request->string('status')->trim()->value() ?: null)
            ->latest()
            ->paginate(15);

        return $this->paginated(MaintenanceRequestResource::collection($requests), $requests);
    }

    public function store(StoreMaintenanceRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (!in_array((int) $validated['flat_id'], $this->myFlatIds(), true)) {
            return $this->fail('You can only raise a request for your own flat.', 403);
        }

        $maintenanceRequest = MaintenanceRequest::create([
            ...$validated,
            'status' => 'open',
            'raised_by_name' => $this->user()->name,
            'raised_by_phone' => $this->user()->phone,
            'raised_by_user_id' => $this->user()->id,
        ]);

        $maintenanceRequest->logHistory('created', null, $this->user()->id);

        return $this->ok(new MaintenanceRequestResource($maintenanceRequest->load('flat.block')), 'Request logged successfully.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $maintenanceRequest = MaintenanceRequest::with('flat.block')
            ->whereIn('flat_id', $this->myFlatIds())
            ->findOrFail($id);

        return $this->ok(new MaintenanceRequestResource($maintenanceRequest));
    }
}
