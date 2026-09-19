<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;

/**
 * Stored-XSS defence at the door. Every free-text value that comes in
 * (visitor name, purpose/notes, complaint text, announcements, addresses,
 * profile fields, search boxes ...) has HTML removed *before* it reaches
 * validation or the database, so an `<img onerror=…>`/`<script>` payload is
 * never stored in the first place. Output is additionally HTML-escaped by
 * Blade and rendered as plain text by the Flutter app, so this is
 * defence in depth, not the only barrier.
 *
 * Deliberately conservative so legitimate text is untouched: only *real, closed* tags
 * (`<` + optional `/` + a letter, ending in `>`) are removed, so
 * "5 < 6", "a<3" or "x<y and z" survive. Passwords, OTPs and tokens are never
 * altered. Nothing here needs the application to support rich text — no view
 * renders user data as HTML.
 */
class SanitizeHtmlInput extends TransformsRequest
{
    /** Keys whose values are credentials/opaque tokens and must reach the app byte-for-byte. */
    private const EXEMPT_KEY_PATTERN = '/pass|token|otp|secret|signature|_key$/i';

    protected function transform($key, $value)
    {
        if (! is_string($value) || $value === '' || preg_match(self::EXEMPT_KEY_PATTERN, (string) $key)) {
            return $value;
        }

        return self::sanitize($value);
    }

    public static function sanitize(string $value): string
    {
        // NUL bytes are never legitimate in text and are used to split payloads.
        $value = str_replace("\0", '', $value);

        // Whole executable blocks, contents included (`<script>alert(1)</script>`).
        $value = preg_replace('#<\s*(script|style|iframe|object|embed|svg|math)\b.*?<\s*/\s*\1\s*>#is', '', $value) ?? $value;

        // Comments, processing instructions and CDATA.
        $value = preg_replace('#<!--.*?-->|<\?.*?\?>|<!\[CDATA\[.*?\]\]>#s', '', $value) ?? $value;

        // Any remaining real tag, opening, closing or self-closing.
        $value = preg_replace('#</?\s*[a-z!][^<>]*>#i', '', $value) ?? $value;

        // A value that is *only* a script URL (e.g. a "website" field).
        if (preg_match('#^\s*(javascript|vbscript|data)\s*:#i', $value) === 1) {
            return '';
        }

        return $value;
    }
}
