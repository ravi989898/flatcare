<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\VisitorResource;
use App\Models\Tenant\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only: visitor check-in/check-out is a gate-security action, done
 * from the web portal today. Residents can see who came/is at their flat.
 * Pre-approving an expected visitor isn't modeled yet (no "pending" status
 * on Visitor) — left for a later phase.
 */
class VisitorController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $visitors = Visitor::with('flat.block')
            ->whereIn('flat_id', $this->myFlatIds())
            ->status($request->string('status')->trim()->value() ?: null)
            ->latest('check_in_at')
            ->paginate(15);

        return $this->paginated(VisitorResource::collection($visitors), $visitors);
    }

    public function show(int $id): JsonResponse
    {
        $visitor = Visitor::with('flat.block')
            ->whereIn('flat_id', $this->myFlatIds())
            ->findOrFail($id);

        return $this->ok(new VisitorResource($visitor));
    }
}
