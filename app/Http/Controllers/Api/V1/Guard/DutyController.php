<?php

namespace App\Http\Controllers\Api\V1\Guard;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Concerns\ManagesSecurityGuardDuty;
use App\Models\Tenant\SecurityGuard;
use App\Models\Tenant\SecurityGuardLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lets a guard start/end their own duty shift from the app, instead of only
 * a Society Admin assigning it from the web portal
 * (Society\SecurityGuardController) — same ManagesSecurityGuardDuty
 * bookkeeping either way, so "who's on Day/Night duty" stays consistent
 * regardless of which side made the change.
 */
class DutyController extends ApiController
{
    use ManagesSecurityGuardDuty;

    public function show(): JsonResponse
    {
        $guard = $this->guard();
        $openLog = SecurityGuardLog::where('security_guard_id', $guard->id)->whereNull('ended_at')->first();

        return $this->ok([
            'name' => $guard->name,
            'phone' => $guard->phone,
            'status' => $guard->status,
            'assigned_shift' => $guard->shift,
            'on_duty' => $openLog !== null,
            'current_shift' => $openLog?->shift,
            'started_at' => $openLog?->started_at?->toIso8601String(),
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shift' => 'required|in:day,night',
        ]);

        $this->assignShift($this->guard(), $validated['shift']);

        return $this->ok(null, 'You are now on duty.');
    }

    public function end(): JsonResponse
    {
        $this->endDuty($this->guard());

        return $this->ok(null, 'You are now off duty.');
    }

    private function guard(): SecurityGuard
    {
        return SecurityGuard::where('phone', $this->user()->phone)->firstOrFail();
    }
}
