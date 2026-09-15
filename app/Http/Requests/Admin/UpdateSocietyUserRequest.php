<?php

namespace App\Http\Requests\Admin;

use App\Services\TenantService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Admin\SocietyUserController::update(). See StoreSocietyAdminRequest's
 * docblock for why the tenant switch happens in prepareForValidation()
 * rather than the controller body — the `unique:society.users,...`/
 * `exists:flats,id` rules below need it done first.
 */
class UpdateSocietyUserRequest extends FormRequest
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
        $userId = $this->route('userId');

        return [
            'flat_id' => ['required', 'exists:flats,id'],
            'resident_type' => ['required', 'in:owner,tenant,occupant'],
            'is_primary' => ['boolean'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', "unique:society.users,email,{$userId}"],
            'phone' => ['required', 'string', 'max:20', "unique:society.users,phone,{$userId}"],
        ];
    }
}
