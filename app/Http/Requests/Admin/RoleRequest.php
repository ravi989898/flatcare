<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
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
        $roleId = $this->route('id');

        return [
            'name' => [
                'required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/',
                'unique:main.role_definitions,name'.($roleId ? ",{$roleId}" : ''),
            ],
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'priority' => ['required', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
