<?php

namespace App\Http\Requests\Admin;

use App\Models\SocietyDatabase;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by SocietyController::store() and ::update() — the only
 * difference is that update() must exclude the society's own row from the
 * email `unique` check (via the {id} route parameter) and may leave
 * db_password blank when credentials are already on file.
 */
class SocietyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        // Blank optional text inputs arrive as "" rather than absent; normalize
        // them to null so nullable numeric/date columns don't receive "".
        $this->merge(collect($this->only([
            'registration_number', 'total_flats', 'total_blocks', 'fixed_maintenance',
            'water_unit_rate', 'admin_name', 'admin_email', 'admin_phone', 'alternate_phone', 'description',
        ]))
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $societyId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'email' => [
                'required', 'email',
                'unique:main.societies,email'.($societyId ? ",{$societyId}" : ''),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'total_flats' => ['nullable', 'integer', 'min:0'],
            'total_blocks' => ['nullable', 'integer', 'min:0'],
            'fixed_maintenance' => ['nullable', 'numeric', 'min:0'],
            'water_unit_rate' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:active,inactive,expired,archived'],
            'is_trial' => ['boolean'],
            'payment_verified' => ['boolean'],
            'admin_name' => ['nullable', 'string', 'max:255'],
            'admin_email' => ['nullable', 'email', 'max:255'],
            'admin_phone' => ['nullable', 'string', 'max:20'],
            'db_name' => ['nullable', 'string', 'max:255'],
            'db_user' => ['nullable', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $dbName = trim((string) $this->input('db_name'));
            $dbUser = trim((string) $this->input('db_user'));
            $dbPassword = trim((string) $this->input('db_password'));

            if ($dbName === '' && $dbUser === '' && $dbPassword === '') {
                return;
            }

            $societyId = $this->route('id');
            $hasExistingDatabase = $societyId
                ? SocietyDatabase::where('society_id', $societyId)->exists()
                : false;

            if ($dbName === '' || $dbUser === '' || ($dbPassword === '' && ! $hasExistingDatabase)) {
                $validator->errors()->add(
                    'db_name',
                    $societyId
                        ? 'Database Name and User are required (Password too, unless one is already on file).'
                        : 'Database Name, User, and Password must all be provided together.'
                );
            }
        });
    }

    /**
     * The manual db_name/db_user/db_password fields (used when the hosting
     * environment doesn't let the app CREATE DATABASE itself, so the super
     * admin creates the tenant database by hand and pastes its credentials
     * in here instead). Returns null when none of the three were provided
     * (the normal auto-provisioning path) — withValidator() above already
     * guarantees that if any is non-blank, all required ones are present.
     *
     * @return array{db_name: string, db_user: string, db_password: ?string}|null
     */
    public function manualDbFields(): ?array
    {
        $dbName = trim((string) $this->input('db_name'));
        $dbUser = trim((string) $this->input('db_user'));
        $dbPassword = trim((string) $this->input('db_password'));

        if ($dbName === '' && $dbUser === '' && $dbPassword === '') {
            return null;
        }

        return [
            'db_name' => $dbName,
            'db_user' => $dbUser,
            'db_password' => $dbPassword === '' ? null : $dbPassword,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function societyFields(): array
    {
        return $this->safe()->except(['db_name', 'db_user', 'db_password']);
    }
}
