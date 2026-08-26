<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\EmergencyContactResource;
use App\Models\Tenant\EmergencyContact;
use Illuminate\Http\JsonResponse;

class EmergencyContactController extends ApiController
{
    public function index(): JsonResponse
    {
        $contacts = EmergencyContact::orderBy('sort_order')->get();

        return $this->ok(EmergencyContactResource::collection($contacts));
    }
}
