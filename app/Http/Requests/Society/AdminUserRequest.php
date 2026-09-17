<?php

namespace App\Http\Requests\Society;

use App\Rules\IndianMobileNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Used by Society\AdminController::update() — password changes go through
 * the dedicated "Forgot Password" flow instead (resetPassword()), not this
 * form, so there's no password field here.
 */
class AdminUserRequest extends FormRequest
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
        $adminId = $this->route('adminId');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email',
                'unique:society.users,email'.($adminId ? ",{$adminId}" : ''),
            ],
            'phone' => [
                'required', 'digits:10', new IndianMobileNumber(),
                'unique:society.users,phone'.($adminId ? ",{$adminId}" : ''),
            ],
            'role' => ['required', 'exists:society.roles,id'],
        ];
    }
}
