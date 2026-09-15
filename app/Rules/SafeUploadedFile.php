<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Defense-in-depth on top of Laravel's own `mimes:`/`image` rules, since
 * neither of those look past the file's declared extension/MIME far enough
 * to catch a renamed executable or an HTML/script payload smuggled in under
 * an allowed extension (see ARCHITECTURE.md §12.2 "All uploads validated").
 * No AV daemon (ClamAV) is available on this box, so this is the practical
 * best-effort substitute: verify the real on-disk content type via finfo
 * rather than the client-supplied one, and reject known executable/script
 * signatures outright regardless of what extension the file claims to be.
 */
class SafeUploadedFile implements ValidationRule
{
    /**
     * Magic-byte / leading-text signatures that must never be accepted,
     * whatever extension or MIME type the upload claims.
     *
     * @var array<int, string>
     */
    private const DANGEROUS_SIGNATURES = [
        "MZ",           // Windows PE executable (.exe, .dll, .scr, ...)
        "\x7fELF",      // Linux/ELF executable
        "#!",           // shebang script
        '<?php',
        '<%',           // ASP/JSP script tags
        '<script',
    ];

    /**
     * @param  array<int, string>  $allowedExtensions  lowercase, no leading dot
     */
    public function __construct(private readonly array $allowedExtensions) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (! in_array($extension, $this->allowedExtensions, true)) {
            $fail("The :attribute must be a file of type: {$this->extensionList()}.");

            return;
        }

        $realPath = $value->getRealPath();

        if ($realPath === false || ! is_readable($realPath)) {
            $fail('The :attribute could not be read.');

            return;
        }

        $expectedMimes = $this->expectedMimeTypesFor($extension);
        $actualMime = (string) (finfo_file(finfo_open(FILEINFO_MIME_TYPE), $realPath) ?: '');

        if ($expectedMimes !== [] && ! in_array($actualMime, $expectedMimes, true)) {
            $fail('The :attribute does not match its file extension.');

            return;
        }

        $head = file_get_contents($realPath, false, null, 0, 4096) ?: '';

        foreach (self::DANGEROUS_SIGNATURES as $signature) {
            if (str_starts_with($head, $signature) || stripos($head, $signature) !== false) {
                $fail('The :attribute contains content that is not allowed.');

                return;
            }
        }
    }

    private function extensionList(): string
    {
        return implode(', ', $this->allowedExtensions);
    }

    /**
     * @return array<int, string>
     */
    private function expectedMimeTypesFor(string $extension): array
    {
        return match ($extension) {
            'jpg', 'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml', 'text/plain', 'text/html', 'text/xml', 'application/xml'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            default => [],
        };
    }
}
