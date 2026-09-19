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
    /**
     * Elements that can run script, embed foreign content, or (SMIL
     * animate/set) rewrite an href to a javascript: URL after the fact.
     */
    private const BLOCKED_ELEMENTS = ['script', 'foreignObject', 'iframe', 'object', 'embed', 'animate', 'set', 'handler', 'listener'];

    public static function sanitize(string $svgContents): string
    {
        // A DOCTYPE/ENTITY declaration has no place in a logo, and is the
        // vector for XXE (reading a local file into the stored SVG) and
        // "billion laughs" expansion attacks — refuse it outright.
        if (preg_match('/<!(DOCTYPE|ENTITY)/i', $svgContents) === 1) {
            return '';
        }

        $previous = libxml_use_internal_errors(true);

        $document = new \DOMDocument;
        // No LIBXML_NOENT: never substitute entities. LIBXML_NONET blocks any
        // network fetch while parsing.
        $loaded = $document->loadXML($svgContents, LIBXML_NONET);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            // Not parseable as XML at all — treat as unsafe rather than store as-is.
            return '';
        }

        $xpath = new \DOMXPath($document);

        $condition = implode(' or ', array_map(
            fn (string $tag) => 'local-name()="'.$tag.'"',
            self::BLOCKED_ELEMENTS
        ));

        foreach (iterator_to_array($xpath->query('//*['.$condition.']') ?: []) as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach (iterator_to_array($xpath->query('//@*') ?: []) as $attribute) {
            /** @var \DOMAttr $attribute */
            $name = strtolower($attribute->nodeName);
            $value = trim($attribute->nodeValue ?? '');

            $isEventHandler = str_starts_with($name, 'on');
            $isScriptUri = in_array($name, ['href', 'xlink:href', 'src'], true)
                && preg_match('/^[\s\x00-\x20]*(javascript|data|vbscript):/i', $value) === 1;
            // CSS can smuggle a script/URL via url(...) or expression(...).
            $isDangerousStyle = $name === 'style'
                && preg_match('/expression\s*\(|javascript:|url\s*\(\s*[\'"]?\s*(javascript|data):/i', $value) === 1;

            if ($isEventHandler || $isScriptUri || $isDangerousStyle) {
                $attribute->ownerElement?->removeAttributeNode($attribute);
            }
        }

        return $document->saveXML() ?: '';
    }
}
