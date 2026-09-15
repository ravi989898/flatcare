<?php

namespace App\Http\Requests\Society;

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
            'block_id' => ['nullable', 'exists:blocks,id'],
            'flat_id' => ['nullable', 'exists:flats,id'],
            'category' => ['required', 'in:'.implode(',', MaintenanceRequest::CATEGORIES)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'in:'.implode(',', MaintenanceRequest::PRIORITIES)],
            'raised_by_name' => ['required', 'string', 'max:255'],
            'raised_by_phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
