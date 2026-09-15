<?php

namespace App\Http\Requests\Admin;

use App\Services\TenantService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Admin\SocietyUserController::store() — Super Admin onboarding a resident
 * into a society's directory, mirroring Society\DirectoryController::store()
 * (the same action a Society Admin takes for their own society). The
 * `unique:society.users,...`/`exists:flats,id` rules only check the right
 * tenant database if the `society` connection has already been switched —
 * done here in prepareForValidation() since FormRequest rules() runs before
 * the controller body (see StoreSocietyAdminRequest's docblock).
 */
class StoreSocietyUserRequest extends FormRequest
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
        return [
            'flat_id' => ['required', 'exists:flats,id'],
            'resident_type' => ['required', 'in:owner,tenant,occupant'],
            'is_primary' => ['boolean'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:society.users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:society.users,phone'],
        ];
    }
}
