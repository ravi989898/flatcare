<?php

namespace App\Http\Requests\Api\V1\Resident;

use App\Models\Tenant\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequestRequest extends FormRequest
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
            'flat_id' => ['required', 'exists:flats,id'],
            'category' => ['required', 'in:'.implode(',', MaintenanceRequest::CATEGORIES)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'in:'.implode(',', MaintenanceRequest::PRIORITIES)],
        ];
    }
}
