<?php

namespace App\Console\Commands;

use App\Models\SocietyDatabase;
use App\Models\Tenant\DeviceToken;
use App\Models\Tenant\ResidentNotification;
use App\Services\FcmService;
use App\Services\TenantService;
use Illuminate\Console\Command;

/**
 * Checks push notifications on a server, step by step:
 *
 *   php artisan fcm:test                          config + Google login, and each society's devices / last pushes
 *   php artisan fcm:test --society=1 --user=5     ...and send a test push to that user's phones
 *
 * Each step says what is wrong and what to do, so a live server can be
 * fixed without guessing.
 */
class TestPushNotifications extends Command
{
    protected $signature = 'fcm:test {--society= : Society id to check / send in} {--user= : User id (in that society) to send a test push to}';

    protected $description = 'Check the push notification (FCM) setup and optionally send a test push';

    public function handle(FcmService $fcm, TenantService $tenants): int
    {
        $this->line('1. Firebase credentials and Google login');

        if ($error = $fcm->diagnose()) {
            $this->error('   '.$error);
            $this->line('   See mobile/FCM_SETUP.md, step 2. After changing .env run: php artisan config:clear');

            return self::FAILURE;
        }

        $this->info('   OK - Google issued an access token.');

        $societies = SocietyDatabase::active()
            ->when($this->option('society'), fn ($query, $id) => $query->where('society_id', $id))
            ->get();

        foreach ($societies as $societyDatabase) {
            $tenants->setTenant($societyDatabase->society_id);
            $this->newLine();
            $this->line("2. Society #{$societyDatabase->society_id}");

            $active = DeviceToken::active()->count();
            $this->line("   Active registered devices: {$active}");

            if ($active === 0) {
                $this->warn('   No phone has registered. Log out and log in again in the app (a build made with google-services.json).');
            }

            foreach (ResidentNotification::query()->latest('id')->take(5)->get() as $notification) {
                $this->line(sprintf(
                    '   #%d %s %s user=%d push=%s %s',
                    $notification->id,
                    $notification->created_at,
                    $notification->type,
                    $notification->user_id,
                    $notification->push_status,
                    (string) $notification->push_error,
                ));
            }
        }

        if (!$this->option('user')) {
            return self::SUCCESS;
        }

        if (!$this->option('society')) {
            $this->error('Pass --society together with --user.');

            return self::FAILURE;
        }

        $tenants->setTenant((int) $this->option('society'));
        $devices = DeviceToken::active()->where('user_id', $this->option('user'))->get();

        $this->newLine();
        $this->line("3. Test push to user #{$this->option('user')} ({$devices->count()} device(s))");

        if ($devices->isEmpty()) {
            $this->error('   This user has no active device. Log in on the phone first.');

            return self::FAILURE;
        }

        $sent = 0;

        foreach ($devices as $device) {
            [$result, $error] = $fcm->send($device->token, 'FlatCare test', 'Push notifications are working.', ['type' => 'test'], $device->platform);

            if ($result === FcmService::OK) {
                $sent++;
                $this->info("   Device #{$device->id} ({$device->platform}): sent");
            } else {
                if ($result === FcmService::INVALID_TOKEN) {
                    $device->deactivate('Rejected by FCM: '.$error);
                }
                $this->error("   Device #{$device->id} ({$device->platform}): {$error}");
            }
        }

        return $sent > 0 ? self::SUCCESS : self::FAILURE;
    }
}
