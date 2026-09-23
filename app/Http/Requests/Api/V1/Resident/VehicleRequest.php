<?php

namespace App\Http\Requests\Api\V1\Resident;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('registration_number')) {
            $this->merge(['registration_number' => strtoupper(trim((string) $this->input('registration_number')))]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'registration_number.unique' => 'This vehicle number is already registered in the society.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $sometimesOnUpdate = $this->isMethod('put') || $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'vehicle_type' => [$sometimesOnUpdate, 'string', 'max:50'],
            // Unique among live vehicles only — a soft-deleted row with the
            // same number is cleared by VehicleController before saving.
            'registration_number' => [
                $sometimesOnUpdate,
                'string',
                'max:50',
                Rule::unique('society.vehicles', 'registration_number')
                    ->whereNull('deleted_at')
                    ->ignore($this->route('id')),
            ],
            'model' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'parking_slot' => ['nullable', 'string', 'max:50'],
        ];
    }
}
