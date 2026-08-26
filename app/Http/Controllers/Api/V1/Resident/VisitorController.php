<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Resident\StoreVisitorInviteRequest;
use App\Http\Resources\Api\V1\VisitorResource;
use App\Models\Tenant\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Check-in/check-out is still a gate-security action done from the web
 * portal (Society\VisitorController). Residents can see who came/is at
 * their flat, and — via store() — pre-approve an expected visitor
 * ("Invite Visitor" / "Gate Pass" in the mobile app), which lands as a
 * `pending` row the gate desk later checks in when the visitor arrives.
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

    public function store(StoreVisitorInviteRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (!in_array((int) $validated['flat_id'], $this->myFlatIds(), true)) {
            return $this->fail('You can only invite a visitor to your own flat.', 403);
        }

        $visitor = Visitor::create([
            ...$validated,
            'status' => 'pending',
            'invited_by_user_id' => $this->user()->id,
            'pass_code' => strtoupper(Str::random(6)),
        ]);

        return $this->ok(new VisitorResource($visitor->load('flat.block')), 'Visitor invited successfully.', 201);
    }
}
