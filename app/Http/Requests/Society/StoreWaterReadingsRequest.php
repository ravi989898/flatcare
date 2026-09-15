<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

class StoreWaterReadingsRequest extends FormRequest
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
            'month' => ['required', 'date_format:Y-m'],
            'block_id' => ['nullable', 'integer', 'exists:blocks,id'],
            'readings' => ['required', 'array'],
            'readings.*.flat_id' => ['required', 'integer', 'exists:flats,id'],
            'readings.*.previous_reading' => ['nullable', 'numeric', 'min:0'],
            'readings.*.current_reading' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
