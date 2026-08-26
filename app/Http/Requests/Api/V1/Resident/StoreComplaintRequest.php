<?php

namespace App\Http\Requests\Api\V1\Resident;

use App\Models\Tenant\Complaint;
use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
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
            'category' => ['required', 'in:'.implode(',', Complaint::CATEGORIES)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'against' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'in:'.implode(',', Complaint::PRIORITIES)],
        ];
    }
}
