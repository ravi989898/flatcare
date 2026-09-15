<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Society\DirectoryController::store() — onboards a new resident. The
 * `phone` field here is kept as a general string (not IndianMobileNumber):
 * it was previously `string|max:20` with no digit-count constraint, unlike
 * flats.mobile_number which is specifically the OTP-login identifier.
 */
class StoreResidentRequest extends FormRequest
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
            'resident_type' => ['required', 'in:owner,tenant,occupant'],
            'is_primary' => ['boolean'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
        ];
    }
}
