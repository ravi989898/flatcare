<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

class StoreExtraChargeRequest extends FormRequest
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
            'flat_id' => ['required', 'exists:society.flats,id'],
            'fee_type_id' => ['required', 'exists:society.fee_types,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
