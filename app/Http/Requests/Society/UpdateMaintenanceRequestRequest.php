<?php

namespace App\Http\Requests\Society;

use App\Models\Tenant\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceRequestRequest extends FormRequest
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
            'status' => ['required', 'in:'.implode(',', MaintenanceRequest::STATUSES)],
            'priority' => ['required', 'in:'.implode(',', MaintenanceRequest::PRIORITIES)],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
