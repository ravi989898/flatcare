<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Services\Api\OtpService;
use Illuminate\Foundation\Http\FormRequest;

class OtpRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Records store the bare 10 digits, but a phone's autofill or a pasted
     * contact often gives "+91 98765 43210" - match on the 10 digits.
     */
    protected function prepareForValidation(): void
    {
        $mobileNumber = $this->input('mobile_number');

        if (is_string($mobileNumber) && ($normalized = OtpService::normalize($mobileNumber)) !== null) {
            $this->merge(['mobile_number' => $normalized]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mobile_number' => ['required', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail) {
                if (!is_string($value) || OtpService::normalize($value) === null) {
                    $fail('Enter a valid 10-digit mobile number.');
                }
            }],
        ];
    }
}
