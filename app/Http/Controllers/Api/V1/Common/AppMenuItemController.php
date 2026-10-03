<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AppMenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Which of the mobile app's own menu items (see mobile/lib/features/home/
 * screens/home_screen.dart's hardcoded _MenuItem list, each one keyed to a
 * row here) the signed-in user's role is allowed to see - set per role by
 * their society's Admin under the web portal's Settings -> Permissions ->
 * App Permission. The app fetches this once per session and hides any
 * tile whose key isn't in the returned list; it still defaults to showing
 * everything if this call fails, since this is a display preference, not
 * an access control boundary (every route it links to is already guarded
 * server-side on its own).
 */
class AppMenuItemController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $society = $request->attributes->get('api_society');

        // 'managed' = every tile App Permission controls; the app hides a
        // managed tile that isn't in 'keys' and leaves all other tiles alone.
        return $this->ok([
            'keys' => AppMenuItem::visibleKeysForUser($this->user(), $society?->id),
            'managed' => AppMenuItem::orderBy('display_order')->pluck('key')->all(),
        ]);
    }
}
