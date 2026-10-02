<?php

namespace App\Console\Commands;

use App\Models\SocietyDatabase;
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

        // Failed pushes, and ones that never went out (see NotificationService::retryPending()).
        foreach (SocietyDatabase::active()->get() as $societyDatabase) {
            $tenants->setTenant($societyDatabase->society_id);

            $retried += $notifications->retryPending((int) $this->option('max-age'));
        }

        $this->info("Retried {$retried} push notification(s).");

        return self::SUCCESS;
    }
}
