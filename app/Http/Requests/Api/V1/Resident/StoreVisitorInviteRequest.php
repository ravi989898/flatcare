<?php

namespace App\Http\Requests\Api\V1\Resident;

use App\Models\Tenant\Visitor;
use App\Rules\SafeUploadedFile;
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
            'visitor_phone' => ['required', 'string', 'max:20'],
            'visitor_email' => ['nullable', 'email', 'max:255'],
            'purpose' => ['required', 'in:'.implode(',', Visitor::PURPOSES)],
            'vehicle_number' => ['nullable', 'string', 'max:20'],
            'entry_kind' => ['sometimes', 'in:'.implode(',', Visitor::ENTRY_KINDS)],
            // A dated Gate Pass needs its From/To window; the quicker
            // Pre-Approval form has none.
            'expected_at' => ['nullable', 'required_if:entry_kind,gate_pass', 'date'],
            'valid_until' => ['nullable', 'required_if:entry_kind,gate_pass', 'date', 'after_or_equal:expected_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photo' => [
                'nullable',
                'image',
                'max:4096',
                'mimes:jpg,jpeg,png,webp',
                new SafeUploadedFile(['jpg', 'jpeg', 'png', 'webp']),
            ],
        ];
    }
}
