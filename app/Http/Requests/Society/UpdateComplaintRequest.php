<?php

namespace App\Http\Requests\Society;

use App\Models\Tenant\Complaint;
use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintRequest extends FormRequest
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
            'status' => ['required', 'in:'.implode(',', Complaint::STATUSES)],
            'priority' => ['required', 'in:'.implode(',', Complaint::PRIORITIES)],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
