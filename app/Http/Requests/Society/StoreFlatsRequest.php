<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Society\BlockController::flatsStore() — bulk-creates one or many flats at
 * once from a newline/comma-separated textarea of flat numbers. Parsing and
 * per-number validation (uniqueness, restore-vs-create) happens in the
 * controller after this, since it depends on splitting the raw text first.
 */
class StoreFlatsRequest extends FormRequest
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
            'flat_numbers' => ['required', 'string'],
        ];
    }
}
