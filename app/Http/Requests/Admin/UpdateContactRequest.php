<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $regex = 'regex:/^\+?[0-9][0-9\s\-]{8,18}$/';

        return [
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone_1' => ['required', 'string', 'max:20', $regex],
            'contact_phone_2' => ['nullable', 'string', 'max:20', $regex],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_phone_1.regex' => 'Enter a valid phone number (digits, optional +91).',
            'contact_phone_2.regex' => 'Enter a valid phone number (digits, optional +91).',
        ];
    }
}
