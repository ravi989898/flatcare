<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * VehicleResource plus the owner/flat info a guard needs to verify a
 * vehicle at the gate — a resident already knows whose vehicle is whose
 * (VehicleResource, used on their own "My Vehicles" screen), a guard
 * doesn't.
 *
 * @mixin \App\Models\Tenant\Vehicle
 */
class GuardVehicleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Already constrained to active-only by the eager load in
        // Api\V1\Guard\VehicleController::index.
        $activeResidency = $this->user?->residencies->first();

        return [
            'id' => $this->id,
            'vehicle_type' => $this->vehicle_type,
            'registration_number' => $this->registration_number,
            'model' => $this->model,
            'color' => $this->color,
            'year' => $this->year,
            'parking_slot' => $this->parking_slot,
            'status' => $this->status,
            'owner_name' => $this->user?->name,
            'owner_phone' => $this->user?->phone,
            'flat_label' => $activeResidency?->flat?->display_label,
        ];
    }
}
