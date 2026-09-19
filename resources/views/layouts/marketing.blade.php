{{--
    Shell for every PUBLIC marketing page (home + SEO landing pages).
    Head data comes from $seo (App\Support\Seo::forPage): unique title and
    description, canonical URL, Open Graph / Twitter tags and JSON-LD.

    No inline scripts (the CSP forbids them). JSON-LD is a data block, not
    executable script, so it is allowed.
--}}
<!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="robots" content="{{ $seo['robots'] }}">
    <link rel="canonical" href="{{ $seo['canonical'] }}">
    @if (config('seo.google_verification'))
        <meta name="google-site-verification" content="{{ config('seo.google_verification') }}">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1c3f2b">
    <meta name="application-name" content="FlatCare">
    <meta name="apple-mobile-web-app-title" content="FlatCare">

    {{-- Open Graph (Facebook, WhatsApp, LinkedIn) --}}
    <meta property="og:type" content="{{ $seo['og_type'] }}">
    <meta property="og:site_name" content="{{ $seo['site_name'] }}">
    <meta property="og:locale" content="en_IN">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:image" content="{{ $seo['og_image'] }}">
    <meta property="og:image:secure_url" content="{{ $seo['og_image'] }}">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="{{ $seo['og_image_width'] }}">
    <meta property="og:image:height" content="{{ $seo['og_image_height'] }}">
    <meta property="og:image:alt" content="{{ $seo['og_image_alt'] }}">

    {{-- Twitter / X --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    <meta name="twitter:image" content="{{ $seo['og_image'] }}">
    <meta name="twitter:image:alt" content="{{ $seo['og_image_alt'] }}">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/favicon-192.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    {{-- Structured data (Organization, WebSite/SoftwareApplication, WebPage, BreadcrumbList, FAQPage) --}}
    <script type="application/ld+json">@json($seo['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="{{ asset('css/marketing.css') }}?v={{ filemtime(public_path('css/marketing.css')) }}" rel="stylesheet">

    @stack('head')
</head>
<body>

@include('marketing.partials.nav')

<main id="main">
    @yield('content')
</main>

@include('marketing.partials.footer')
@include('marketing.partials.trial-modal')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" defer></script>
{{-- External script (the CSP forbids inline JS); data-open tells it whether to
     re-open the trial modal to show validation errors / the thank-you note. --}}
<script src="{{ asset('js/welcome-trial-modal.js') }}" defer @if (($autoOpenTrialModal ?? true) && ($errors->any() || session('trial_inquiry_sent'))) data-open="1" @endif></script>
</body>
</html>
