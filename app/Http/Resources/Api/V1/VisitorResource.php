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
            'visitor_email' => $this->visitor_email,
            'purpose' => $this->purpose,
            'vehicle_number' => $this->vehicle_number,
            'status' => $this->status,
            // True only for a guard-raised entry request still awaiting the
            // resident's decision — not a resident's own self-invite (which
            // is also `pending` but has invited_by_user_id set). Drives the
            // Approve/Reject buttons in the mobile app.
            'awaiting_approval' => $this->status === 'pending' && is_null($this->invited_by_user_id),
            'check_in_at' => $this->check_in_at?->toIso8601String(),
            'check_out_at' => $this->check_out_at?->toIso8601String(),
            'expected_at' => $this->expected_at?->toIso8601String(),
            'valid_until' => $this->valid_until?->toIso8601String(),
            'entry_kind' => $this->entry_kind,
            'pass_code' => $this->pass_code,
            'notes' => $this->notes,
            'photo_url' => $this->photo_path ? asset('storage/'.$this->photo_path) : null,
            'flat' => $this->whenLoaded('flat', fn () => new FlatResource($this->flat)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
