<?php

namespace App\Http\Requests\Admin;

use App\Services\TenantService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The `unique:society.users,...`/`exists:society.roles,id` rules below only
 * check the right tenant database if the `society` connection has already
 * been switched to this society — the controller used to do that itself
 * before calling `$request->validate()` inline. FormRequest rules() runs
 * before the controller body, so the switch has to happen here instead.
 */
class StoreSocietyAdminRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:society.users,email'],
            'phone' => ['required', 'digits:10', 'unique:society.users,phone'],
            'password' => ['required', 'min:10', 'confirmed'],
            'role' => ['required', 'exists:society.roles,id'],
        ];
    }
}
