<?php

namespace App\Http\Requests\Society;

use App\Models\Tenant\Document;
use App\Rules\SafeUploadedFile;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Extensions accepted in the society document library — office
     * documents and scanned images only. Previously this field had no
     * `mimes:`/type restriction at all (any file type, 10MB cap only),
     * which is the biggest hole in the app's upload surface: these files
     * land on the public disk and are directly downloadable by URL.
     *
     * @var array<int, string>
     */
    public const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];

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
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:'.implode(',', Document::CATEGORIES)],
            'file' => [
                'required',
                'file',
                'max:10240', // 10 MB
                'mimes:'.implode(',', self::ALLOWED_EXTENSIONS),
                new SafeUploadedFile(self::ALLOWED_EXTENSIONS),
            ],
        ];
    }
}
