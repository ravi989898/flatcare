<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafeUploadedFile;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLogoRequest extends FormRequest
{
    private const ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'svg', 'webp'];

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
            'logo' => [
                'required',
                'image',
                'max:2048',
                'mimes:'.implode(',', self::ALLOWED_EXTENSIONS),
                new SafeUploadedFile(self::ALLOWED_EXTENSIONS),
            ],
        ];
    }
}
