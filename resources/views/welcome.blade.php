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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --brand: #2f6f4f;
            --brand-dark: #234f38;
            --brand-light: #eaf5ee;
            --ink: #17241d;
        }
        body { font-family: 'Inter', system-ui, sans-serif; color: var(--ink); }
        .navbar-brand { font-weight: 800; letter-spacing: -.02em; }
        .btn-brand { background: var(--brand); border-color: var(--brand); color: #fff; }
        .btn-brand:hover { background: var(--brand-dark); border-color: var(--brand-dark); color: #fff; }
        .btn-outline-brand { border-color: var(--brand); color: var(--brand); }
        .btn-outline-brand:hover { background: var(--brand); color: #fff; }
        .text-brand { color: var(--brand); }
        .bg-brand-light { background: var(--brand-light); }
        .hero { padding: 6rem 0 5rem; background: radial-gradient(circle at top right, var(--brand-light), #fff 60%); }
        .hero h1 { font-weight: 800; letter-spacing: -.03em; font-size: clamp(2.2rem, 4vw, 3.4rem); }
        .feature-icon {
            width: 52px; height: 52px; border-radius: .9rem;
            background: var(--brand-light); color: var(--brand);
            display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
        }
        .card-feature { border: 1px solid #eceff1; border-radius: 1rem; transition: transform .15s ease, box-shadow .15s ease; }
        .card-feature:hover { transform: translateY(-4px); box-shadow: 0 1rem 2rem rgba(0,0,0,.06); }
        .step-num {
            width: 36px; height: 36px; border-radius: 50%; background: var(--brand); color: #fff;
            display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;
        }
        .cta-band { background: linear-gradient(135deg, var(--brand), var(--brand-dark)); border-radius: 1.5rem; }
        footer { background: #14201a; color: #cbd7cf; }
        footer a { color: #e7efe9; text-decoration: none; }
        footer a:hover { color: #fff; }
        .quote-mark { font-size: 2.5rem; line-height: 1; color: var(--brand); opacity: .3; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand text-brand" href="{{ route('home') }}">
            @include('partials.brand')
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#how-it-works">How it works</a></li>
                <li class="nav-item"><a class="nav-link" href="#testimonials">Testimonials</a></li>
            </ul>
            <div class="d-flex gap-2">
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-brand">Go to Dashboard</a>
                    @endif
                    <form action="{{ route('logout') }}" method="post" class="m-0">
                        @csrf
                        <button class="btn btn-outline-brand" type="submit">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-brand">Sign in</a>
                    <a href="{{ route('register') }}" class="btn btn-brand">Get started</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<header class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-brand-light text-brand mb-3 px-3 py-2 rounded-pill">Built for landlords &amp; property managers</span>
                <h1 class="mb-4">Effortless property care, all in one place.</h1>
                <p class="fs-5 text-secondary mb-4">
                    FlatCare brings maintenance requests, rent tracking and tenant communication
                    together — so nothing falls through the cracks and every flat gets the care it deserves.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn btn-brand btn-lg px-4">Start free trial</a>
                    <a href="#how-it-works" class="btn btn-outline-brand btn-lg px-4">See how it works</a>
                </div>
                <p class="text-muted small mt-3 mb-0"><i class="bi bi-shield-check"></i> No credit card required · Cancel anytime</p>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-body p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold mb-0">This week's overview</h6>
                            <span class="badge bg-success-subtle text-success">Live</span>
                        </div>
                        <div class="row g-3 text-center">
                            <div class="col-4">
                                <div class="border rounded-3 p-3">
                                    <div class="fs-3 fw-bold text-brand">24</div>
                                    <div class="small text-muted">Open tickets</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded-3 p-3">
                                    <div class="fs-3 fw-bold text-brand">98%</div>
                                    <div class="small text-muted">On-time rent</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded-3 p-3">
                                    <div class="fs-3 fw-bold text-brand">4.9</div>
                                    <div class="small text-muted">Tenant rating</div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <div class="small">Leaky faucet — Unit 3B <span class="text-muted">resolved 2h ago</span></div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-clock-fill text-warning fs-5"></i>
                            <div class="small">AC service — Unit 5A <span class="text-muted">scheduled tomorrow</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<section id="features" class="py-5 py-lg-6 my-4">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Everything you need to manage your properties</h2>
            <p class="text-secondary fs-5">One dashboard for maintenance, payments and people.</p>
        </div>
        <div class="row g-4">
            @foreach ([
                ['icon' => 'bi-tools', 'title' => 'Maintenance tracking', 'text' => 'Tenants submit requests with photos; you assign, track and close them out in a click.'],
                ['icon' => 'bi-credit-card', 'title' => 'Rent & payments', 'text' => 'Automated reminders, online payments and a clear ledger for every unit.'],
                ['icon' => 'bi-chat-dots', 'title' => 'Built-in messaging', 'text' => 'Keep every conversation with tenants and vendors organized by property.'],
                ['icon' => 'bi-graph-up', 'title' => 'Reports & insights', 'text' => 'See occupancy, spend and response times at a glance.'],
                ['icon' => 'bi-shield-lock', 'title' => 'Secure by default', 'text' => 'Role-based access, encrypted sessions and audit trails out of the box.'],
                ['icon' => 'bi-phone', 'title' => 'Works everywhere', 'text' => 'A responsive dashboard your team and tenants can use from any device.'],
            ] as $f)
                <div class="col-md-6 col-lg-4">
                    <div class="card card-feature h-100 p-4">
                        <div class="feature-icon mb-3"><i class="bi {{ $f['icon'] }}"></i></div>
                        <h5 class="fw-bold">{{ $f['title'] }}</h5>
                        <p class="text-secondary mb-0">{{ $f['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="how-it-works" class="py-5 py-lg-6 bg-brand-light my-4">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Up and running in three steps</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="d-flex gap-3">
                    <div class="step-num">1</div>
                    <div>
                        <h5 class="fw-bold">Add your properties</h5>
                        <p class="text-secondary mb-0">Import units and invite tenants in minutes.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="d-flex gap-3">
                    <div class="step-num">2</div>
                    <div>
                        <h5 class="fw-bold">Automate the busywork</h5>
                        <p class="text-secondary mb-0">Rent reminders and maintenance routing happen on their own.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="d-flex gap-3">
                    <div class="step-num">3</div>
                    <div>
                        <h5 class="fw-bold">Track everything</h5>
                        <p class="text-secondary mb-0">One dashboard shows exactly what needs your attention.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="testimonials" class="py-5 py-lg-6 my-4">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Trusted by property teams</h2>
        </div>
        <div class="row g-4">
            @foreach ([
                ['quote' => 'FlatCare cut our maintenance response time in half. Tenants actually notice the difference.', 'name' => 'Aarav Mehta', 'role' => 'Property Manager, 40 units'],
                ['quote' => 'Rent collection used to be our biggest headache. Now it basically runs itself.', 'name' => 'Sara Lim', 'role' => 'Landlord, 12 units'],
                ['quote' => 'Simple enough for tenants, powerful enough for our whole ops team.', 'name' => 'Daniel Cruz', 'role' => 'Operations Lead, Skyline Homes'],
            ] as $t)
                <div class="col-lg-4">
                    <div class="card card-feature h-100 p-4">
                        <div class="quote-mark">&ldquo;</div>
                        <p class="mb-4">{{ $t['quote'] }}</p>
                        <div class="mt-auto">
                            <div class="fw-bold">{{ $t['name'] }}</div>
                            <div class="small text-muted">{{ $t['role'] }}</div>
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
            <h2 class="fw-bold mb-3">Ready to simplify property management?</h2>
            <p class="fs-5 mb-4 opacity-75">Join FlatCare today — set up takes less than five minutes.</p>
            <a href="{{ route('register') }}" class="btn btn-light btn-lg px-5 fw-semibold">Create your free account</a>
        </div>
    </div>
</section>

<footer class="pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="fs-4 fw-bold text-white mb-2">@include('partials.brand')</div>
                <p class="small mb-0">Effortless property care for landlords, managers and tenants.</p>
            </div>
            <div class="col-lg-2 col-6">
                <h6 class="text-white">Product</h6>
                <ul class="list-unstyled small">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How it works</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-6">
                <h6 class="text-white">Account</h6>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('login') }}">Sign in</a></li>
                    <li><a href="{{ route('register') }}">Register</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="text-white">Stay in touch</h6>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
