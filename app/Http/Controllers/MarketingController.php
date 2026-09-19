<?php

namespace App\Http\Controllers;

use App\Support\Seo;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Public marketing site: the home page, the SEO landing pages described in
 * config/seo.php, and sitemap.xml. Nothing here needs a signed-in user.
 */
class MarketingController extends Controller
{
    public function home(): View
    {
        return view('welcome', [
            'key' => 'home',
            'page' => Seo::pageConfig('home'),
            'seo' => Seo::forPage('home'),
            'topics' => $this->relatedFor(['apartment-management-software', 'society-management-software', 'society-maintenance-software', 'apartment-maintenance-management', 'society-maintenance-billing', 'society-accounting-software', 'society-management-app', 'apartment-management-app']),
        ]);
    }

    public function page(string $key): View
    {
        $page = Seo::pageConfig($key);

        return view('marketing.page', [
            'key' => $key,
            'page' => $page,
            'seo' => Seo::forPage($key),
            'related' => $this->relatedFor($page['related'] ?? []),
        ]);
    }

    public function sitemap(): Response
    {
        return response()
            ->view('sitemap', ['urls' => Seo::sitemapEntries()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Cards linking to other pages with descriptive anchor text.
     *
     * @param  array<int, string>  $keys
     * @return array<int, array{url: string, label: string, blurb: string}>
     */
    private function relatedFor(array $keys): array
    {
        $pages = Seo::pages();

        return collect($keys)
            ->filter(fn (string $key) => isset($pages[$key]))
            ->map(fn (string $key) => [
                'url' => route('marketing.'.$key),
                'label' => $pages[$key]['name'],
                'blurb' => $pages[$key]['blurb'] ?? $this->blurb($pages[$key]['description']),
            ])
            ->values()
            ->all();
    }

    private function blurb(string $description): string
    {
        return \Illuminate\Support\Str::limit($description, 92, '…');
    }
}
