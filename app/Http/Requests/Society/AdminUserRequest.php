<?php

namespace App\Http\Requests\Society;

use App\Rules\IndianMobileNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by Society\AdminController::store() and ::update() — the only
 * difference is that update() must exclude the admin's own row from the
 * email/phone `unique` checks (via the {adminId} route parameter) and may
 * leave the password blank to keep the existing one.
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
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

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
            'password' => [$isUpdate ? 'nullable' : 'required', 'min:10', 'confirmed'],
            'role' => ['required', 'exists:society.roles,id'],
        ];
    }
}
