<?php

namespace App\Console\Commands;

use App\Models\SocietyDatabase;
use App\Models\Tenant\ResidentNotification;
use App\Services\NotificationService;
use App\Services\TenantService;
use Illuminate\Console\Command;

/**
 * Re-sends pushes that failed (FCM outage, timeout) across every society.
 * The in-app notification and the visitor row were already stored, so this
 * only retries delivery; it stops after NotificationService::MAX_PUSH_ATTEMPTS
 * tries and gives up on anything older than --max-age minutes (default 30)
 * so nobody gets an "approve this visitor" push for someone who left long ago.
 * Scheduled every minute (routes/console.php).
 */
class RetryFailedPushes extends Command
{
    protected $signature = 'notifications:retry-push {--max-age=30 : Only retry notifications newer than this many minutes}';

    protected $description = 'Retry push notifications that failed to send';

    public function handle(TenantService $tenants, NotificationService $notifications): int
    {
        $retried = 0;

        foreach (SocietyDatabase::active()->get() as $societyDatabase) {
            $tenants->setTenant($societyDatabase->society_id);

            ResidentNotification::query()
                ->where('push_status', 'failed')
                ->where('push_attempts', '<', NotificationService::MAX_PUSH_ATTEMPTS)
                ->where('created_at', '>=', now()->subMinutes((int) $this->option('max-age')))
                ->with('visitor')
                ->get()
                ->each(function (ResidentNotification $notification) use ($notifications, &$retried) {
                    // A request that has since been decided needn't be pushed as "please decide".
                    if ($notification->type === 'visitor_request' && $notification->visitor?->status !== 'pending') {
                        $notification->update(['push_status' => 'skipped', 'push_error' => 'Request no longer pending.']);

                        return;
                    }

                    $notifications->push($notification);
                    $retried++;
                });
        }

        $this->info("Retried {$retried} push notification(s).");

        return self::SUCCESS;
    }
}
