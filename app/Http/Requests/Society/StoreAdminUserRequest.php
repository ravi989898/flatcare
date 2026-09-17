<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Promotes an existing society user to admin — mirrors
 * Admin\StoreSocietyAdminRequest (the Super Admin's cross-tenant
 * equivalent). Separate from AdminUserRequest, which still handles editing
 * an existing admin's own name/email/phone.
 */
class StoreAdminUserRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:society.users,id'],
            'password' => ['required', 'min:10', 'confirmed'],
            'role' => ['required', 'exists:society.roles,id'],
        ];
    }
}
