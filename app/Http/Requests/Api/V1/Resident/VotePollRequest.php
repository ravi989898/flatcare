<?php

namespace App\Http\Requests\Api\V1\Resident;

use Illuminate\Foundation\Http\FormRequest;

class VotePollRequest extends FormRequest
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
            'poll_option_id' => ['required', 'integer', 'exists:poll_options,id'],
        ];
    }
}
