<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResidentCommitteeRequest extends FormRequest
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
            'committee_position' => ['nullable', 'string', 'max:100'],
            'committee_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
