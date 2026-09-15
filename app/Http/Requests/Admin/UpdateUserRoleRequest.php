<?php

namespace App\Http\Requests\Admin;

use App\Services\TenantService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * `role_id` is validated with `exists:society.roles,id`, which only checks
 * the right table if the `society` connection has already been switched to
 * this society's tenant database — the controller previously did that
 * itself before calling `$request->validate()` inline. A FormRequest's
 * rules() runs during dependency resolution, before the controller method
 * body executes, so the switch has to happen here instead, in
 * prepareForValidation(), using the same {id} route segment the controller
 * receives as $societyId.
 */
class UpdateUserRoleRequest extends FormRequest
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
            'role_id' => ['nullable', 'exists:society.roles,id'],
        ];
    }
}
