<?php

namespace App\Http\Requests\Api\V1\Resident;

use App\Models\Tenant\DailyHelper;
use App\Rules\SafeUploadedFile;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "Add helper" on the Daily Helper screen. flat_id ownership is re-checked
 * in the controller against myFlatIds(), like the other resident stores.
 */
class StoreDailyHelperRequest extends FormRequest
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
            'flat_id' => ['required', 'exists:flats,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'helper_type' => ['required', 'in:'.implode(',', DailyHelper::TYPES)],
            'photo' => [
                'nullable',
                'image',
                'max:4096',
                'mimes:jpg,jpeg,png,webp',
                new SafeUploadedFile(['jpg', 'jpeg', 'png', 'webp']),
            ],
        ];
    }
}
