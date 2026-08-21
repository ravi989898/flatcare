<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant\Complaint
 */
class ComplaintResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'subject' => $this->subject,
            'description' => $this->description,
            'against' => $this->against,
            'priority' => $this->priority,
            'status' => $this->status,
            'flat' => $this->whenLoaded('flat', fn () => new FlatResource($this->flat)),
            'resolution_notes' => $this->resolution_notes,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'history' => $this->when($request->routeIs('*.show'), $this->history),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
