<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

class ElectionVoteRequest extends FormRequest
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
            'candidate_id' => ['required', 'exists:election_candidates,id'],
            'voter_user_id' => ['required', 'exists:users,id'],
        ];
    }
}
