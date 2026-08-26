<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

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
            // No dedicated "raised on" column — the bill's created_at is
            // exactly that moment, so it doubles as the bill date shown in
            // the app rather than adding a column that would always match it.
            'bill_date' => $this->created_at?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            // The billing month this charge covers: a water-reading-generated
            // bill already carries this on its reading; a manually-raised
            // bill (e.g. a one-off penalty) has no such concept, so it falls
            // back to the due date's month.
            'billing_period' => $this->waterReading?->reading_month?->format('F Y')
                ?? $this->due_date?->format('F Y'),
            // There's no late-fee mechanism in this app yet (no rule to
            // compute one from), so this is always 0 rather than a made-up
            // number — still a real, current fact about the bill.
            'late_fee' => 0.0,
            'notes' => $this->notes,
            'breakdown' => $this->breakdown($request),
            'flat' => $this->whenLoaded('flat', fn () => new FlatResource($this->flat)),
            'payments' => $this->whenLoaded('payments', fn () => PaymentResource::collection($this->payments)),
            // The resident viewing their own bill — shown as the bill's
            // contact number, same as MainAppSeeder/other resources use
            // Auth::guard('society') to reach the authenticated tenant user.
            'mobile_number' => Auth::guard('society')->user()?->phone,
        ];
    }

    /**
     * @return array<int, array{label: string, amount: float}>
     */
    private function breakdown(Request $request): array
    {
        return $this->breakdownLines($request->attributes->get('api_society'));
    }
}
