<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Resident\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\Resident\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

    /**
     * Change Password (mobile Profile screen) — distinct from the
     * pre-login /auth/forgot-password + /auth/reset-password pair, which
     * requires no current password since the resident is locked out.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $this->user();

        if (!Hash::check($request->string('current_password')->value(), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Your current password is incorrect.',
            ]);
        }

        $user->update(['password' => Hash::make($request->string('password')->value())]);

        return $this->ok(null, 'Password changed successfully.');
    }
}
