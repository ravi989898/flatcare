<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by Society\FeeTypeController::store() and ::update() — the rules
 * are identical for both actions.
 */
class FeeTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
