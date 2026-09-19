<?php

namespace Tests\Feature;

use App\Support\Seo;
use Tests\TestCase;

/**
 * Technical-SEO regression tests: every public page is indexable, has a unique
 * title/description, exactly one H1, a correct canonical, valid JSON-LD and is
 * listed in the sitemap. See SEO_AUDIT.md.
 */
class SeoTest extends TestCase
{
    /** @return array<string, string> key => path */
    private function paths(): array
    {
        return collect(config('seo.pages'))->map(fn ($p) => $p['path'])->all();
    }

    public function test_every_public_page_returns_200_and_is_indexable(): void
    {
        foreach ($this->paths() as $key => $path) {
            $response = $this->get($path);

            $response->assertOk();
            $html = $response->getContent();

            $this->assertMatchesRegularExpression('/<meta name="robots" content="index, follow[^"]*">/', $html, "$path must be indexable");
            $this->assertStringNotContainsString('noindex', strtolower((string) $response->headers->get('X-Robots-Tag')), "$path must not send X-Robots-Tag noindex");
            $this->assertStringNotContainsString('name="robots" content="noindex', $html);
        }
    }

    public function test_each_page_has_exactly_one_h1(): void
    {
        foreach ($this->paths() as $path) {
            $html = $this->get($path)->getContent();

            $this->assertSame(1, preg_match_all('/<h1[\s>]/i', $html), "$path must have exactly one <h1>");
        }
    }

    public function test_home_page_has_the_requested_title_h1_and_description(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('<title>FlatCare – Smart Apartment &amp; Society Management Software</title>', $html);
        $this->assertMatchesRegularExpression('#<h1[^>]*>\s*Smart <span class="grad-text">Apartment &amp; Society Management Software</span></h1>#', $html);

        $length = mb_strlen(config('seo.pages.home.description'));
        $this->assertGreaterThanOrEqual(140, $length);
        $this->assertLessThanOrEqual(165, $length);
    }

    public function test_titles_and_descriptions_are_unique_and_a_sensible_length(): void
    {
        $titles = [];
        $descriptions = [];

        foreach (config('seo.pages') as $key => $page) {
            $this->assertLessThanOrEqual(70, mb_strlen($page['title']), "$key title too long");
            $this->assertGreaterThanOrEqual(30, mb_strlen($page['title']), "$key title too short");
            $this->assertGreaterThanOrEqual(120, mb_strlen($page['description']), "$key description too short");
            $this->assertLessThanOrEqual(165, mb_strlen($page['description']), "$key description too long");
            $this->assertStringContainsStringIgnoringCase('flatcare', $page['title'], "$key title should carry the brand");

            $titles[] = $page['title'];
            $descriptions[] = $page['description'];
        }

        $this->assertSame($titles, array_values(array_unique($titles)), 'duplicate titles');
        $this->assertSame($descriptions, array_values(array_unique($descriptions)), 'duplicate descriptions');
    }

    public function test_canonical_is_the_clean_https_non_www_url_even_with_query_strings_and_other_hosts(): void
    {
        foreach ($this->paths() as $key => $path) {
            $expected = $path === '/' ? 'https://flatcare.in/' : 'https://flatcare.in'.$path;

            foreach ([$path, $path.'?utm_source=test&ref=1', 'http://www.flatcare.in'.$path] as $url) {
                $html = $this->get($url)->getContent();

                $this->assertStringContainsString('<link rel="canonical" href="'.$expected.'">', $html, "canonical for $url");
                $this->assertStringContainsString('<meta property="og:url" content="'.$expected.'">', $html);
            }
        }
    }

    public function test_open_graph_and_twitter_tags_are_present(): void
    {
        foreach ($this->paths() as $path) {
            $html = $this->get($path)->getContent();

            foreach (['og:title', 'og:description', 'og:image', 'og:url', 'og:type'] as $tag) {
                $this->assertStringContainsString('property="'.$tag.'"', $html, "$path missing $tag");
            }
            foreach (['twitter:card', 'twitter:title', 'twitter:description', 'twitter:image'] as $tag) {
                $this->assertStringContainsString('name="'.$tag.'"', $html, "$path missing $tag");
            }
            $this->assertStringContainsString('content="https://flatcare.in/images/marketing/flatcare-society-management-app.jpg"', $html);
        }
    }

    public function test_json_ld_is_valid_and_faq_schema_matches_the_visible_faq(): void
    {
        foreach ($this->paths() as $key => $path) {
            $html = $this->get($path)->getContent();

            preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
            $data = json_decode($m[1] ?? '', true);

            $this->assertIsArray($data, "$path JSON-LD must be valid JSON");
            $types = collect($data['@graph'])->pluck('@type')->all();
            $this->assertContains('Organization', $types);
            $this->assertContains('WebPage', $types);

            if ($key === 'home') {
                $this->assertContains('WebSite', $types);
                $this->assertContains('SoftwareApplication', $types);
            } else {
                $this->assertContains('BreadcrumbList', $types, "$path needs a BreadcrumbList");
            }

            $faqNode = collect($data['@graph'])->firstWhere('@type', 'FAQPage');
            $faqs = config("seo.pages.$key.faqs", []);

            $this->assertNotNull($faqNode, "$path needs FAQPage");
            $this->assertCount(count($faqs), $faqNode['mainEntity']);

            // every FAQ question in the schema is visible on the page
            foreach ($faqNode['mainEntity'] as $q) {
                $this->assertStringContainsString(e($q['name']), $html, "FAQ '{$q['name']}' must be visible on $path");
            }
        }
    }

    public function test_sitemap_lists_every_public_page_and_nothing_private(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk();
        $xml = $response->getContent();

        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $this->assertNotFalse(simplexml_load_string($xml), 'sitemap must be valid XML');

        foreach ($this->paths() as $path) {
            $loc = $path === '/' ? 'https://flatcare.in/' : 'https://flatcare.in'.$path;
            $this->assertStringContainsString("<loc>$loc</loc>", $xml);
        }

        preg_match_all('#<loc>([^<]+)</loc>#', $xml, $locs);

        foreach ($locs[1] as $loc) {
            $this->assertStringStartsWith('https://flatcare.in', $loc);
            $this->assertStringNotContainsString('?', $loc, 'sitemap URLs must not carry query strings');
            $this->assertDoesNotMatchRegularExpression('#^https://flatcare\.in/(admin|society|login|register|api|downloads|logout|email)(/|$)#', $loc, "private URL in sitemap: $loc");
        }

        $this->assertCount(count($locs[1]), array_unique($locs[1]), 'sitemap must not contain duplicates');

        $this->assertCount(count($this->paths()), Seo::sitemapEntries());
    }

    public function test_robots_txt_blocks_private_areas_and_points_to_the_sitemap(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap: https://flatcare.in/sitemap.xml', $robots);
        $this->assertStringContainsString('Allow: /', $robots);
        foreach (['/admin/', '/society/', '/api/', '/login', '/register'] as $private) {
            $this->assertStringContainsString("Disallow: $private", $robots);
        }
        // the public pages and assets must not be blocked
        $this->assertDoesNotMatchRegularExpression('#^Disallow:\s*/?$#m', $robots);
        $this->assertStringNotContainsString('Disallow: /css', $robots);
        $this->assertStringNotContainsString('Disallow: /js', $robots);
        $this->assertStringNotContainsString('Disallow: /images', $robots);
    }

    public function test_private_areas_send_a_noindex_header(): void
    {
        foreach (['/login', '/register', '/society/login', '/admin/dashboard'] as $path) {
            $this->assertStringContainsString('noindex', (string) $this->get($path)->headers->get('X-Robots-Tag'), "$path must send X-Robots-Tag noindex");
        }
    }

    public function test_unknown_urls_return_a_404_that_is_noindex(): void
    {
        $response = $this->get('/this-page-does-not-exist');

        $response->assertNotFound();
        $this->assertStringContainsString('name="robots" content="noindex', $response->getContent());
    }

    public function test_pages_link_to_each_other_with_descriptive_anchor_text(): void
    {
        $home = $this->get('/')->getContent();

        foreach ([
            '/features' => 'features',
            '/pricing' => 'pricing',
            '/apartment-management-software' => 'apartment management software',
            '/society-management-software' => 'society management software',
            '/society-maintenance-billing' => 'society maintenance billing',
            '/contact' => 'contact',
        ] as $path => $anchor) {
            $this->assertMatchesRegularExpression('#<a[^>]+href="[^"]*'.preg_quote($path, '#').'"[^>]*>[^<]*'.preg_quote($anchor, '#').'#i', $home, "home must link to $path with the anchor '$anchor'");
        }
    }

    public function test_images_have_dimensions_and_alt_text(): void
    {
        foreach ($this->paths() as $path) {
            preg_match_all('/<img\b[^>]*>/i', $this->get($path)->getContent(), $imgs);

            foreach ($imgs[0] as $img) {
                $this->assertStringContainsString('alt=', $img, "$path: <img> without alt: $img");

                // Site images (not the brand logo from platform settings) need explicit dimensions.
                if (str_contains($img, '/images/marketing/')) {
                    $this->assertMatchesRegularExpression('/\bwidth="\d+"/', $img, "$path: missing width: $img");
                    $this->assertMatchesRegularExpression('/\bheight="\d+"/', $img, "$path: missing height: $img");
                }
            }
        }
    }
}
