<?php

namespace App\Http\Requests\Society;

use App\Models\Tenant\Event;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by Society\EventController::store() and ::update() — the rules are
 * identical for both actions.
 */
class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        // Blank optional inputs arrive as "" rather than absent; normalize
        // so nullable rules don't reject an intentionally empty field.
        $this->merge(collect($this->only(['location', 'end_at']))
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
            'description' => ['required', 'string'],
            'category' => ['required', 'in:'.implode(',', Event::CATEGORIES)],
            'location' => ['nullable', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
        ];
    }
}
