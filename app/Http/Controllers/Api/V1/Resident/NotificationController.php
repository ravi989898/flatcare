<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\Tenant\ResidentNotification;
use Illuminate\Http\JsonResponse;

class NotificationController extends ApiController
{
    public function index(): JsonResponse
    {
        $notifications = ResidentNotification::where('user_id', $this->user()->id)
            ->latest()
            ->paginate(20);

        return $this->paginated(NotificationResource::collection($notifications), $notifications);
    }

    public function unreadCount(): JsonResponse
    {
        $count = ResidentNotification::where('user_id', $this->user()->id)->unread()->count();

        return $this->ok(['count' => $count]);
    }

    public function markRead(int $id): JsonResponse
    {
        $notification = ResidentNotification::where('user_id', $this->user()->id)->findOrFail($id);

        if (!$notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }

        return $this->ok(new NotificationResource($notification));
    }

    public function markAllRead(): JsonResponse
    {
        ResidentNotification::where('user_id', $this->user()->id)->unread()->update(['read_at' => now()]);

        return $this->ok(null, 'All notifications marked as read.');
    }
}
