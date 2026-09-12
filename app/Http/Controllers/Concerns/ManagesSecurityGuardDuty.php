<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Tenant\SecurityGuard;
use App\Models\Tenant\SecurityGuardLog;

/**
 * Shared by the Super Admin and Society Admin security-guard controllers
 * (App\Http\Controllers\Admin\SocietySecurityController and
 * App\Http\Controllers\Society\SecurityGuardController) so the duty-log
 * bookkeeping behind "who's on Day/Night duty, and since when" only lives
 * in one place.
 */
trait ManagesSecurityGuardDuty
{
    /**
     * Put $guard on duty for $shift, starting now. Closes whichever other
     * guard currently holds that shift (their log row's ended_at is null),
     * and closes this guard's own open log if they were on a *different*
     * shift. Safe to call repeatedly with no shift change — it's a no-op
     * once this guard already holds this shift with an open log.
     */
    private function assignShift(SecurityGuard $guard, string $shift): void
    {
        SecurityGuardLog::where('shift', $shift)
            ->where('security_guard_id', '!=', $guard->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);

        $openLog = SecurityGuardLog::where('security_guard_id', $guard->id)->whereNull('ended_at')->first();

        if ($openLog && $openLog->shift === $shift) {
            return;
        }

        if ($openLog) {
            $openLog->update(['ended_at' => now()]);
        }

        SecurityGuardLog::create([
            'security_guard_id' => $guard->id,
            'shift' => $shift,
            'started_at' => now(),
        ]);
    }

    /**
     * Take $guard off duty entirely (deactivation) — closes their open log
     * row, if any, leaving that shift vacant until someone else is assigned.
     */
    private function endDuty(SecurityGuard $guard): void
    {
        SecurityGuardLog::where('security_guard_id', $guard->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);
    }

    /**
     * The guard currently holding each shift ('day'/'night' => SecurityGuard),
     * for the "on duty now" summary at the top of the guard list.
     */
    private function currentDutyByShift()
    {
        return SecurityGuardLog::with('securityGuard')
            ->whereNull('ended_at')
            ->get()
            ->keyBy('shift')
            ->map(fn (SecurityGuardLog $log) => $log->securityGuard);
    }
}
