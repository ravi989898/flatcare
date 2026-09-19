# FlatCare – SEO Audit & Implementation Report

Site: https://flatcare.in/ · Stack: Laravel 12 / Blade, Apache (`public/.htaccess`).

**No ranking is promised.** Nobody can guarantee a Google position. This work makes the site technically correct and clearly about *FlatCare = apartment & society management software*, so Google can crawl, index and understand it. Whether "flatcare" ranks first also depends on things outside the code (see §12).

## 1. Issues found before the work

| # | Issue | Impact |
|---|---|---|
| 1 | Only **one** public page (the home page). No pages for the target keywords (society management, maintenance billing, app …), no Features/Pricing/About/Contact | Google had nothing to rank for most of your keywords |
| 2 | Home copy and H1 described **property-management for landlords** ("FlatCare — effortless property care", rent, tenants) while the product is apartment & society software | Google could not tell what FlatCare is |
| 3 | The home H1 did not match the title/meta ("Smart Apartment & Society Management Software") | Weak topical signal |
| 4 | Sitemap contained only `/`; `robots.txt` had an empty `Disallow:` (private areas not declared) | Poor crawl guidance |
| 5 | Schema was a single `SoftwareApplication` (with an invalid, price-less `Offer`); no Organization, WebSite, WebPage, Breadcrumb or FAQ | No brand entity for "FlatCare"/"Flat Care" |
| 6 | No visible FAQ, no internal links to inner pages, nav had only in-page anchors | No internal link equity or anchor text |
| 7 | Canonical used `url('/')` from the current request (host/scheme dependent); no www/http → https redirect | Risk of duplicate host variants |
| 8 | Images: 4.2 MB of PNG/JPG (two 1.5 MB PNG backgrounds), meaningless names (`f1.png`, `s2.png`), `alt=""` on content art, no width/height | Slow LCP, weak image SEO, layout shift |
| 9 | 404 page = framework default; login/register/admin had no `noindex` header | Possible index of private/error URLs |
| 10 | Heading order: home jumped `<h1>` → `<h3>` (app band), several `<h5>` headings | Weak document outline |
| 11 | `<style>` block (≈250 lines) inlined into the home HTML on every request | Not cacheable, larger HTML |

## 2. What was implemented

**Architecture (existing app reused, nothing rewritten):** content lives in `config/seo.php`; `App\Support\Seo` builds canonicals and JSON-LD; `MarketingController` renders the pages; `layouts/marketing.blade.php` + `marketing/partials/*` hold the shared head, nav, footer and trial modal; the old inline CSS moved to `public/css/marketing.css`. The visual design of the home page is unchanged.

**Pages (13, all indexable, in the sitemap):**

| URL | Title (chars) | H1 |
|---|---|---|
| `/` | FlatCare – Smart Apartment & Society Management Software (56) | Smart Apartment & Society Management Software |
| `/apartment-management-software` | Apartment Management Software for Buildings \| FlatCare (54) | Apartment Management Software for Everyday Building Operations |
| `/society-management-software` | Society Management Software for Housing Societies \| FlatCare (60) | Society Management Software for Housing Societies and RWAs |
| `/society-maintenance-software` | Society Maintenance Software & App \| FlatCare (45) | Society Maintenance Software That Keeps Dues and Complaints Organised |
| `/apartment-maintenance-management` | Apartment Maintenance Management Software \| FlatCare (52) | Apartment Maintenance Management Made Simple |
| `/society-maintenance-billing` | Society Maintenance Billing Software \| FlatCare (47) | Society Maintenance Billing Software with Online Payment |
| `/society-accounting-software` | Society Accounting Software for Dues & Payments \| FlatCare (58) | Society Accounting Software for Dues, Payments and Receipts |
| `/society-management-app` | Society Management App for Residents \| FlatCare (47) | Society Management App for Residents and Committees |
| `/apartment-management-app` | Apartment Management App with Visitor Approval \| FlatCare (57) | Apartment Management App for Visitors, Bills and Complaints |
| `/features` | FlatCare Features – Society & Apartment Management Tools (56) | FlatCare Features for Apartment and Society Management |
| `/pricing` | FlatCare Pricing – Free Trial for Housing Societies (51) | FlatCare Pricing for Apartments and Housing Societies |
| `/about` | About FlatCare – Apartment & Society Management Software (56) | About FlatCare |
| `/contact` | Contact FlatCare – Support and Free Trial Requests (50) | Contact FlatCare |

Meta descriptions (all unique, 140–154 characters, checked by a test) are in `config/seo.php`. Home:
*"FlatCare is smart apartment and society management software for maintenance billing, expense tracking, accounting, residents and daily society operations."* (154)

**Heading structure:** one `<h1>` per page (tested). Home: H1 → H2 "What is FlatCare?", "A better place to call home", "Everything you need to run your apartment or society", "Everything your society needs, in one place", "See your whole society at a glance", "Up and running in three steps", "Trusted by society committees", FAQ, closing CTA; feature/step/FAQ cards are H3. Landing pages: H1 → 3 × H2 (topic) → H3 cards → H2 FAQ → H2 "Explore more".

**Keywords:** placed naturally in the H1/H2/first paragraph of the page they belong to (one primary phrase per page; related phrases used once or twice in body and FAQ). Brand phrases ("FlatCare", "Flat Care", "FlatCare app", "FlatCare society management") appear in titles, headings, About/footer, schema `alternateName` and copy. No hidden text, no repeated keyword blocks.

**Home content added:** "What is FlatCare?", a 12-topic solutions grid covering apartment management, society management, maintenance billing, fee collection, expense & accounting records, resident management, complaint management, security management, reports/summaries, admin management, the app and pricing (each links to its page), a visible 9-question FAQ. Hero/mock-up text rewritten from landlord/tenant language to society language.

**Structured data (JSON-LD, one `@graph` per page, valid JSON tested):**
- Every page: `Organization` (name FlatCare, alternate names Flat Care / FlatCare Society Management / FlatCare App, logo, customer-support contact point, India), `WebPage`, `FAQPage`.
- Home also: `WebSite` (with alternate names) and `SoftwareApplication` (BusinessApplication, Web + Android, feature list, publisher).
- Inner pages: `BreadcrumbList` (Home › page).
- **FAQPage is generated from the same array that renders the visible FAQ** (a test asserts every schema question is visible on the page).
- Not added on purpose: `AggregateRating`/`Review` (the testimonials are not verifiable reviews), `Offer` with a price (no public prices yet), `sameAs` (no social profile URLs supplied).

**Canonical:** built from the configured origin `https://flatcare.in` + the page path, never from the request, so `?utm_…`, `www.`, `http://` and trailing-slash variants all canonicalise to the clean URL (tested with query strings and another host). `.htaccess` also 301-redirects `www.flatcare.in` and plain `http://flatcare.in` to `https://flatcare.in` (production hosts only; honours `X-Forwarded-Proto`), and strips trailing slashes.

**Open Graph / Twitter:** `og:type/site_name/locale/url/title/description/image(+secure_url,type,width,height,alt)`, `twitter:card=summary_large_image` + title/description/image/alt on every page. Image: `/images/marketing/flatcare-society-management-app.jpg` (1600×666).

**robots.txt** (`public/robots.txt`): `Allow: /`; `Disallow:` `/admin/`, `/society/`, `/api/`, `/login`, `/register`, `/logout`, `/email/`, `/trial-inquiries`, `/downloads/`; `Sitemap: https://flatcare.in/sitemap.xml`. CSS/JS/images are **not** blocked. In addition, private areas send `X-Robots-Tag: noindex, nofollow, noarchive` (robots.txt only stops crawling; the header stops indexing).

**sitemap.xml** (`/sitemap.xml`, generated from `config/seo.php`): 13 canonical URLs, each with `lastmod`, `changefreq`, `priority`; no admin/login/private/duplicate URLs, no query strings (tested).

**Internal linking:** nav (Features, How it works, Solutions ▾ → 5 pages, Pricing, About, Contact); footer (all 8 solution pages + company pages); home topic grid and inline links (`society management software`, `apartment management software`, `society maintenance billing`, `pricing`, `features`, `contact`); each landing page has in-text links and an "Explore more from FlatCare" block; breadcrumbs on inner pages; a test checks the home page links to the required pages with descriptive anchor text.

**Image SEO:** every marketing image converted to WebP (hero background 1,539 KB → 63 KB, app banner 1,529 KB → 52 KB, banners 198/176 KB → 110/95 KB, illustrations 40–99 KB → 2–9 KB): **whole `public/images/marketing` folder ≈ 4.2 MB → ≈ 0.66 MB**. Meaningful file names (`apartment-society-hero-background.webp`, `features/maintenance-billing.webp`, `steps/society-setup.webp`, …), descriptive `alt` text, explicit `width`/`height` on every content image (tested), `loading="lazy"` + `decoding="async"` below the fold, hero background preloaded (`fetchpriority="high"`), JPG kept only as the OG/`<picture>` fallback.

**Technical SEO:** 404 page is friendly, has links back to key pages and `noindex`; heading levels fixed; `lang="en-IN"`; skip-to-content link; `theme-color`; CSS moved out of the HTML to a cacheable, versioned file (`?v=filemtime`); Bootstrap JS `defer`; SRI on CDN assets (from the security audit); no inline scripts (CSP-safe); responsive layout; no horizontal overflow at desktop width; crawler test of all internal links/assets found **0 broken links**.

**Tests:** `tests/Feature/SeoTest.php` – 12 tests / ~800 assertions: 200 + indexable, exactly one H1, requested home title/H1/description, unique titles & descriptions with sane lengths, canonical (with `?utm`, `www`, `http`), OG/Twitter tags, JSON-LD validity + FAQ visibility, sitemap content, robots.txt content, `noindex` headers on private areas, noindex 404, internal links, image alt/dimensions. Full suite: **51 passed, 0 failed**.

## 3. Sitemap / robots / canonical status
- `https://flatcare.in/sitemap.xml` – valid XML, 13 URLs ✅ · `https://flatcare.in/robots.txt` – valid, references the sitemap ✅ · canonical on every indexable page ✅ · no accidental `noindex` on public pages (tested) ✅.

## 4. Mobile SEO
Bootstrap responsive grid, `viewport` meta, 16px+ body text, tap-sized buttons, images scale (`img-fluid`), navigation collapses to a menu. Verified: no horizontal overflow. Run Google's Mobile-Friendly/PageSpeed check after deployment (§8).

## 5. Performance (what changed / what remains)
Changed: −3.5 MB of images, cacheable CSS, preloaded hero, `defer` JS, explicit image sizes (less layout shift), lazy loading.
Remaining (not changed, to keep the design): Bootstrap CSS/Icons and Google Fonts come from CDNs (render-blocking; self-hosting them would improve LCP and let you tighten the CSP); the community banner image (`flatcare-society-management-app`) still shows the old "Maintenance Requests" wording inside the picture; server should send long `Cache-Control` for `/css`, `/js`, `/images` and enable gzip/brotli; measure real Core Web Vitals in Search Console after launch.

## 6. Things I need you to confirm (content accuracy)
1. **Expenses & accounting.** The code has bills, fee types, extra charges, water readings, payments and receipts, but **no dedicated expense-ledger module**. I used your requested title/description on the home page, but the body copy says honestly that FlatCare covers charges, payments and receipts and that expense workflows should be discussed. If you have (or plan) an expense module, tell me and I will update the copy.
2. **Pricing.** No prices exist in the project, so `/pricing` explains "free trial + plan based on society size" without numbers, and no price schema is emitted.
3. **Social profiles.** Add the URLs (Facebook, Instagram, LinkedIn, YouTube, Play Store…) to `sameAs` in `App\Support\Seo::organization()` once they exist – this is one of the strongest signals for a brand query.
4. **Testimonials/statistics** on the home page (names, "98%", "4.9/5", "20+ properties") look like placeholders. Replace them with real ones; do not add review schema until they are real.
5. **Hindi/Gujarati:** only English pages exist. Localised pages would need `hreflang` – not added.

## 7. Files added / changed
Added: `config/seo.php`, `app/Support/Seo.php`, `app/Http/Controllers/MarketingController.php`, `resources/views/layouts/marketing.blade.php`, `resources/views/marketing/page.blade.php` + `partials/{nav,footer,faq,trial-form,trial-modal}.blade.php`, `resources/views/errors/404.blade.php`, `public/css/marketing.css`, `tests/Feature/SeoTest.php`, WebP images (renamed).
Changed: `resources/views/welcome.blade.php` (extends the layout; new copy/sections), `resources/views/sitemap.blade.php`, `routes/web.php`, `public/robots.txt`, `public/.htaccess`, `app/Http/Middleware/SecurityHeaders.php` (X-Robots-Tag), `SEO_AUDIT.md`.
Removed: the heavy PNG/JPG originals of the marketing images (kept `flatcare-society-management-app.jpg` for OG).

## 8. Google Search Console – steps after deployment
1. Deploy, then check: `https://flatcare.in/` loads over HTTPS, `http://` and `www.` redirect once to `https://flatcare.in/`, `/robots.txt` and `/sitemap.xml` open, and `/features` etc. return 200.
2. Open https://search.google.com/search-console → **Add property** → *Domain* (`flatcare.in`, DNS TXT record) – or *URL prefix* `https://flatcare.in/` with the HTML tag: put the token in `.env` as `GOOGLE_SITE_VERIFICATION=<content value>` (the layout prints the meta tag), run `php artisan config:cache`, click **Verify**.
3. **Sitemaps** → add `sitemap.xml` → Submit. Status should be "Success" with 13 discovered URLs.
4. **URL Inspection** → enter `https://flatcare.in/` → *Test live URL* → check "URL is available to Google" and "Indexing allowed: Yes" → **Request indexing**. Repeat for `/society-management-software`, `/apartment-management-software` and `/features`.
5. **Pages** report → confirm no "Excluded by noindex" or "Duplicate, Google chose different canonical" for public pages; **Enhancements** → check FAQ and Breadcrumbs are valid (Software application is informational).
6. **Core Web Vitals** and **Mobile usability** reports fill in after a few weeks of traffic; also run https://pagespeed.web.dev/ for the home page and `/society-maintenance-billing`.
7. Validate structured data with https://validator.schema.org/ and https://search.google.com/test/rich-results.
8. Recheck in 1–4 weeks; indexing and ranking are not instant.

## 9. Brand search ("flatcare", "flat care") – off-site actions
The site now states consistently what FlatCare is. To help Google connect the brand: create/verify a **Google Business Profile** if you have an office; publish the **Android app on Google Play** with the same name and description; create social profiles and link them from the site (`sameAs`); get FlatCare listed on relevant directories and in local society/RWA communities; earn a few genuine backlinks (customers, press, partner sites); keep the name, logo and description identical everywhere. Do not buy links or use keyword-stuffed listings.
