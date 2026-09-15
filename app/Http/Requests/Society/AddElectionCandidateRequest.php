<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

class AddElectionCandidateRequest extends FormRequest
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
            'user_id' => ['required', 'exists:users,id'],
            'manifesto' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
