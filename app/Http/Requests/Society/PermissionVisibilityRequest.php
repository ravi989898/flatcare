<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Posted by Settings -> Permissions: {visibility: {role_id: {item_id: "on"}}},
 * the same shape as Admin\VisibilityMapRequest.
 */
class PermissionVisibilityRequest extends FormRequest
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
            'visibility' => ['array'],
            'visibility.*' => ['array'],
        ];
    }
}
