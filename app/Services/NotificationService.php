<?php

namespace App\Services;

use App\Models\Tenant\ResidentNotification;

/**
 * Thin helper for producing rows in the resident notification feed from
 * the handful of admin actions that should generate one (see the callers:
 * Society\AnnouncementController::store(), Society\MaintenanceController::
 * update(), Society\VisitorController::store()/checkOut(),
 * Society\PaymentController::store(), Society\EventController::store()).
 * Deliberately not queued/broadcast — the mobile app just polls
 * GET /notifications, so a synchronous insert is all this needs today.
 */
class NotificationService
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public function notify(int $userId, string $type, string $title, ?string $body = null, ?array $data = null): ResidentNotification
    {
        return ResidentNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    /**
     * Notify every resident on a set of flats (e.g. everyone affected by a
     * maintenance status change or a new bill) — resolved via active
     * FlatResident rows rather than a raw users query, so it only reaches
     * residents currently linked to those flats.
     *
     * @param  array<int>  $flatIds
     * @param  array<string, mixed>|null  $data
     */
    public function notifyFlats(array $flatIds, string $type, string $title, ?string $body = null, ?array $data = null): void
    {
        if (empty($flatIds)) {
            return;
        }

        $userIds = \App\Models\Tenant\FlatResident::query()
            ->active()
            ->whereIn('flat_id', $flatIds)
            ->pluck('user_id')
            ->unique();

        foreach ($userIds as $userId) {
            $this->notify($userId, $type, $title, $body, $data);
        }
    }

    /**
     * Notify every active resident in the society — used for society-wide
     * posts like a new announcement or event.
     *
     * @param  array<string, mixed>|null  $data
     */
    public function notifyAllResidents(string $type, string $title, ?string $body = null, ?array $data = null): void
    {
        $userIds = \App\Models\Tenant\User::query()->active()->pluck('id');

        foreach ($userIds as $userId) {
            $this->notify($userId, $type, $title, $body, $data);
        }
    }
}
