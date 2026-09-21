<?php

namespace App\Services;

use App\Models\Tenant\DeviceToken;
use App\Models\Tenant\FlatResident;
use App\Models\Tenant\ResidentNotification;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin helper for producing rows in the resident notification feed from
 * the handful of admin actions that should generate one (see the callers:
 * Society\AnnouncementController::store(), Society\VisitorController::
 * store()/checkOut(), Society\PaymentController::store(),
 * Society\EventController::store(), and the visitor approval flow in
 * App\Services\VisitorWorkflow).
 *
 * Every notification is stored first (that row is what the app's
 * Notifications screen lists); a push to the user's registered devices is
 * then attempted only when the caller asks for it ($push) - used for the
 * time-critical visitor approval flow. A push can never fail the caller:
 * the outcome is recorded on the row (push_status / push_error) so it can
 * be retried with `php artisan notifications:retry-push`.
 */
class NotificationService
{
    public const MAX_PUSH_ATTEMPTS = 5;

    public function __construct(private FcmService $fcm) {}

    /**
     * @param  array<string, mixed>|null  $data
     */
    public function notify(
        int $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?array $data = null,
        ?int $visitorId = null,
        bool $push = false,
    ): ResidentNotification {
        $notification = ResidentNotification::create([
            'user_id' => $userId,
            'society_id' => app(TenantService::class)->getCurrentTenantId(),
            'visitor_id' => $visitorId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'push_status' => 'skipped',
        ]);

        if ($push) {
            $this->push($notification);
        }

        return $notification;
    }

    /**
     * Send (or re-send) the push for a stored notification to every active
     * device of its user. `sent` if at least one device accepted it; tokens
     * FCM says are dead are deactivated on the spot; anything else leaves
     * the row `failed` for a retry. Never throws.
     */
    public function push(ResidentNotification $notification): void
    {
        try {
            $tokens = DeviceToken::active()->where('user_id', $notification->user_id)->get();

            if ($tokens->isEmpty() || !$this->fcm->isConfigured()) {
                $notification->update([
                    'push_status' => 'skipped',
                    'push_error' => $tokens->isEmpty() ? 'No active device registered.' : 'FCM is not configured.',
                ]);

                return;
            }

            $payload = array_merge($notification->data ?? [], [
                'type' => $notification->type,
                'notification_id' => $notification->id,
                'visitor_id' => $notification->visitor_id,
                'society_id' => $notification->society_id,
            ]);

            $delivered = 0;
            $errors = [];

            foreach ($tokens as $device) {
                [$result, $error] = $this->fcm->send($device->token, $notification->title, (string) $notification->body, $payload, $device->platform);

                if ($result === FcmService::OK) {
                    $delivered++;
                    $device->update(['last_used_at' => now()]);
                } elseif ($result === FcmService::INVALID_TOKEN) {
                    $device->deactivate('Rejected by FCM: '.$error);
                } else {
                    $errors[] = $error;
                }
            }

            $notification->update([
                'push_status' => $delivered > 0 ? 'sent' : ($errors ? 'failed' : 'skipped'),
                'push_attempts' => $notification->push_attempts + 1,
                'push_sent_at' => $delivered > 0 ? now() : null,
                'push_error' => $delivered > 0 || !$errors ? null : mb_substr(implode('; ', array_unique($errors)), 0, 1000),
            ]);
        } catch (Throwable $e) {
            Log::error('Push notification failed', ['notification_id' => $notification->id, 'error' => $e->getMessage()]);

            try {
                $notification->update([
                    'push_status' => 'failed',
                    'push_attempts' => $notification->push_attempts + 1,
                    'push_error' => mb_substr($e->getMessage(), 0, 1000),
                ]);
            } catch (Throwable) {
                // Nothing more to do - the in-app row exists and the caller carries on.
            }
        }
    }

    /**
     * Notify the residents authorised to act for a flat - every active
     * FlatResident row with an active account - with a push. Used for
     * visitor requests, where the resident has to respond right now.
     *
     * @param  array<string, mixed>|null  $data
     * @return int  how many residents were notified
     */
    public function notifyFlatResidents(int $flatId, string $type, string $title, ?string $body, ?array $data, ?int $visitorId): int
    {
        $userIds = FlatResident::query()
            ->active()
            ->where('flat_id', $flatId)
            ->pluck('user_id')
            ->unique();

        $userIds = User::query()->active()->whereIn('id', $userIds)->pluck('id');

        foreach ($userIds as $userId) {
            $this->notify($userId, $type, $title, $body, $data, $visitorId, push: true);
        }

        return $userIds->count();
    }

    /**
     * Notify every resident on a set of flats (e.g. everyone affected by a
     * maintenance status change or a new bill) - resolved via active
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

        $userIds = FlatResident::query()
            ->active()
            ->whereIn('flat_id', $flatIds)
            ->pluck('user_id')
            ->unique();

        foreach ($userIds as $userId) {
            $this->notify($userId, $type, $title, $body, $data);
        }
    }

    /**
     * Notify every active resident in the society - used for society-wide
     * posts like a new announcement or event.
     *
     * @param  array<string, mixed>|null  $data
     */
    public function notifyAllResidents(string $type, string $title, ?string $body = null, ?array $data = null): void
    {
        $userIds = User::query()->active()->pluck('id');

        foreach ($userIds as $userId) {
            $this->notify($userId, $type, $title, $body, $data);
        }
    }
}
