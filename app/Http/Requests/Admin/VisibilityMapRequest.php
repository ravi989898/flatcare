<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by MenuSettingController and DashboardWidgetSettingController —
 * both post the identical shape: {visibility: {role_id: {item_id: "on"}}}.
 */
class VisibilityMapRequest extends FormRequest
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
