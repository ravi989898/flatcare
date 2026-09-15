<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by Society\ServiceProviderController::store() and ::update() — the
 * rules are identical for both actions. `phone` stays a general string (not
 * IndianMobileNumber): service providers (plumbers, electricians, etc.)
 * aren't necessarily using a 10-digit Indian mobile number.
 */
class ServiceProviderRequest extends FormRequest
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
            'service_type' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
