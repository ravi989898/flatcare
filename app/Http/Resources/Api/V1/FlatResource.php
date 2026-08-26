<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant\Flat
 */
class FlatResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'flat_number' => $this->flat_number,
            'floor_number' => $this->floor_number,
            'flat_type' => $this->flat_type,
            'area_sqft' => $this->area_sqft !== null ? (float) $this->area_sqft : null,
            'owner_name' => $this->owner_name,
            'display_label' => $this->display_label,
            'block' => [
                'id' => $this->block?->id,
                'name' => $this->block?->name,
            ],
        ];
    }
}
