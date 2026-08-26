<?php

namespace App\Http\Requests\Api\V1\Resident;

use App\Models\Tenant\Visitor;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "Invite Visitor" / "Gate Pass" — a resident pre-approving an expected
 * visitor. flat_id ownership is re-checked in the controller against
 * myFlatIds(), same as every other resident store endpoint.
 */
class StoreVisitorInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'flat_id' => ['required', 'exists:flats,id'],
            'visitor_name' => ['required', 'string', 'max:255'],
            'visitor_phone' => ['nullable', 'string', 'max:20'],
            'purpose' => ['required', 'in:'.implode(',', Visitor::PURPOSES)],
            'vehicle_number' => ['nullable', 'string', 'max:20'],
            'expected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
