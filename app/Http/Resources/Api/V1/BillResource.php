<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant\MaintenanceBill
 */
class BillResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'amount' => (float) $this->amount,
            'paid_amount' => $this->paid_amount,
            'balance' => $this->balance,
            'status' => $this->status,
            'due_date' => $this->due_date?->toDateString(),
            'notes' => $this->notes,
            'flat' => $this->whenLoaded('flat', fn () => new FlatResource($this->flat)),
            'payments' => $this->whenLoaded('payments', fn () => PaymentResource::collection($this->payments)),
        ];
    }
}
