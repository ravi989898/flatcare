<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                // Note: Password::uncompromised() (HaveIBeenPwned breach check) is intentionally
                // NOT used here — it makes an outbound HTTPS call on every registration, and this
                // environment's PHP/cURL has no CA bundle configured (curl.cainfo unset in php.ini),
                // so every signup would hard-fail with an SSL error. Enable it once curl.cainfo
                // points at a valid cacert.pem.
            ],
        ];
    }
}
