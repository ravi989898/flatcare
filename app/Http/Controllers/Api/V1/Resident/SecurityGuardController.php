<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\SecurityGuardResource;
use App\Models\Tenant\SecurityGuardLog;
use Illuminate\Http\JsonResponse;

class SecurityGuardController extends ApiController
{
    /**
     * The guard currently holding each shift - the open (ended_at null)
     * duty log row per shift. `day`/`night` are null when that shift is
     * currently vacant.
     */
    public function index(): JsonResponse
    {
        $onDuty = SecurityGuardLog::with('securityGuard')
            ->whereNull('ended_at')
            ->get()
            ->keyBy('shift');

        return $this->ok([
            'day' => optional($onDuty->get('day'))->securityGuard ? new SecurityGuardResource($onDuty->get('day')->securityGuard) : null,
            'night' => optional($onDuty->get('night'))->securityGuard ? new SecurityGuardResource($onDuty->get('night')->securityGuard) : null,
        ]);
    }
}
