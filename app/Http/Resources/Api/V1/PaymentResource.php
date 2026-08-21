<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant\Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'payment_date' => $this->payment_date?->toDateString(),
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
        ];
    }
}
