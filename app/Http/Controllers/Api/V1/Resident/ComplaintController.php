<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Resident\StoreComplaintRequest;
use App\Http\Resources\Api\V1\ComplaintResource;
use App\Models\Tenant\Complaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplaintController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $complaints = Complaint::with('flat.block')
            ->whereIn('flat_id', $this->myFlatIds())
            ->status($request->string('status')->trim()->value() ?: null)
            ->latest()
            ->paginate(15);

        return $this->paginated(ComplaintResource::collection($complaints), $complaints);
    }

    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (!in_array((int) $validated['flat_id'], $this->myFlatIds(), true)) {
            return $this->fail('You can only raise a complaint for your own flat.', 403);
        }

        $complaint = Complaint::create([
            ...$validated,
            'status' => 'open',
            'raised_by_name' => $this->user()->name,
            'raised_by_phone' => $this->user()->phone,
            'raised_by_user_id' => $this->user()->id,
        ]);

        $complaint->logHistory('created', null, $this->user()->id);

        return $this->ok(new ComplaintResource($complaint->load('flat.block')), 'Complaint submitted successfully.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $complaint = Complaint::with('flat.block')
            ->whereIn('flat_id', $this->myFlatIds())
            ->findOrFail($id);

        return $this->ok(new ComplaintResource($complaint));
    }
}
