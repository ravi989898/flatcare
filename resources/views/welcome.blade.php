<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'FlatCare') }} — Effortless Property Care</title>
    <meta name="description" content="FlatCare helps landlords and tenants manage maintenance, payments and communication in one simple place.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --brand: #2f6f4f;
            --brand-dark: #1c3f2b;
            --brand-mid: #256345;
            --brand-light: #eaf5ee;
            --brand-soft: #f4faf6;
            --gold: #f0b429;
            --ink: #142019;
            --muted: #5d6b63;
            --ring: rgba(47,111,79,.14);
        }
        * { scroll-behavior: smooth; }
        body { font-family: 'Inter', system-ui, sans-serif; color: var(--ink); background: #fff; }
        h1, h2, h3, h4, h5, h6 { letter-spacing: -.02em; }
        .navbar-brand { font-weight: 800; letter-spacing: -.02em; }
        .text-brand { color: var(--brand); }
        .text-muted-2 { color: var(--muted); }
        .bg-brand-light { background: var(--brand-light); }
        .bg-brand-soft { background: var(--brand-soft); }

        /* Buttons */
        .btn-brand {
            background: linear-gradient(135deg, var(--brand-mid), var(--brand-dark));
            border: none; color: #fff;
            box-shadow: 0 .5rem 1.25rem -.4rem rgba(28,63,43,.5);
            transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
        }
        .btn-brand:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 .9rem 1.6rem -.4rem rgba(28,63,43,.55); filter: brightness(1.04); }
        .btn-outline-brand { border: 1.5px solid #d7e4db; color: var(--ink); background: #fff; transition: all .15s ease; }
        .btn-outline-brand:hover { border-color: var(--brand); color: var(--brand); background: var(--brand-soft); }
        .btn-ghost-light { border: 1.5px solid rgba(255,255,255,.35); color: #fff; background: rgba(255,255,255,.06); }
        .btn-ghost-light:hover { background: rgba(255,255,255,.16); color: #fff; border-color: rgba(255,255,255,.55); }

        /* Navbar */
        .navbar-glass {
            background: rgba(255,255,255,.78);
            backdrop-filter: blur(14px) saturate(160%);
            -webkit-backdrop-filter: blur(14px) saturate(160%);
            border-bottom: 1px solid rgba(20,32,25,.06);
        }
        .nav-link { font-weight: 500; color: var(--ink); position: relative; }
        .nav-link:hover { color: var(--brand); }

        /* Hero */
        .hero { position: relative; padding: 7.5rem 0 6rem; overflow: hidden; isolation: isolate; }
        .hero-mesh {
            position: absolute; inset: -10% -10% auto -10%; height: 130%; z-index: -1;
            background:
                radial-gradient(38rem 26rem at 88% -8%, rgba(240,180,41,.16), transparent 60%),
                radial-gradient(46rem 32rem at 8% 8%, rgba(47,111,79,.16), transparent 60%),
                radial-gradient(40rem 30rem at 60% 40%, rgba(37,99,69,.08), transparent 65%),
                linear-gradient(180deg, #fbfdfc, #ffffff 55%);
        }
        .hero-mesh::before {
            content: ''; position: absolute; inset: 0;
            background-image: radial-gradient(rgba(20,32,25,.05) 1px, transparent 1px);
            background-size: 22px 22px;
            -webkit-mask-image: radial-gradient(60rem 40rem at 75% 10%, #000 5%, transparent 70%);
            mask-image: radial-gradient(60rem 40rem at 75% 10%, #000 5%, transparent 70%);
        }
        .eyebrow-badge {
            display: inline-flex; align-items: center; gap: .5rem;
            background: #fff; border: 1px solid var(--ring); color: var(--brand-dark);
            font-weight: 600; font-size: .8rem; padding: .45rem .9rem;
            border-radius: 50rem; box-shadow: 0 .3rem .8rem -.3rem rgba(20,32,25,.08);
        }
        .eyebrow-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--gold); flex-shrink: 0; }
        .hero h1 { font-weight: 800; font-size: clamp(2.4rem, 4.2vw, 3.75rem); line-height: 1.06; }
        .hero .grad-text {
            background: linear-gradient(100deg, var(--brand-mid), var(--gold) 115%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .avatar-stack { display: flex; }
        .avatar-stack span {
            width: 34px; height: 34px; border-radius: 50%; border: 2.5px solid #fff;
            margin-left: -10px; display: flex; align-items: center; justify-content: center;
            font-size: .68rem; font-weight: 700; color: #fff;
        }
        .avatar-stack span:first-child { margin-left: 0; }

        /* Hero mockup */
        .hero-visual-wrap { position: relative; padding: .5rem 0 2.75rem 2.25rem; }
        .hero-visual-glow {
            position: absolute; width: 22rem; height: 22rem; border-radius: 50%;
            background: radial-gradient(circle, rgba(47,111,79,.22), transparent 70%);
            top: -2rem; right: -3rem; z-index: -1; filter: blur(4px);
        }
        .mock-panel {
            background: #fff; border-radius: 1.5rem; border: 1px solid rgba(20,32,25,.06);
            box-shadow: 0 2.5rem 4rem -1.5rem rgba(20,32,25,.28), 0 .5rem 1rem -.5rem rgba(20,32,25,.08);
            overflow: hidden;
        }
        .mock-topbar { background: var(--brand-dark); padding: .8rem 1.1rem; display: flex; align-items: center; gap: .4rem; }
        .mock-dot { width: 9px; height: 9px; border-radius: 50%; background: rgba(255,255,255,.35); }
        .mock-dot:first-child { background: var(--gold); }
        .mock-body { padding: 1.4rem; }
        .mock-row { height: 12px; border-radius: 6px; background: var(--brand-light); }
        .mock-bar-bg { height: 16px; border-radius: 8px; background: #eef2ef; overflow: hidden; }
        .mock-bar-fill { height: 100%; border-radius: 8px; background: linear-gradient(90deg, var(--brand-mid), #4c9d75); }
        .float-card {
            position: absolute; background: #fff; border-radius: 1.1rem;
            box-shadow: 0 1.5rem 2.5rem -1rem rgba(20,32,25,.22); border: 1px solid rgba(20,32,25,.05);
        }
        .float-card--stat { left: -2rem; bottom: -1.75rem; max-width: 250px; }
        .float-card--task { right: -1.5rem; top: 2.5rem; max-width: 210px; }
        .stat-chip { background: var(--brand-soft); border-radius: .7rem; padding: .55rem .5rem; }

        /* Sections */
        .section-eyebrow {
            display: inline-block; font-weight: 700; font-size: .72rem; letter-spacing: .12em;
            text-transform: uppercase; color: var(--brand); background: var(--brand-light);
            padding: .35rem .8rem; border-radius: 50rem; margin-bottom: .9rem;
        }
        .section-title { font-weight: 800; font-size: clamp(1.7rem, 2.6vw, 2.35rem); }

        /* Trust bar */
        .trust-bar { border-top: 1px solid #eef1ef; border-bottom: 1px solid #eef1ef; background: #fcfdfc; }
        .trust-mark { font-weight: 800; font-size: 1.05rem; color: #b7c2bb; letter-spacing: -.01em; opacity: .9; transition: opacity .15s ease, color .15s ease; }
        .trust-mark:hover { color: var(--brand); opacity: 1; }

        /* Feature cards */
        .card-feature {
            border: 1px solid #eef1ef; border-radius: 1.25rem; background: #fff;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            position: relative; overflow: hidden;
        }
        .card-feature:hover { transform: translateY(-6px); box-shadow: 0 1.5rem 2.5rem -1rem rgba(20,32,25,.14); border-color: transparent; }
        .card-feature .feature-index {
            position: absolute; top: 1.1rem; right: 1.3rem; font-weight: 800; font-size: .85rem;
            color: #d6e0da;
        }
        .feature-icon {
            width: 54px; height: 54px; border-radius: 1rem;
            background: linear-gradient(135deg, var(--brand-light), #dcefe3); color: var(--brand-dark);
            display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
        }

        /* Steps */
        .step-card { background: #fff; border: 1px solid #eef1ef; border-radius: 1.25rem; height: 100%; position: relative; }
        .step-num {
            width: 44px; height: 44px; border-radius: 50%;
            background: linear-gradient(135deg, var(--brand-mid), var(--brand-dark)); color: #fff;
            display: flex; align-items: center; justify-content: center; font-weight: 800; flex-shrink: 0;
            box-shadow: 0 .5rem 1rem -.3rem rgba(28,63,43,.4);
        }
        .step-connector { position: absolute; top: 22px; left: calc(50% + 22px); width: calc(100% - 44px); height: 2px; background: repeating-linear-gradient(90deg, #cfe0d6 0 8px, transparent 8px 14px); }

        /* Stats band */
        .stats-band {
            background: linear-gradient(135deg, var(--brand-dark), var(--brand-mid) 65%, #2c7350);
            border-radius: 1.75rem; color: #fff; position: relative; overflow: hidden;
        }
        .stats-band::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(30rem 20rem at 90% 0%, rgba(240,180,41,.18), transparent 60%);
        }
        .stat-num { font-weight: 800; font-size: clamp(1.9rem, 3vw, 2.7rem); }

        /* Testimonials */
        .quote-mark { font-family: Georgia, serif; font-size: 3rem; line-height: 1; color: var(--brand); opacity: .18; }
        .avatar-badge {
            width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(135deg, var(--brand-mid), var(--brand-dark)); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .95rem;
        }
        .stars { color: var(--gold); font-size: .85rem; letter-spacing: .1em; }

        /* CTA */
        .cta-band {
            background: linear-gradient(135deg, var(--brand-dark), var(--brand-mid));
            border-radius: 1.75rem; position: relative; overflow: hidden;
        }
        .cta-band::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(34rem 22rem at 15% 110%, rgba(240,180,41,.16), transparent 60%);
        }

        footer { background: #0f1a13; color: #aebbb2; }
        footer a { color: #d9e4dc; text-decoration: none; }
        footer a:hover { color: #fff; }
        footer .footer-brand-text { color: #fff; }

        @media (max-width: 991.98px) {
            .float-card--stat { position: static; margin-top: -2.5rem; margin-inline: 1rem; max-width: none; }
            .float-card--task { display: none; }
            .step-connector { display: none; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light navbar-glass sticky-top py-3">
    <div class="container">
        <a class="navbar-brand text-brand" href="{{ route('home') }}">
            @include('partials.brand')
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link px-3" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="#how-it-works">How it works</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="#testimonials">Testimonials</a></li>
            </ul>
            <div class="d-flex gap-2">
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-brand rounded-pill px-4">Go to Dashboard</a>
                    @endif
                    <form action="{{ route('logout') }}" method="post" class="m-0">
                        @csrf
                        <button class="btn btn-outline-brand rounded-pill px-4" type="submit">Sign out</button>
                    </form>
                @else
                    <button type="button" class="btn btn-brand rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#trialInquiryModal">Get started</button>
                @endauth
            </div>
        </div>
    </div>
</nav>

<header class="hero">
    <div class="hero-mesh"></div>
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="eyebrow-badge mb-4"><span class="eyebrow-dot"></span> Built for landlords &amp; property managers</span>
                <h1 class="mb-4">Effortless property care, <span class="grad-text">all in one place.</span></h1>
                <p class="fs-5 text-muted-2 mb-4">
                    FlatCare brings maintenance requests, rent tracking and tenant communication
                    together — so nothing falls through the cracks and every flat gets the care it deserves.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <button type="button" class="btn btn-brand btn-lg rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#trialInquiryModal">Start free trial <i class="bi bi-arrow-right ms-1"></i></button>
                    <a href="#how-it-works" class="btn btn-outline-brand btn-lg rounded-pill px-4">See how it works</a>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-stack">
                        <span style="background:#2f6f4f;">AM</span>
                        <span style="background:#4c9d75;">SL</span>
                        <span style="background:#f0b429; color:#1c3f2b;">DC</span>
                    </div>
                    <div class="small text-muted-2">
                        <span class="stars">★★★★★</span> Loved by <strong class="text-ink">20+</strong> property teams
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-visual-wrap">
                    <div class="hero-visual-glow"></div>
                    <div class="mock-panel">
                        <div class="mock-topbar">
                            <div class="mock-dot"></div><div class="mock-dot"></div><div class="mock-dot"></div>
                        </div>
                        <div class="mock-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="fw-bold">Portfolio overview</div>
                                <span class="badge bg-success-subtle text-success rounded-pill px-3">Live</span>
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
                                <div class="col-4"><div class="ratio ratio-1x1 rounded-3" style="background:var(--brand-light);"></div></div>
                                <div class="col-4"><div class="ratio ratio-1x1 rounded-3" style="background:#eef2ef;"></div></div>
                                <div class="col-4"><div class="ratio ratio-1x1 rounded-3" style="background:var(--brand-light);"></div></div>
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
                            <div>Leaky faucet resolved <span class="text-muted-2">2h ago</span></div>
                        </div>
                    </div>

                    <div class="float-card float-card--task p-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-bell-fill text-brand"></i>
                            <div class="fw-bold small">Rent reminder sent</div>
                        </div>
                        <div class="small text-muted-2">Block B &middot; 18 tenants notified</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<section id="features" class="py-5 py-lg-6 my-4">
    <div class="container">
        <div class="text-center mb-5 mx-auto" style="max-width:640px;">
            <span class="section-eyebrow">Features</span>
            <h2 class="section-title mb-3">Everything you need to manage your properties</h2>
            <p class="text-muted-2 fs-5">One dashboard for maintenance, payments and people — built to feel effortless.</p>
        </div>
        <div class="row g-4">
            @foreach ([
                ['icon' => 'bi-tools', 'title' => 'Maintenance tracking', 'text' => 'Tenants submit requests with photos; you assign, track and close them out in a click.'],
                ['icon' => 'bi-credit-card', 'title' => 'Rent & payments', 'text' => 'Automated reminders, online payments and a clear ledger for every unit.'],
                ['icon' => 'bi-chat-dots', 'title' => 'Built-in messaging', 'text' => 'Keep every conversation with tenants and vendors organized by property.'],
                ['icon' => 'bi-graph-up', 'title' => 'Reports & insights', 'text' => 'See occupancy, spend and response times at a glance.'],
                ['icon' => 'bi-shield-lock', 'title' => 'Secure by default', 'text' => 'Role-based access, encrypted sessions and audit trails out of the box.'],
                ['icon' => 'bi-phone', 'title' => 'Works everywhere', 'text' => 'A responsive dashboard your team and tenants can use from any device.'],
            ] as $i => $f)
                <div class="col-md-6 col-lg-4">
                    <div class="card card-feature h-100 p-4">
                        <span class="feature-index">{{ sprintf('%02d', $i + 1) }}</span>
                        <div class="feature-icon mb-3"><i class="bi {{ $f['icon'] }}"></i></div>
                        <h5 class="fw-bold">{{ $f['title'] }}</h5>
                        <p class="text-muted-2 mb-0">{{ $f['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
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
                <span class="section-eyebrow">Built for busy teams</span>
                <h2 class="section-title mb-3">See every property at a glance</h2>
                <p class="text-muted-2 fs-5 mb-4">
                    From a single dashboard, track open tickets, upcoming visits and rent status across
                    every building you manage — no more spreadsheets or scattered messages.
                </p>
                <ul class="list-unstyled d-flex flex-column gap-3">
                    <li class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill text-brand mt-1"></i><span>Real-time maintenance status</span></li>
                    <li class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill text-brand mt-1"></i><span>Automated rent reminders</span></li>
                    <li class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill text-brand mt-1"></i><span>One inbox for every tenant conversation</span></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section id="how-it-works" class="py-5 py-lg-6 bg-brand-soft my-4">
    <div class="container py-4">
        <div class="text-center mb-5 mx-auto" style="max-width:560px;">
            <span class="section-eyebrow">How it works</span>
            <h2 class="section-title">Up and running in three steps</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 position-relative">
                <div class="step-connector d-none d-lg-block"></div>
                <div class="step-card p-4">
                    <div class="step-num mb-3">1</div>
                    <h5 class="fw-bold">Add your properties</h5>
                    <p class="text-muted-2 mb-0">Import units and invite tenants in minutes.</p>
                </div>
            </div>
            <div class="col-lg-4 position-relative">
                <div class="step-connector d-none d-lg-block"></div>
                <div class="step-card p-4">
                    <div class="step-num mb-3">2</div>
                    <h5 class="fw-bold">Automate the busywork</h5>
                    <p class="text-muted-2 mb-0">Rent reminders and maintenance routing happen on their own.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="step-card p-4">
                    <div class="step-num mb-3">3</div>
                    <h5 class="fw-bold">Track everything</h5>
                    <p class="text-muted-2 mb-0">One dashboard shows exactly what needs your attention.</p>
                </div>
            </div>
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
        <div class="text-center mb-5 mx-auto" style="max-width:560px;">
            <span class="section-eyebrow">Testimonials</span>
            <h2 class="section-title">Trusted by property teams</h2>
        </div>
        <div class="row g-4">
            @foreach ([
                ['quote' => 'FlatCare cut our maintenance response time in half. Tenants actually notice the difference.', 'name' => 'Aarav Mehta', 'role' => 'Property Manager, 40 units'],
                ['quote' => 'Rent collection used to be our biggest headache. Now it basically runs itself.', 'name' => 'Sneha Kapoor', 'role' => 'Landlord, 12 units'],
                ['quote' => 'Simple enough for tenants, powerful enough for our whole ops team.', 'name' => 'Rohan Verma', 'role' => 'Operations Lead, Skyline Homes'],
            ] as $t)
                <div class="col-lg-4">
                    <div class="card card-feature h-100 p-4">
                        <div class="stars mb-2">★★★★★</div>
                        <div class="quote-mark">&ldquo;</div>
                        <p class="mb-4 mt-n2">{{ $t['quote'] }}</p>
                        <div class="mt-auto d-flex align-items-center gap-3">
                            <div class="avatar-badge">{{ collect(explode(' ', $t['name']))->map(fn ($p) => mb_substr($p, 0, 1))->join('') }}</div>
                            <div>
                                <div class="fw-bold">{{ $t['name'] }}</div>
                                <div class="small text-muted-2">{{ $t['role'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="cta-band text-white text-center p-5 p-lg-6">
            <h2 class="fw-bold mb-3 position-relative">Ready to simplify property management?</h2>
            <p class="fs-5 mb-4 opacity-75 position-relative">Join FlatCare today — set up takes less than five minutes.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3 position-relative">
                <button type="button" class="btn btn-light btn-lg rounded-pill px-5 fw-semibold" data-bs-toggle="modal" data-bs-target="#trialInquiryModal">Create your free account</button>
                <a href="#how-it-works" class="btn btn-ghost-light btn-lg rounded-pill px-4">See how it works</a>
            </div>
            <p class="small opacity-75 mt-3 mb-0 position-relative"><i class="bi bi-shield-check"></i> No credit card required · Cancel anytime</p>
        </div>
    </div>
</section>

<footer class="pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="fs-4 fw-bold footer-brand-text mb-2">@include('partials.brand')</div>
                <p class="small mb-0">Effortless property care for landlords, managers and tenants.</p>
            </div>
            <div class="col-lg-2 col-6">
                <h6 class="footer-brand-text">Product</h6>
                <ul class="list-unstyled small">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How it works</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-6">
                <h6 class="footer-brand-text">Account</h6>
                <ul class="list-unstyled small">
                    <li><a href="#" data-bs-toggle="modal" data-bs-target="#trialInquiryModal">Start free trial</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="footer-brand-text">Stay in touch</h6>
                <p class="small mb-0">hello@flatcare.test</p>
            </div>
        </div>
        <hr class="border-secondary my-4">
        <div class="d-flex justify-content-between flex-wrap small">
            <span>&copy; {{ date('Y') }} FlatCare. All rights reserved.</span>
            <span>Made with care.</span>
        </div>
    </div>
</footer>

{{-- "Start free trial" doesn't self-register an account — it sends a lead
     to Super Admin (Admin > Inquiries), who reaches out to set the society
     up. --}}
<div class="modal fade" id="trialInquiryModal" tabindex="-1" aria-labelledby="trialInquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            @if (session('trial_inquiry_sent'))
                <div class="modal-body p-5 text-center">
                    <div class="feature-icon mx-auto mb-3" style="font-size:1.8rem;"><i class="bi bi-check-lg"></i></div>
                    <h4 class="fw-bold mb-2">Thanks — we've got it!</h4>
                    <p class="text-muted-2 mb-4">Our team will reach out shortly to set up your society on FlatCare.</p>
                    <button type="button" class="btn btn-brand rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                </div>
            @else
                <div class="modal-header border-0 px-4 pt-4">
                    <div>
                        <h5 class="modal-title fw-bold" id="trialInquiryModalLabel">Start your free trial</h5>
                        <p class="small text-muted-2 mb-0">Tell us a bit about your society — our team will reach out to set you up.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('trial_inquiries.store') }}" method="POST" class="modal-body px-4 pb-4 pt-2">
                    @csrf
                    <div class="mb-3">
                        <label for="society_name" class="form-label small fw-semibold">Society name</label>
                        <input type="text" name="society_name" id="society_name" value="{{ old('society_name') }}" class="form-control @error('society_name') is-invalid @enderror" required>
                        @error('society_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="contact_name" class="form-label small fw-semibold">Your name</label>
                        <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name') }}" class="form-control @error('contact_name') is-invalid @enderror" required>
                        @error('contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="email" class="form-label small fw-semibold">Email address</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="phone" class="form-label small fw-semibold">Mobile number</label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" required>
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="address" class="form-label small fw-semibold">Address</label>
                        <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror" required>{{ old('address') }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-brand btn-lg w-100 rounded-pill">Request my free trial</button>
                    <p class="small text-muted-2 text-center mt-3 mb-0"><i class="bi bi-shield-check"></i> No credit card required · We'll contact you within one business day</p>
                </form>
            @endif
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@if ($errors->any() || session('trial_inquiry_sent'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(document.getElementById('trialInquiryModal')).show();
        });
    </script>
@endif
</body>
</html>
