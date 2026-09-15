<?php

namespace App\Http\Requests\Society;

use App\Models\Tenant\Flat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Society\PaymentController::store() — raises a bill for one flat, or every
 * active flat at once when "all" is chosen.
 */
class StoreBillRequest extends FormRequest
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
            'flat_id' => ['required', Rule::in(array_merge(['all'], Flat::pluck('id')->all()))],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
