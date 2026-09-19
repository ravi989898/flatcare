{{--
    Generic public page (SEO landing pages, Features, Pricing, About, Contact).
    Renders exactly what config/seo.php describes: ONE <h1>, <h2> sections,
    <h3> cards, a visible FAQ and descriptive internal links.
--}}
@extends('layouts.marketing', ['autoOpenTrialModal' => $key !== 'contact'])

@php
    $isContact = $key === 'contact';
    $contact = config('seo.contact');
@endphp

@section('content')
    <header class="seo-hero">
        <div class="container">
            <nav aria-label="Breadcrumb" class="seo-breadcrumb mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $page['name'] }}</li>
                </ol>
            </nav>
            <div class="row align-items-center g-5">
                <div class="col-lg-{{ $isContact ? '6' : '7' }}">
                    <h1 class="seo-h1 mb-3">{{ $page['h1'] }}</h1>
                    <p class="fs-5 text-muted-2 mb-4">{{ $page['lead'] }}</p>
                    <div class="d-flex flex-wrap gap-3">
                        <button type="button" class="btn btn-brand btn-lg rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#trialInquiryModal">Start free trial <i class="bi bi-arrow-right ms-1"></i></button>
                        @if ($isContact)
                            <a href="mailto:{{ $contact['email'] }}" class="btn btn-outline-brand btn-lg rounded-pill px-4"><i class="bi bi-envelope me-1"></i> Email us</a>
                        @else
                            <a href="{{ route('marketing.contact') }}" class="btn btn-outline-brand btn-lg rounded-pill px-4">Talk to our team</a>
                        @endif
                    </div>
                </div>
                <div class="col-lg-{{ $isContact ? '6' : '5' }}">
                    @if ($isContact)
                        <div class="card seo-card p-4">
                            <h2 class="h5 fw-bold mb-1">Request a free trial or demo</h2>
                            <p class="small text-muted-2 mb-3">Tell us about your society and we will get back to you within one business day.</p>
                            @if (session('trial_inquiry_sent'))
                                <div class="alert alert-success mb-3" role="status"><i class="bi bi-check-circle-fill me-1"></i> Thanks — we've got it! Our team will reach out shortly.</div>
                            @endif
                            @include('marketing.partials.trial-form', ['idp' => 'contact_', 'formClass' => 'p-0'])
                        </div>
                    @else
                        <picture>
                            <source srcset="{{ asset('images/marketing/flatcare-society-management-app.webp') }}" type="image/webp">
                            <img src="{{ asset('images/marketing/flatcare-society-management-app.jpg') }}"
                                 alt="FlatCare apartment and society management software on a resident's phone outside a housing society"
                                 class="img-fluid rounded-4 shadow-lg w-100" width="1600" height="666" fetchpriority="high" decoding="async">
                        </picture>
                    @endif
                </div>
            </div>
        </div>
    </header>

    @if ($isContact)
        <section class="py-5" aria-labelledby="ways-title">
            <div class="container">
                <h2 class="section-title text-center mb-2" id="ways-title">Ways to reach us</h2>
                <p class="text-muted-2 text-center mb-4">Customer support is available round the clock by phone and WhatsApp.</p>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="seo-card">
                            <h3><i class="bi bi-telephone-fill text-brand me-2"></i>Call us</h3>
                            @foreach ($contact['phones'] as $i => $phone)
                                <p class="mb-1"><a href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a></p>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="seo-card">
                            <h3><i class="bi bi-whatsapp text-brand me-2"></i>WhatsApp</h3>
                            @foreach ($contact['whatsapp'] as $i => $wa)
                                <p class="mb-1"><a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">Chat on {{ $contact['phones'][$i] }}</a></p>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="seo-card">
                            <h3><i class="bi bi-envelope-fill text-brand me-2"></i>Email</h3>
                            <p class="mb-1"><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></p>
                            <p class="small text-muted-2 mb-0">For support, demos and trial requests.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @foreach ($page['sections'] as $section)
        @continue(empty($section['items']))
        <section class="py-5 {{ $loop->even ? 'bg-brand-soft' : '' }}" aria-labelledby="s{{ $loop->index }}">
            <div class="container">
                <div class="mb-4" style="max-width: 780px;">
                    <h2 class="section-title mb-2" id="s{{ $loop->index }}">{{ $section['h2'] }}</h2>
                    @if (! empty($section['intro']))
                        <p class="text-muted-2 fs-5 mb-0">{!! \App\Support\Seo::linkify($section['intro']) !!}</p>
                    @endif
                </div>
                <div class="row g-4">
                    @foreach ($section['items'] as $item)
                        <div class="col-md-6 col-lg-{{ count($section['items']) >= 4 ? '3' : '4' }}">
                            <div class="seo-card">
                                <h3>{{ $item['h3'] }}</h3>
                                <p class="text-muted-2 mb-0">{!! \App\Support\Seo::linkify($item['text']) !!}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach

    @include('marketing.partials.faq', ['faqs' => $page['faqs'] ?? []])

    @if (! empty($related))
        <section class="py-5 bg-brand-soft" aria-labelledby="related-title">
            <div class="container">
                <h2 class="section-title text-center mb-4" id="related-title">Explore more from FlatCare</h2>
                <div class="row g-3 justify-content-center">
                    @foreach ($related as $r)
                        <div class="col-md-6 col-lg-3">
                            <a class="seo-card d-block text-decoration-none related-link" href="{{ $r['url'] }}">
                                <span class="fw-bold text-brand">{{ $r['label'] }} <i class="bi bi-arrow-right"></i></span>
                                <span class="d-block small text-muted-2 mt-1">{{ $r['blurb'] }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="py-5">
        <div class="container">
            <div class="cta-band text-white text-center p-4 p-lg-5">
                <div class="position-relative">
                    <h2 class="fw-bold mb-2">See FlatCare working for your society</h2>
                    <p class="mb-4 opacity-75">Start a free trial and our team will help you set up your blocks, flats and admins.</p>
                    <button type="button" class="btn btn-light btn-lg rounded-pill px-5 fw-semibold" data-bs-toggle="modal" data-bs-target="#trialInquiryModal">Start free trial</button>
                </div>
            </div>
        </div>
    </section>
@endsection
