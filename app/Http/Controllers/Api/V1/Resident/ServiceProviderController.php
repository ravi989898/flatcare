<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\ServiceProviderResource;
use App\Models\Tenant\ServiceProvider;
use Illuminate\Http\JsonResponse;

class ServiceProviderController extends ApiController
{
    public function index(): JsonResponse
    {
        $providers = ServiceProvider::orderBy('sort_order')->get();

        return $this->ok(ServiceProviderResource::collection($providers));
    }
}
