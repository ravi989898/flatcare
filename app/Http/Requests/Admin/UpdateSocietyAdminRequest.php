<?php

namespace App\Http\Requests\Admin;

use App\Services\TenantService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * See StoreSocietyAdminRequest's docblock for why the tenant switch happens
 * here rather than in the controller body.
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
            'password' => ['nullable', 'min:10', 'confirmed'],
            'role' => ['required', 'exists:society.roles,id'],
        ];
    }
}
