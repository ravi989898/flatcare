<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Resident\FamilyMemberRequest;
use App\Http\Resources\Api\V1\FamilyMemberResource;
use App\Models\Tenant\FamilyMember;
use Illuminate\Http\JsonResponse;

/**
 * Every lookup is scoped to the authenticated user's own family members —
 * the id in the URL is never trusted on its own (ARCHITECTURE.md §17).
 */
class FamilyMemberController extends ApiController
{
    public function index(): JsonResponse
    {
        $members = FamilyMember::where('user_id', $this->user()->id)->active()->get();

        return $this->ok(FamilyMemberResource::collection($members));
    }

    public function store(FamilyMemberRequest $request): JsonResponse
    {
        $member = FamilyMember::create([
            ...$request->validated(),
            'user_id' => $this->user()->id,
            'status' => 'active',
        ]);

        return $this->ok(new FamilyMemberResource($member), 'Family member added.', 201);
    }

    public function update(FamilyMemberRequest $request, int $id): JsonResponse
    {
        $member = FamilyMember::where('user_id', $this->user()->id)->findOrFail($id);
        $member->update($request->validated());

        return $this->ok(new FamilyMemberResource($member), 'Family member updated.');
    }

    public function destroy(int $id): JsonResponse
    {
        $member = FamilyMember::where('user_id', $this->user()->id)->findOrFail($id);
        $member->delete();

        return $this->ok(null, 'Family member removed.');
    }
}
