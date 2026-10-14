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
