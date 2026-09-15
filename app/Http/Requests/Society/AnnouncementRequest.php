<?php

namespace App\Http\Requests\Society;

use App\Models\Tenant\Announcement;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by Society\AnnouncementController::store() and ::update() — the
 * rules are identical for both actions.
 */
class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        // A blank date input arrives as "" rather than absent; normalize so
        // the nullable|date rule doesn't reject an intentionally empty field.
        if ($this->input('expires_at') === '') {
            $this->merge(['expires_at' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'category' => ['required', 'in:'.implode(',', Announcement::CATEGORIES)],
            'is_pinned' => ['boolean'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
