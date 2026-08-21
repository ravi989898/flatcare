<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Resident\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;

class ProfileController extends ApiController
{
    public function show(): JsonResponse
    {
        $user = $this->user()->load(['roles', 'residencies.flat.block']);

        return $this->ok(new UserResource($user));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->user();
        $user->update($request->validated());

        return $this->ok(new UserResource($user->fresh(['roles', 'residencies.flat.block'])), 'Profile updated successfully.');
    }
}
