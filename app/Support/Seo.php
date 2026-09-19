<?php

namespace App\Support;

/**
 * Builds everything an SEO-friendly public page needs from config/seo.php:
 * canonical URLs, Open Graph / Twitter data and JSON-LD structured data.
 *
 * Canonical URLs are ALWAYS built from the configured site origin
 * (https://flatcare.in) and the page's own path - never from the current
 * request - so http/www variants, trailing slashes, ?utm= parameters or a
 * staging host can never become a duplicate canonical.
 */
class Seo
{
    public static function site(): string
    {
        return rtrim((string) config('seo.url'), '/');
    }

    public static function url(string $path = '/'): string
    {
        $path = '/'.ltrim($path, '/');

        return $path === '/' ? self::site().'/' : self::site().rtrim($path, '/');
    }

    /** @return array<string, mixed> */
    public static function pages(): array
    {
        return (array) config('seo.pages', []);
    }

    /** @return array<string, mixed> */
    public static function pageConfig(string $key): array
    {
        return self::pages()[$key] ?? abort(404);
    }

    /**
     * Head data for a page: title, description, canonical, social tags and
     * the JSON-LD graph. $extra can override any key (e.g. 'robots').
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function forPage(string $key, array $extra = []): array
    {
        $page = self::pageConfig($key);
        $url = self::url($page['path']);

        $graph = [self::organization(), self::webPage($page, $url)];

        if ($key === 'home') {
            $graph[] = self::website();
            $graph[] = self::software();
        } else {
            $graph[] = self::breadcrumbs($page, $url);
        }

        if (! empty($page['faqs'])) {
            $graph[] = self::faq($page['faqs'], $url);
        }

        return array_merge([
            'title' => $page['title'],
            'description' => $page['description'],
            'canonical' => $url,
            'robots' => 'index, follow, max-image-preview:large, max-snippet:-1',
            'og_type' => 'website',
            'og_image' => self::url(config('seo.og_image')),
            'og_image_width' => config('seo.og_image_width'),
            'og_image_height' => config('seo.og_image_height'),
            'og_image_alt' => 'FlatCare apartment and society management software',
            'site_name' => config('seo.brand'),
            'schema' => ['@context' => 'https://schema.org', '@graph' => $graph],
        ], $extra);
    }

    /** @return array<string, mixed> */
    public static function organization(): array
    {
        $contact = config('seo.contact');

        return [
            '@type' => 'Organization',
            '@id' => self::site().'/#organization',
            'name' => config('seo.brand'),
            'alternateName' => config('seo.brand_alternates'),
            'url' => self::url('/'),
            'logo' => [
                '@type' => 'ImageObject',
                '@id' => self::site().'/#logo',
                'url' => self::url(config('seo.logo')),
                'width' => 192,
                'height' => 192,
                'caption' => 'FlatCare logo',
            ],
            'description' => 'FlatCare is apartment and society management software for housing societies, apartments and residential communities in India.',
            'areaServed' => ['@type' => 'Country', 'name' => 'India'],
            'contactPoint' => [[
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => $contact['email'],
                'telephone' => preg_replace('/\s+/', '', $contact['phones'][0]),
                'areaServed' => 'IN',
                'availableLanguage' => ['English'],
            ]],
        ];
    }

    /** @return array<string, mixed> */
    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::site().'/#website',
            'url' => self::url('/'),
            'name' => config('seo.brand'),
            'alternateName' => ['Flat Care', 'FlatCare Society Management'],
            'description' => config('seo.tagline'),
            'inLanguage' => 'en-IN',
            'publisher' => ['@id' => self::site().'/#organization'],
        ];
    }

    /** @return array<string, mixed> */
    public static function software(): array
    {
        return [
            '@type' => 'SoftwareApplication',
            '@id' => self::site().'/#software',
            'name' => config('seo.brand'),
            'alternateName' => ['Flat Care', 'FlatCare App', 'FlatCare Society Management'],
            'url' => self::url('/'),
            'applicationCategory' => 'BusinessApplication',
            'applicationSubCategory' => 'Apartment and society management software',
            'operatingSystem' => 'Web, Android',
            'inLanguage' => 'en-IN',
            'description' => 'FlatCare is apartment and society management software for maintenance billing, payments, residents, visitors, complaints, announcements and committee tools.',
            'featureList' => [
                'Maintenance billing and fee collection',
                'Online payments and receipts',
                'Resident and flat management',
                'Visitor and gate management',
                'Complaint management',
                'Announcements, events and polls',
                'Role-based admin access',
            ],
            'downloadUrl' => self::site().'/downloads/flatcare-app.apk',
            'publisher' => ['@id' => self::site().'/#organization'],
            'provider' => ['@id' => self::site().'/#organization'],
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    public static function webPage(array $page, string $url): array
    {
        return [
            '@type' => 'WebPage',
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => $page['title'],
            'description' => $page['description'],
            'inLanguage' => 'en-IN',
            'isPartOf' => ['@id' => self::site().'/#website'],
            'about' => ['@id' => self::site().'/#organization'],
            'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => self::url(config('seo.og_image'))],
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $page, string $url): array
    {
        return [
            '@type' => 'BreadcrumbList',
            '@id' => $url.'#breadcrumb',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => self::url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $page['name'], 'item' => $url],
            ],
        ];
    }

    /**
     * Only ever built from FAQs that are also rendered on the page.
     *
     * @param  array<int, array{0: string, 1: string}>  $faqs
     * @return array<string, mixed>
     */
    public static function faq(array $faqs, string $url): array
    {
        return [
            '@type' => 'FAQPage',
            '@id' => $url.'#faq',
            'mainEntity' => array_map(fn (array $faq) => [
                '@type' => 'Question',
                'name' => $faq[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => html_entity_decode(strip_tags(self::linkify($faq[1])), ENT_QUOTES)],
            ], $faqs),
        ];
    }

    /**
     * Escapes text and re-enables ONLY simple internal links written in
     * config as <a href="/some-page">label</a>. Anything else stays escaped.
     */
    public static function linkify(string $text): string
    {
        $escaped = e($text);

        return preg_replace_callback(
            '#&lt;a href=&quot;(/[a-z0-9/-]*)&quot;&gt;([^<&]{1,80})&lt;/a&gt;#i',
            fn (array $m) => '<a href="'.e($m[1]).'">'.e(html_entity_decode($m[2])).'</a>',
            $escaped
        ) ?? $escaped;
    }

    /** Sitemap entries: every public page, canonical URL only. @return array<int, array<string, string>> */
    public static function sitemapEntries(): array
    {
        $entries = [];

        foreach (self::pages() as $key => $page) {
            $entries[] = [
                'loc' => self::url($page['path']),
                'lastmod' => config('seo.lastmod'),
                'changefreq' => $key === 'home' ? 'weekly' : 'monthly',
                'priority' => $page['priority'] ?? (in_array($key, ['features', 'pricing', 'contact', 'about'], true) ? '0.7' : '0.8'),
            ];
        }

        return $entries;
    }
}
