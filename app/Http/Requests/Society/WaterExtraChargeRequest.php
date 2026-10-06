<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by Society\WaterExtraChargeController::store() and ::update() — the
 * rules are identical for both actions.
 */
class WaterExtraChargeRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
