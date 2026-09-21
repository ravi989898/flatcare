<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Tenant\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Registers the calling device's FCM token so the backend can push to it.
 * Used by every role (resident and gate keeper alike) - a token is only
 * ever attached to the authenticated user, never to a user id from the
 * request body. Called by the app at login and whenever FCM rotates the
 * token; a user with several devices simply has several active rows.
 */
class DeviceTokenController extends ApiController
{
    /**
     * Idempotent register/refresh. If this exact token was previously
     * registered to someone else (a shared or handed-over phone) it is
     * re-assigned to the current user, so the previous user stops receiving
     * this device's pushes.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:20', 'max:4096'],
            'platform' => ['nullable', 'in:android,ios,web'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $hash = DeviceToken::hash($data['token']);

        $device = DeviceToken::firstOrNew(['token_hash' => $hash]);
        $device->fill([
            'user_id' => $this->user()->id,
            'token' => $data['token'],
            'platform' => $data['platform'] ?? $device->platform,
            'device_name' => $data['device_name'] ?? $device->device_name,
            'is_active' => true,
            'last_used_at' => now(),
            'deactivated_at' => null,
            'deactivation_reason' => null,
        ])->save();

        return $this->ok(['id' => $device->id], 'Device registered.', 201);
    }

    /**
     * Stop pushes to this device (called on logout, before the API token is
     * revoked). Only the user's own registration can be removed.
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:4096']]);

        DeviceToken::where('user_id', $this->user()->id)
            ->where('token_hash', DeviceToken::hash($data['token']))
            ->get()
            ->each(fn (DeviceToken $device) => $device->deactivate('Logged out'));

        return $this->ok(null, 'Device unregistered.');
    }
}
