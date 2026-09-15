<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by Society\EmergencyContactController::store() and ::update() —
 * the rules are identical for both actions. `phone` stays a general string
 * (not IndianMobileNumber): these are things like police/fire/ambulance
 * lines, not necessarily 10-digit Indian mobile numbers.
 */
class EmergencyContactRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'type' => ['required', 'string', 'max:50'],
            'availability' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
