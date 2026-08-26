<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant\Visitor
 */
class VisitorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'visitor_name' => $this->visitor_name,
            'visitor_phone' => $this->visitor_phone,
            'purpose' => $this->purpose,
            'vehicle_number' => $this->vehicle_number,
            'status' => $this->status,
            'check_in_at' => $this->check_in_at?->toIso8601String(),
            'check_out_at' => $this->check_out_at?->toIso8601String(),
            'expected_at' => $this->expected_at?->toIso8601String(),
            'pass_code' => $this->pass_code,
            'notes' => $this->notes,
            'flat' => $this->whenLoaded('flat', fn () => new FlatResource($this->flat)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
