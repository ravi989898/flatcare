<?php

namespace App\Http\Requests\Api\V1\Resident;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Deliberately excludes email/password/status — those aren't self-service:
 * email doubles as the login identifier the society-scan login depends on,
 * password has its own reset flow, and status is admin-controlled.
 */
class UpdateProfileRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'gender' => ['sometimes', 'nullable', 'string', 'in:male,female,other'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }
}
