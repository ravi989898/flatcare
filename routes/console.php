<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Re-send visitor-approval pushes that failed to reach FCM. Needs the usual
// `* * * * * php artisan schedule:run` cron entry (see DEPLOYMENT.md).
Schedule::command('notifications:retry-push')->everyMinute()->withoutOverlapping();
