<?php

namespace App\Rules;

use App\Support\SecurityLog;
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
 *
 * Checks, in order — the client-supplied filename and Content-Type are never
 * trusted:
 *   1. the extension is on the caller's allow-list;
 *   2. the content sniffed from the bytes (finfo) matches that extension;
 *   3. images really decode as images (getimagesize) and are not absurdly
 *      large (decompression-bomb guard);
 *   4. PDFs start with the %PDF- signature;
 *   5. the whole file (not just the head) contains no executable/script
 *      signature — this catches a PHP payload appended after valid image
 *      bytes (a "polyglot"), which a head-only check would miss.
 * Every rejection is written to the security log.
 */
class SafeUploadedFile implements ValidationRule
{
    /**
     * Magic-byte / leading-text signatures that must never be accepted,
     * whatever extension or MIME type the upload claims. Matched
     * case-insensitively anywhere in the file.
     *
     * @var array<int, string>
     */
    private const DANGEROUS_SIGNATURES = [
        '<?php',
        '<?=',
        '<script',
        '<%',           // ASP/JSP script tags
        'javascript:',
        '<iframe',
        '<object',
        '<embed',
    ];

    /** Binary headers that mark an executable, checked at offset 0 only. */
    private const EXECUTABLE_MAGIC = [
        "MZ",           // Windows PE executable (.exe, .dll, .scr, ...)
        "\x7fELF",      // Linux/ELF executable
        "#!",           // shebang script
    ];

    /** Longest allowed side of an uploaded raster image, in pixels. */
    private const MAX_IMAGE_SIDE = 8000;

    /** Upper bound on how much of a file is scanned for script signatures. */
    private const MAX_SCAN_BYTES = 12 * 1024 * 1024;

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
            $this->reject($fail, $attribute, 'extension_not_allowed', "The :attribute must be a file of type: {$this->extensionList()}.", $extension);

            return;
        }

        $realPath = $value->getRealPath();

        if ($realPath === false || ! is_readable($realPath)) {
            $fail('The :attribute could not be read.');

            return;
        }

        $expectedMimes = $this->expectedMimeTypesFor($extension);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMime = $finfo ? (string) (finfo_file($finfo, $realPath) ?: '') : '';

        if ($finfo) {
            finfo_close($finfo);
        }

        if ($expectedMimes !== [] && ! in_array($actualMime, $expectedMimes, true)) {
            $this->reject($fail, $attribute, 'mime_mismatch', 'The :attribute does not match its file extension.', $extension, $actualMime);

            return;
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $info = @getimagesize($realPath);

            if ($info === false || $info[0] < 1 || $info[1] < 1) {
                $this->reject($fail, $attribute, 'not_a_valid_image', 'The :attribute is not a valid image.', $extension, $actualMime);

                return;
            }

            if ($info[0] > self::MAX_IMAGE_SIDE || $info[1] > self::MAX_IMAGE_SIDE) {
                $this->reject($fail, $attribute, 'image_too_large', 'The :attribute has too many pixels; please use a smaller image.', $extension, $actualMime);

                return;
            }
        }

        $head = (string) file_get_contents($realPath, false, null, 0, 8);

        if ($extension === 'pdf' && ! str_starts_with($head, '%PDF-')) {
            $this->reject($fail, $attribute, 'not_a_pdf', 'The :attribute is not a valid PDF.', $extension, $actualMime);

            return;
        }

        foreach (self::EXECUTABLE_MAGIC as $magic) {
            if (str_starts_with($head, $magic)) {
                $this->reject($fail, $attribute, 'executable_signature', 'The :attribute contains content that is not allowed.', $extension, $actualMime);

                return;
            }
        }

        // Scan the file body, not only the head: a PHP/JS payload appended
        // after valid image or PDF bytes is invisible to a head-only check.
        // Office/zip formats are compressed, so signatures don't appear as
        // plain text there — their type is enforced by the finfo check above.
        $body = (string) file_get_contents($realPath, false, null, 0, self::MAX_SCAN_BYTES);

        foreach (self::DANGEROUS_SIGNATURES as $signature) {
            if (stripos($body, $signature) !== false) {
                $this->reject($fail, $attribute, 'script_signature', 'The :attribute contains content that is not allowed.', $extension, $actualMime);

                return;
            }
        }
    }

    private function reject(Closure $fail, string $attribute, string $reason, string $message, string $extension, string $sniffedMime = ''): void
    {
        SecurityLog::warning('upload.blocked', [
            'field' => $attribute,
            'reason' => $reason,
            'extension' => mb_substr($extension, 0, 10),
            'sniffed_mime' => $sniffedMime,
        ]);

        $fail($message);
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
