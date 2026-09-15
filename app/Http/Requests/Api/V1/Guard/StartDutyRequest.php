<?php

namespace App\Http\Requests\Api\V1\Guard;

use Illuminate\Foundation\Http\FormRequest;

class StartDutyRequest extends FormRequest
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
            'shift' => ['required', 'in:day,night'],
        ];
    }
}
