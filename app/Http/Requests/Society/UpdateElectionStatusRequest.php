<?php

namespace App\Http\Requests\Society;

use App\Models\Tenant\Election;
use Illuminate\Foundation\Http\FormRequest;

class UpdateElectionStatusRequest extends FormRequest
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
            'status' => ['required', 'in:'.implode(',', Election::STATUSES)],
        ];
    }
}
