<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            // super_admin is a platform role: a society admin must not be able to
            // grant it by posting its id (the form just hides it from the list).
            'role' => ['required', Rule::exists('society.roles', 'id')->where(fn ($q) => $q->where('name', '!=', 'super_admin'))],
        ];
    }
}
