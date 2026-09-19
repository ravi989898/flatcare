{{--
    FlatCare home page (https://flatcare.in/). Head/SEO data comes from
    config/seo.php via App\Http\Controllers\MarketingController; the
    navigation, footer and trial modal live in resources/views/marketing/partials.
--}}
@extends('layouts.marketing')

@push('head')
    {{-- The hero background is the largest above-the-fold image: fetch it early for a faster LCP. --}}
    <link rel="preload" as="image" href="{{ asset('images/marketing/apartment-society-hero-background.webp') }}" type="image/webp" fetchpriority="high">
@endpush

@section('content')
<header class="hero">
    <div class="hero-mesh"></div>
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="eyebrow-badge mb-4"><span class="eyebrow-dot"></span> FlatCare · Built for housing societies &amp; apartments</span>
                <h1 class="mb-4">Smart <span class="grad-text">Apartment &amp; Society Management Software</span></h1>
                <p class="fs-5 text-muted-2 mb-4">
                    FlatCare helps apartments and housing societies manage maintenance billing, fee collection,
                    residents, visitors and complaints from one dashboard and mobile app — so the committee
                    spends less time on paperwork and residents always know where things stand.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <button type="button" class="btn btn-brand btn-lg rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#trialInquiryModal">Start free trial <i class="bi bi-arrow-right ms-1"></i></button>
                    <a href="#how-it-works" class="btn btn-outline-brand btn-lg rounded-pill px-4"><i class="bi bi-play-circle me-1"></i> See how it works</a>
                </div>
                <div class="hero-checks d-flex flex-wrap gap-4">
                    <span><i class="bi bi-check-circle-fill"></i> Easy to use</span>
                    <span><i class="bi bi-check-circle-fill"></i> Save time</span>
                    <span><i class="bi bi-check-circle-fill"></i> Better living</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-visual-wrap">
                    <div class="hero-visual-glow"></div>
                    <div class="float-icon float-icon--home"><i class="bi bi-house-door-fill"></i></div>
                    <div class="float-icon float-icon--tool"><i class="bi bi-wrench"></i></div>
                    <div class="float-icon float-icon--team"><i class="bi bi-people-fill"></i></div>
                    <div class="float-icon float-icon--chat"><i class="bi bi-chat-square-text-fill"></i></div>
                    <div class="mock-panel">
                        <div class="mock-topbar">
                            <div class="mock-dot"></div><div class="mock-dot"></div><div class="mock-dot"></div>
                        </div>
                        <div class="mock-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="fw-bold">Society overview</div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-4 mock-row"></div>
                                <div class="col-3 mock-row"></div>
                                <div class="col-5 mock-row"></div>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <div class="mock-bar-bg"><div class="mock-bar-fill" style="width:78%"></div></div>
                                <div class="mock-bar-bg"><div class="mock-bar-fill" style="width:54%; background:linear-gradient(90deg,#f0b429,#e0a11f);"></div></div>
                                <div class="mock-bar-bg"><div class="mock-bar-fill" style="width:92%"></div></div>
                            </div>
                            <div class="row g-2 mt-1">
                                <div class="col-4"><div class="mock-stat"><i class="bi bi-house-door-fill"></i><div><small>Total Flats</small><strong>240</strong></div></div></div>
                                <div class="col-4"><div class="mock-stat"><i class="bi bi-people-fill"></i><div><small>Residents</small><strong>218</strong></div></div></div>
                                <div class="col-4"><div class="mock-stat"><i class="bi bi-wrench"></i><div><small>Open Complaints</small><strong>12</strong></div></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="float-card float-card--stat p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 small">This week's overview</h6>
                            <span class="badge bg-success-subtle text-success">Live</span>
                        </div>
                        <div class="row g-2 text-center mb-2">
                            <div class="col-4">
                                <div class="stat-chip">
                                    <div class="fw-bold text-brand">24</div>
                                    <div class="text-muted-2" style="font-size:.65rem;">Tickets</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-chip">
                                    <div class="fw-bold text-brand">98%</div>
                                    <div class="text-muted-2" style="font-size:.65rem;">On-time</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-chip">
                                    <div class="fw-bold text-brand">4.9</div>
                                    <div class="text-muted-2" style="font-size:.65rem;">Rating</div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 small">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <div>Water leak complaint resolved <span class="text-muted-2">2h ago</span></div>
                        </div>
                        <div class="d-flex align-items-center gap-2 small mt-1">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <div>Maintenance bills generated</div>
                        </div>
                        <div class="d-flex align-items-center gap-2 small mt-1">
                            <i class="bi bi-clock-fill text-secondary"></i>
                            <div>2 new complaints received</div>
                        </div>
                    </div>

                    <div class="float-card float-card--task p-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-bell-fill text-brand"></i>
                            <div class="fw-bold small">Notice published</div>
                        </div>
                        <div class="small text-muted-2">Block B &middot; 18 residents notified</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="py-5" id="what-is-flatcare" aria-labelledby="what-is-title">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <span class="section-eyebrow">About FlatCare</span>
                <h2 class="section-title mb-3" id="what-is-title">What is FlatCare?</h2>
                <p class="text-muted-2 fs-5">
                    FlatCare is apartment and society management software for housing societies, apartment buildings and
                    residential communities in India. It replaces registers, spreadsheets and scattered chat groups with
                    one system that the whole community can rely on.
                </p>
                <p class="text-muted-2">
                    Committee members and admins use a web dashboard to prepare maintenance bills, record payments, manage
                    residents and flats, follow up on complaints and publish notices. Residents use the FlatCare mobile app
                    to see their dues, pay online, approve visitors and stay informed, while security guards record every
                    entry at the gate.
                </p>
                <p class="mb-0">
                    <a class="text-brand fw-semibold" href="{{ route('marketing.society-management-software') }}">Learn about society management software</a>
                    &nbsp;·&nbsp;
                    <a class="text-brand fw-semibold" href="{{ route('marketing.apartment-management-software') }}">Explore apartment management software</a>
                </p>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-sm-6"><div class="seo-card"><h3><i class="bi bi-building-check text-brand me-2"></i>For committees</h3><p class="text-muted-2 mb-0">Billing, residents, complaints and notices in one dashboard.</p></div></div>
                    <div class="col-sm-6"><div class="seo-card"><h3><i class="bi bi-phone text-brand me-2"></i>For residents</h3><p class="text-muted-2 mb-0">Bills, visitor approvals and updates in a simple app.</p></div></div>
                    <div class="col-sm-6"><div class="seo-card"><h3><i class="bi bi-shield-check text-brand me-2"></i>For security</h3><p class="text-muted-2 mb-0">A digital gate register with resident approval.</p></div></div>
                    <div class="col-sm-6"><div class="seo-card"><h3><i class="bi bi-buildings text-brand me-2"></i>For any size</h3><p class="text-muted-2 mb-0">From one apartment building to multi-block societies.</p></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 my-2">
    <div class="container">
        <div class="text-center mb-5 mx-auto" style="max-width:640px;">
            <span class="section-eyebrow">Care for a Better Living</span>
            <h2 class="section-title mb-3">A better place to call home</h2>
            <p class="text-muted-2 fs-5">Residents, maintenance bills, payments and announcements — everything a happy society needs, in one friendly app.</p>
        </div>
        <picture>
            <source srcset="{{ asset('images/marketing/flatcare-society-management-app.webp') }}" type="image/webp">
            <img src="{{ asset('images/marketing/flatcare-society-management-app.jpg') }}"
                 alt="A family using the FlatCare society management app to check maintenance bills, payments and announcements outside their apartment building"
                 class="img-fluid rounded-4 shadow-lg w-100" loading="lazy" decoding="async" width="1600" height="666">
        </picture>
    </div>
</section>

<section class="pt-4 pb-0">
    <div class="container">
        <div class="cta-band app-band text-white p-4 p-lg-5 d-flex align-items-center">
            <div class="app-band-copy">
                <h2 class="h3 fw-bold mb-2"><i class="bi bi-phone"></i> Get the FlatCare society management app</h2>
                <p class="mb-4">
                    Pay maintenance, raise requests and stay updated — right from your phone.
                    Android only for now, test build.
                </p>
                <a href="{{ config('flatcare.apk_url') ?? '/downloads/flatcare-app.apk' }}" download="flatcare-app.apk" class="btn btn-light btn-lg px-4 fw-semibold">
                    <i class="bi bi-download"></i> Download for Android
                </a>
                <p class="small mb-0 mt-2 opacity-75">
                    After downloading, open the file and allow "install from unknown sources" if asked.
                </p>
            </div>
        </div>
    </div>
</section>

<section id="features" class="py-5 py-lg-6 my-2">
    <div class="container">
        <div class="text-center mb-5 mx-auto" style="max-width:640px;">
            <span class="section-eyebrow">Features</span>
            <h2 class="section-title mb-3">Everything you need to run your apartment or society</h2>
            <p class="text-muted-2 fs-5">One dashboard for maintenance billing, payments and people — built to feel effortless. <a href="{{ route('marketing.features') }}" class="text-brand fw-semibold">See all FlatCare features</a>.</p>
        </div>
        <div class="row g-4">
            @foreach ([
                ['icon' => 'bi-tools', 'title' => 'Maintenance billing', 'text' => 'Fixed maintenance, water and extra charges billed for every flat, with clear payment status.', 'art' => 'maintenance-billing', 'w' => 177, 'h' => 237, 'alt' => 'Maintenance billing checklist for a housing society'],
                ['icon' => 'bi-credit-card', 'title' => 'Fee collection & payments', 'text' => 'Residents pay online, the office records other payments, and receipts are always on record.', 'art' => 'fee-collection-payments', 'w' => 226, 'h' => 237, 'alt' => 'Online maintenance payment and receipt'],
                ['icon' => 'bi-chat-dots', 'title' => 'Notices & complaints', 'text' => 'Announcements reach every resident, and complaints are tracked from report to resolution.', 'art' => 'resident-communication', 'w' => 228, 'h' => 247, 'alt' => 'Resident announcements and complaint messages'],
                ['icon' => 'bi-graph-up', 'title' => 'Dues at a glance', 'text' => 'See who has paid, what is pending and how collections are going, without spreadsheets.', 'art' => 'society-reports', 'w' => 230, 'h' => 235, 'alt' => 'Society collection summary and dues chart'],
                ['icon' => 'bi-shield-lock', 'title' => 'Security & visitors', 'text' => 'A digital gate register with resident approval, gate passes and role-based admin access.', 'art' => 'security-visitor-management', 'w' => 221, 'h' => 245, 'alt' => 'Visitor management shield for apartment security'],
                ['icon' => 'bi-phone', 'title' => 'Web dashboard & mobile app', 'text' => 'A responsive dashboard for the committee and an Android app for residents and guards.', 'art' => 'web-and-mobile-access', 'w' => 241, 'h' => 255, 'alt' => 'FlatCare web dashboard and mobile app'],
            ] as $i => $f)
                <div class="col-md-6 col-lg-4">
                    <div class="card card-feature h-100 p-4">
                        <span class="feature-index">{{ sprintf('%02d', $i + 1) }}</span>
                        <div class="feature-icon mb-3"><i class="bi {{ $f['icon'] }}"></i></div>
                        <h3 class="h5 fw-bold">{{ $f['title'] }}</h3>
                        <p class="text-muted-2 mb-0 feature-desc">{{ $f['text'] }}</p>
                        <img src="{{ asset('images/marketing/features/'.$f['art'].'.webp') }}" alt="{{ $f['alt'] }}" class="feature-art" width="{{ $f['w'] }}" height="{{ $f['h'] }}" loading="lazy" decoding="async">
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-5 bg-brand-soft" id="solutions" aria-labelledby="solutions-title">
    <div class="container">
        <div class="text-center mb-5 mx-auto" style="max-width:700px;">
            <span class="section-eyebrow">Solutions</span>
            <h2 class="section-title mb-3" id="solutions-title">Everything your society needs, in one place</h2>
            <p class="text-muted-2 fs-5 mb-0">From maintenance billing to gate security, FlatCare covers the daily work of an apartment or housing society.</p>
        </div>
        <div class="row g-4">
            @foreach ([
                ['icon' => 'bi-buildings', 'title' => 'Apartment management', 'text' => 'Flat, block and resident records for apartment buildings.', 'route' => 'marketing.apartment-management-software', 'anchor' => 'Apartment management software'],
                ['icon' => 'bi-people', 'title' => 'Society management', 'text' => 'Committee roles, notices, polls and elections for housing societies.', 'route' => 'marketing.society-management-software', 'anchor' => 'Society management software'],
                ['icon' => 'bi-receipt', 'title' => 'Maintenance billing', 'text' => 'Bills for every flat, including water and extra charges.', 'route' => 'marketing.society-maintenance-billing', 'anchor' => 'Society maintenance billing'],
                ['icon' => 'bi-cash-coin', 'title' => 'Maintenance fee collection', 'text' => 'Online payments, recorded payments and receipts for each flat.', 'route' => 'marketing.apartment-maintenance-management', 'anchor' => 'Apartment maintenance management'],
                ['icon' => 'bi-journal-check', 'title' => 'Expense & accounting records', 'text' => 'Charges, extra charges, payments and receipts kept clean for the treasurer.', 'route' => 'marketing.society-accounting-software', 'anchor' => 'Society accounting software'],
                ['icon' => 'bi-person-lines-fill', 'title' => 'Resident management', 'text' => 'Owners, tenants, family members and vehicles in one directory.', 'route' => 'marketing.features', 'anchor' => 'FlatCare features'],
                ['icon' => 'bi-chat-left-text', 'title' => 'Complaint management', 'text' => 'Residents report issues; the committee tracks each one to completion.', 'route' => 'marketing.society-maintenance-software', 'anchor' => 'Society maintenance software'],
                ['icon' => 'bi-shield-lock', 'title' => 'Security management', 'text' => 'Visitor entries, gate passes, pre-approvals and guard roster.', 'route' => 'marketing.apartment-management-app', 'anchor' => 'Apartment management app'],
                ['icon' => 'bi-bar-chart-line', 'title' => 'Reports & summaries', 'text' => 'Pending dues, payment status and visitor logs at a glance.', 'route' => 'marketing.features', 'anchor' => 'All features'],
                ['icon' => 'bi-person-gear', 'title' => 'Admin management', 'text' => 'Role-based access for admins, committee members, residents and guards.', 'route' => 'marketing.about', 'anchor' => 'About FlatCare'],
                ['icon' => 'bi-phone', 'title' => 'Society management app', 'text' => 'Bills, visitors, complaints and notices on the resident’s phone.', 'route' => 'marketing.society-management-app', 'anchor' => 'Society management app'],
                ['icon' => 'bi-tag', 'title' => 'Pricing & free trial', 'text' => 'Start with a free trial and get a plan that fits your society.', 'route' => 'marketing.pricing', 'anchor' => 'FlatCare pricing'],
            ] as $topic)
                <div class="col-md-6 col-lg-4">
                    <div class="seo-card">
                        <h3><i class="bi {{ $topic['icon'] }} text-brand me-2"></i>{{ $topic['title'] }}</h3>
                        <p class="text-muted-2 mb-2">{{ $topic['text'] }}</p>
                        <a class="fw-semibold text-brand text-decoration-none" href="{{ route($topic['route']) }}">{{ $topic['anchor'] }} <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="text-center mt-4 mb-0">Questions? <a class="text-brand fw-semibold" href="{{ route('marketing.contact') }}">Contact the FlatCare team</a>.</p>
    </div>
</section>


<section class="py-5 my-2">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-lg-2">
                <div class="mock-panel">
                    <div class="mock-topbar">
                        <div class="mock-dot"></div><div class="mock-dot"></div><div class="mock-dot"></div>
                    </div>
                    <div class="mock-body">
                        <div class="row g-2 mb-3">
                            <div class="col-4"><div class="rounded-3 p-3" style="background:var(--brand-light);"><div class="fw-bold text-brand small">Open</div><div class="fw-bold">12</div></div></div>
                            <div class="col-4"><div class="rounded-3 p-3" style="background:#fff7e6;"><div class="fw-bold small" style="color:#a3760f;">Due</div><div class="fw-bold">5</div></div></div>
                            <div class="col-4"><div class="rounded-3 p-3" style="background:#eaf6ee;"><div class="fw-bold text-brand small">Paid</div><div class="fw-bold">231</div></div></div>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <div class="mock-bar-bg"><div class="mock-bar-fill" style="width:180px; max-width:75%;"></div></div>
                            <div class="mock-bar-bg"><div class="mock-bar-fill" style="width:60%; background:linear-gradient(90deg,#f0b429,#e0a11f);"></div></div>
                            <div class="mock-bar-bg"><div class="mock-bar-fill" style="width:88%"></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 order-lg-1">
                <span class="section-eyebrow">Built for busy committees</span>
                <h2 class="section-title mb-3">See your whole society at a glance</h2>
                <p class="text-muted-2 fs-5 mb-4">
                    From a single dashboard, track open complaints, visitors and maintenance dues across every block and flat — no more spreadsheets or scattered messages.
                </p>
                <ul class="list-unstyled d-flex flex-column gap-3">
                    <li class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill text-brand mt-1"></i><span>Live maintenance billing and payment status</span></li>
                    <li class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill text-brand mt-1"></i><span>Notifications and notices for every resident</span></li>
                    <li class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill text-brand mt-1"></i><span>One place for complaints, visitors and society records</span></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="py-5 my-2">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <img src="{{ asset('images/marketing/society-security-gate-family.webp') }}"
                     alt="A family welcomed by the security guard at their housing society gate, with visitors approved through FlatCare"
                     class="img-fluid rounded-4 shadow-lg w-100" loading="lazy" decoding="async" width="1200" height="800">
            </div>
            <div class="col-lg-6">
                <span class="section-eyebrow">Care for a Better Living</span>
                <h2 class="section-title mb-3">Happier residents, calmer committees</h2>
                <p class="text-muted-2 fs-5 mb-4">
                    When maintenance, payments and announcements just work, residents stop chasing the
                    committee — and start enjoying the place they live in.
                </p>
                <ul class="list-unstyled d-flex flex-column gap-3">
                    <li class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill text-brand mt-1"></i><span>Every notice reaches every resident, instantly</span></li>
                    <li class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill text-brand mt-1"></i><span>Security, visitors and facilities, all tracked in one place</span></li>
                    <li class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill text-brand mt-1"></i><span>A friendlier way to manage a happier community</span></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section id="how-it-works" class="py-5 py-lg-6 bg-brand-soft my-4">
    <div class="container py-4">
        <div class="text-center mb-5 mx-auto" style="max-width:600px;">
            <span class="section-eyebrow">How it works</span>
            <h2 class="section-title mb-2">Up and running in <span class="text-brand">three steps</span></h2>
            <p class="text-muted-2 fs-5 mb-0">Set up, automate and stay in control — all in just a few minutes.</p>
        </div>
        <div class="row g-4">
            @foreach ([
                ['title' => 'Set up your society', 'text' => 'Add blocks, flats and residents in minutes. Get everything ready in one place.', 'art' => 'society-setup', 'alt' => 'Apartment building added to FlatCare'],
                ['title' => 'Handle everything with ease', 'text' => 'Track maintenance billing, collect payments, manage visitors and keep residents updated.', 'art' => 'daily-society-management', 'alt' => 'Society tasks checklist with a settings gear'],
                ['title' => 'Keep your society connected', 'text' => 'Manage complaints, visitors, notices and society activities from a single dashboard.', 'art' => 'connected-society-dashboard', 'alt' => 'Society dashboard with resident, document and calendar icons'],
            ] as $i => $step)
                <div class="col-lg-4 position-relative">
                    @unless ($loop->last)
                        <div class="step-connector d-none d-lg-block"></div>
                    @endunless
                    <div class="step-card p-4">
                        <div class="step-num mb-3">{{ $i + 1 }}</div>
                        <h3 class="h5 fw-bold">{{ $step['title'] }}</h3>
                        <p class="text-muted-2 mb-0 step-desc">{{ $step['text'] }}</p>
                        <img src="{{ asset('images/marketing/steps/'.$step['art'].'.webp') }}" alt="{{ $step['alt'] }}" class="step-art" width="270" height="293" loading="lazy" decoding="async">
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-5 my-4">
    <div class="container">
        <div class="stats-band p-5 p-lg-6">
            <div class="row g-4 text-center position-relative">
                <div class="col-6 col-lg-3">
                    <div class="stat-num">20+</div>
                    <div class="small opacity-75 mt-1">Properties managed</div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-num">3k+</div>
                    <div class="small opacity-75 mt-1">Requests resolved</div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-num">98%</div>
                    <div class="small opacity-75 mt-1">On-time response</div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-num">4.9/5</div>
                    <div class="small opacity-75 mt-1">Average rating</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="testimonials" class="py-5 py-lg-6 my-4">
    <div class="container">
        <div class="text-center mb-5 mx-auto" style="max-width:640px;">
            <span class="section-eyebrow">Testimonials</span>
            <h2 class="section-title mb-2">Trusted by <span class="text-brand">society committees</span></h2>
            <p class="text-muted-2 fs-5 mb-0">Property managers, residents and committees rely on FlatCare to simplify day-to-day operations and keep their communities happy.</p>
        </div>
        <div class="row g-4">
            @foreach ([
                ['quote' => 'FlatCare made our **maintenance process so simple.** Tenants can easily raise requests and we can track everything in one place.', 'name' => 'Aarav Mehta', 'role' => 'Property Manager, 40 Units', 'tone' => 'green'],
                ['quote' => 'Rent collection used to be our biggest headache. Now it **basically runs itself.** FlatCare saves us hours every month.', 'name' => 'Sneha Kapoor', 'role' => 'Landlord, 12 Units', 'tone' => 'blue'],
                ['quote' => 'Simple, powerful and easy to use. FlatCare keeps our residents engaged and our community **running smoothly.**', 'name' => 'Rohan Verma', 'role' => 'Operations Lead, Skyline Homes', 'tone' => 'gold'],
            ] as $i => $t)
                <div class="col-lg-4">
                    <div class="card card-feature testimonial-card testimonial-card--{{ $t['tone'] }} h-100 p-4">
                        <div class="stars mb-2">★★★★★</div>
                        <div class="quote-mark">&ldquo;</div>
                        <p class="mb-4 mt-n2 testimonial-quote">{{-- Escape FIRST, then turn only our own **bold** markers into <b>: no raw HTML from data ever reaches the page. --}}
                        {!! preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', e($t['quote'])) !!}</p>
                        <div class="mt-auto d-flex align-items-center gap-3">
                            <img src="{{ asset('images/marketing/testimonials/a'.($i + 1).'.jpg') }}" alt="{{ $t['name'] }}, {{ $t['role'] }}" class="testimonial-avatar" width="48" height="48" loading="lazy" decoding="async">
                            <div>
                                <div class="fw-bold">{{ $t['name'] }}</div>
                                <div class="small text-muted-2">{{ $t['role'] }}</div>
                            </div>
                        </div>
                        <img src="{{ asset('images/marketing/testimonials/'.['property-manager','landlord','operations-lead'][$i].'-testimonial.webp') }}" alt="" class="feature-art testimonial-art" width="260" height="300" loading="lazy" decoding="async">
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@include('marketing.partials.faq', ['faqs' => $page['faqs'], 'faqTitle' => 'FlatCare FAQs: apartment and society management software'])

<section class="py-5">
    <div class="container">
        <div class="cta-band text-white text-center p-5 p-lg-6">
            <h2 class="fw-bold mb-3 position-relative">Ready to simplify apartment and society management?</h2>
            <p class="fs-5 mb-4 opacity-75 position-relative">Start a free FlatCare trial — our team helps you set up your society. See <a href="{{ route('marketing.pricing') }}" class="text-white">pricing</a> or <a href="{{ route('marketing.contact') }}" class="text-white">contact us</a>.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3 position-relative">
                <button type="button" class="btn btn-light btn-lg rounded-pill px-5 fw-semibold" data-bs-toggle="modal" data-bs-target="#trialInquiryModal">Create your free account</button>
                <a href="#how-it-works" class="btn btn-ghost-light btn-lg rounded-pill px-4">See how it works</a>
            </div>
            <p class="small opacity-75 mt-3 mb-0 position-relative"><i class="bi bi-shield-check"></i> No credit card required · Cancel anytime</p>
        </div>
    </div>
</section>
@endsection
