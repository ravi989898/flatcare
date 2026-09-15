<?php

namespace App\Http\Requests;

use App\Rules\IndianMobileNumber;
use App\Rules\SafeUploadedFile;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared between Admin\SocietySecurityController (Super Admin, cross-tenant)
 * and Society\SecurityGuardController (a society's own admin) — both accept
 * exactly the same fields for the same SecurityGuard model, just scoped to
 * a different tenant connection by the controller before this runs.
 */
class SecurityGuardRequest extends FormRequest
{
    private const ALLOWED_PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

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
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'digits:10', new IndianMobileNumber],
            'shift' => ['required', 'in:day,night'],
            'aadhar_last4' => ['nullable', 'digits:4'],
            'photo' => [
                'nullable',
                'image',
                'max:2048',
                'mimes:'.implode(',', self::ALLOWED_PHOTO_EXTENSIONS),
                new SafeUploadedFile(self::ALLOWED_PHOTO_EXTENSIONS),
            ],
        ];
    }
}
