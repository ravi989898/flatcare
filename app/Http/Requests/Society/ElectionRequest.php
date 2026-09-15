<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by Society\ElectionController::store() and ::update() — the rules
 * are identical for both actions.
 */
class ElectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        // Blank optional inputs arrive as "" rather than absent; normalize
        // so nullable rules don't reject an intentionally empty field.
        $this->merge(collect($this->only([
            'description', 'nomination_start_at', 'nomination_end_at', 'voting_start_at', 'voting_end_at',
        ]))
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'nomination_start_at' => ['nullable', 'date'],
            'nomination_end_at' => ['nullable', 'date', 'after_or_equal:nomination_start_at'],
            'voting_start_at' => ['nullable', 'date'],
            'voting_end_at' => ['nullable', 'date', 'after_or_equal:voting_start_at'],
        ];
    }
}
