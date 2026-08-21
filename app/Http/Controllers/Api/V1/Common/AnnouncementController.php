<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\AnnouncementResource;
use App\Models\Tenant\Announcement;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends ApiController
{
    public function index(): JsonResponse
    {
        $announcements = Announcement::with('postedBy')
            ->visible()
            ->pinnedFirst()
            ->paginate(10);

        return $this->paginated(AnnouncementResource::collection($announcements), $announcements);
    }

    public function show(int $id): JsonResponse
    {
        $announcement = Announcement::with('postedBy')->visible()->findOrFail($id);

        return $this->ok(new AnnouncementResource($announcement));
    }
}
