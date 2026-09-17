<?php

namespace App\Http\Requests\Admin;

use App\Services\TenantService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * See StoreSocietyAdminRequest's docblock for why the tenant switch happens
 * here rather than in the controller body. Password changes go through the
 * dedicated "Forgot Password" flow (resetPassword()) instead of this form,
 * so there's no password field here.
 */
class UpdateSocietyAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        app(TenantService::class)->switchConnection((int) $this->route('id'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $adminId = $this->route('adminId');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', "unique:society.users,email,{$adminId}"],
            'phone' => ['required', 'digits:10', "unique:society.users,phone,{$adminId}"],
            'role' => ['required', 'exists:society.roles,id'],
        ];
    }
}
