<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant\Vehicle
 */
class VehicleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_type' => $this->vehicle_type,
            'registration_number' => $this->registration_number,
            'model' => $this->model,
            'color' => $this->color,
            'year' => $this->year,
            'parking_slot' => $this->parking_slot,
            'status' => $this->status,
        ];
    }
}
