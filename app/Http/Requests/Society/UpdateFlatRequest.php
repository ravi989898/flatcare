<?php

namespace App\Http\Requests\Society;

use App\Rules\IndianMobileNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Society\BlockController::flatsUpdate(). mobile_number gets the
 * IndianMobileNumber format check because flats.mobile_number is the
 * OTP-login identifier used by Api\V1\Auth\OtpAuthController — see that
 * rule's docblock.
 */
class UpdateFlatRequest extends FormRequest
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
        $flatId = $this->route('flatId');

        return [
            'flat_number' => [
                'required', 'string', 'max:255',
                'unique:society.flats,flat_number'.($flatId ? ",{$flatId}" : ''),
            ],
            'mobile_number' => [
                'nullable', 'digits:10', new IndianMobileNumber(),
                'unique:society.flats,mobile_number'.($flatId ? ",{$flatId}" : ''),
            ],
        ];
    }
}
