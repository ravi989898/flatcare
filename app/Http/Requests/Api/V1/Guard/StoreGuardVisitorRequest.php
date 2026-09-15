<?php

namespace App\Http\Requests\Api\V1\Guard;

use App\Models\Tenant\Visitor;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Walk-in visitor registration from the gate app — unlike
 * Resident\StoreVisitorInviteRequest (a resident pre-approving someone
 * expected later), this is the guard logging someone who has physically
 * arrived right now, so there's no expected_at and the visitor lands
 * already checked_in (see Api\V1\Guard\VisitorController::store). `photo`
 * is optional and, on the app side, camera-only (no gallery picker) so it
 * can only ever be a photo of the person actually at the gate right now.
 */
class StoreGuardVisitorRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
