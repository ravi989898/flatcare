<?php

namespace App\Http\Requests\Society;

use App\Models\Tenant\Visitor;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Society\VisitorController::store() — visitor check-in. `visitor_phone`
 * stays a general string (not IndianMobileNumber): the gate register just
 * needs a contact number, not necessarily a resident-style mobile.
 */
class StoreVisitorRequest extends FormRequest
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
            'flat_id' => ['required', 'exists:flats,id'],
            'visitor_name' => ['required', 'string', 'max:255'],
            'visitor_phone' => ['nullable', 'string', 'max:20'],
            'purpose' => ['required', 'in:'.implode(',', Visitor::PURPOSES)],
            'vehicle_number' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
