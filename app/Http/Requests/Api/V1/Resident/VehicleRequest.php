<?php

namespace App\Http\Requests\Api\V1\Resident;

use Illuminate\Foundation\Http\FormRequest;

class VehicleRequest extends FormRequest
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
        $sometimesOnUpdate = $this->isMethod('put') || $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'vehicle_type' => [$sometimesOnUpdate, 'string', 'max:50'],
            'registration_number' => [$sometimesOnUpdate, 'string', 'max:50'],
            'model' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'parking_slot' => ['nullable', 'string', 'max:50'],
        ];
    }
}
