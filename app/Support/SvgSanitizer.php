<?php

namespace App\Support;

/**
 * Strips script content from an uploaded SVG before it's written to the
 * public disk. SVG is XML, so it can carry an inline `<script>`, an
 * `onload`/`onclick`/... event attribute, or a `javascript:`/`data:` URI in
 * an `href` — any of which executes in the browser of whoever opens the
 * file directly from its public storage URL (stored XSS). Laravel's
 * `image`/`mimes:svg` rules don't look inside the file for this, so it has
 * to be sanitized explicitly wherever `.svg` uploads are accepted (see the
 * platform branding logo).
 */
class SvgSanitizer
{
    public static function sanitize(string $svgContents): string
    {
        $previous = libxml_use_internal_errors(true);

        $document = new \DOMDocument;
        $loaded = $document->loadXML($svgContents, LIBXML_NONET | LIBXML_NOENT);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            // Not parseable as XML at all — treat as unsafe rather than store as-is.
            return '';
        }

        $xpath = new \DOMXPath($document);

        foreach (iterator_to_array($xpath->query('//*[local-name()="script"]') ?: []) as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach (iterator_to_array($xpath->query('//@*') ?: []) as $attribute) {
            /** @var \DOMAttr $attribute */
            $name = strtolower($attribute->nodeName);
            $value = trim($attribute->nodeValue ?? '');

            $isEventHandler = str_starts_with($name, 'on');
            $isScriptUri = ($name === 'href' || $name === 'xlink:href' || $name === 'src')
                && preg_match('/^\s*(javascript|data):/i', $value) === 1;

            if ($isEventHandler || $isScriptUri) {
                $attribute->ownerElement?->removeAttributeNode($attribute);
            }
        }

        return $document->saveXML() ?: '';
    }
}
