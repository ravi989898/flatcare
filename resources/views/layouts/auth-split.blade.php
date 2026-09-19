{{--
    Shared split-screen sign-in shell for BOTH portals (super-admin login and
    society-portal login): illustrated brand panel on the left, sign-in card
    on the right. The forms themselves live in auth/login.blade.php and
    society/auth/login.blade.php and keep their own routes/fields/guards.

    No inline JS (the CSP forbids it): the show/hide-password button is driven
    by public/js/login-ui.js through data-toggle-password.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('page_title', 'Sign in') — {{ config('app.name', 'FlatCare') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">

    <style>
        :root {
            --brand: #157a3e;
            --brand-dark: #0f6631;
            --ink: #0f2a3d;
            --muted: #5d6b74;
            --line: #dfe7e2;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            color: var(--ink);
            min-height: 100vh;
            background:
                radial-gradient(60rem 40rem at 100% 0%, rgba(120, 200, 150, .18), transparent 60%),
                linear-gradient(135deg, #fbfefc 0%, #eef8f2 100%);
            position: relative;
            overflow-x: hidden;
        }

        /* Illustration: buildings + trees fading into the page background. */
        .scene {
            position: fixed; left: 0; bottom: 0; width: min(52vw, 780px, calc(36vh * 728 / 416)); aspect-ratio: 728 / 416;
            background: url('{{ asset('images/marketing/login-scene.jpg') }}') left bottom / cover no-repeat;
            mix-blend-mode: multiply; pointer-events: none;
            -webkit-mask-image: linear-gradient(to top, #000 55%, transparent 100%);
            mask-image: linear-gradient(to top, #000 55%, transparent 100%);
        }

        .shell {
            position: relative; z-index: 1; min-height: 100vh;
            max-width: 1240px; margin: 0 auto; padding: 2.5rem 1.5rem;
            display: grid; grid-template-columns: 1.05fr 1fr; gap: 3rem; align-items: center;
        }

        /* Left brand panel */
        .brand-panel { align-self: start; padding-top: 1rem; }
        .brand-panel .brand img { height: 56px; width: auto; }
        .brand-panel h1 { font-size: clamp(2.2rem, 4.4vw, 3.4rem); line-height: 1.08; font-weight: 800; margin: 2.75rem 0 1rem; letter-spacing: -.02em; }
        .brand-panel h1 span { color: var(--brand); display: block; }
        .brand-panel p.lead { font-size: 1.1rem; color: var(--muted); max-width: 30rem; line-height: 1.55; margin: 0 0 2rem; }
        .perks { display: flex; flex-wrap: wrap; gap: 1.4rem; }
        .perk { width: 5.6rem; text-align: center; font-size: .8rem; color: var(--ink); line-height: 1.25; }
        .perk i {
            display: flex; align-items: center; justify-content: center; width: 3.6rem; height: 3.6rem;
            border-radius: 50%; margin: 0 auto .5rem; font-size: 1.5rem;
            box-shadow: 0 .6rem 1.2rem -.6rem rgba(15, 42, 61, .35);
        }
        .perk .p1 { background: #e3f4e8; color: #1c8a4a; }
        .perk .p2 { background: #e5eefc; color: #2f6fd6; }
        .perk .p3 { background: #fdeed9; color: #e08a1a; }
        .perk .p4 { background: #fde5e7; color: #d64550; }

        /* Sign-in card */
        .card-wrap { display: flex; justify-content: center; }
        .signin-card {
            width: 100%; max-width: 520px; background: #fff; border-radius: 1.75rem;
            padding: 2.4rem 2.5rem 2rem; position: relative; overflow: hidden;
            box-shadow: 0 2rem 4rem -1.5rem rgba(15, 60, 40, .25), 0 .25rem .75rem rgba(15, 60, 40, .05);
        }
        .signin-card::before {
            content: ''; position: absolute; top: -3.5rem; right: -3.5rem; width: 11rem; height: 11rem; border-radius: 50%;
            background: radial-gradient(circle at 40% 40%, #dff3e6, #eef9f2 70%); pointer-events: none;
        }
        .signin-card > * { position: relative; }
        .card-logo { text-align: center; }
        .card-logo img { height: 52px; width: auto; }
        .card-logo + h2 { text-align: left; }
        .signin-card h2 { font-size: 1.75rem; font-weight: 800; margin: 1.5rem 0 .4rem; letter-spacing: -.01em; }
        .signin-card .sub { color: var(--muted); margin: 0 0 1.6rem; line-height: 1.5; }

        .field { position: relative; margin-bottom: 1rem; }
        .field > i.lead-icon { position: absolute; left: 1rem; top: 1.7rem; transform: translateY(-50%); font-size: 1.15rem; color: #5b6770; pointer-events: none; }
        .field input[type="email"], .field input[type="text"], .field input[type="password"] {
            width: 100%; height: 3.4rem; border: 1px solid var(--line); border-radius: .9rem; background: #fff;
            padding: 0 3rem 0 3rem; font: inherit; font-size: 1rem; color: var(--ink);
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .field input::placeholder { color: #94a0a8; }
        .field input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 .2rem rgba(21, 122, 62, .15); }
        .field input.is-invalid { border-color: #d64550; }
        .toggle-pass { position: absolute; right: .4rem; top: 1.7rem; transform: translateY(-50%); width: 2.6rem; height: 2.6rem; border: 0; background: transparent; color: #5b6770; font-size: 1.2rem; cursor: pointer; border-radius: .6rem; }
        .toggle-pass:hover { color: var(--brand); background: #f1f7f3; }
        .err { display: block; color: #c53a45; font-size: .85rem; font-weight: 600; margin-top: .35rem; }

        .row-opts { display: flex; align-items: center; justify-content: space-between; margin: .4rem 0 1.5rem; gap: 1rem; }
        .remember { display: inline-flex; align-items: center; gap: .6rem; cursor: pointer; font-size: .98rem; user-select: none; }
        .remember input { width: 1.25rem; height: 1.25rem; accent-color: var(--brand); margin: 0; }

        .btn-signin {
            width: 100%; height: 3.4rem; border: 0; border-radius: 999px; cursor: pointer;
            background: linear-gradient(135deg, #1a8f47, var(--brand-dark)); color: #fff; font: inherit; font-size: 1.1rem; font-weight: 700;
            display: inline-flex; align-items: center; justify-content: center; gap: .6rem;
            box-shadow: 0 .8rem 1.4rem -.6rem rgba(15, 102, 49, .6); transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
        }
        .btn-signin:hover { transform: translateY(-1px); filter: brightness(1.05); }
        .btn-signin:focus-visible { outline: 3px solid rgba(21, 122, 62, .35); outline-offset: 2px; }

        .or { display: flex; align-items: center; gap: 1rem; color: #8a969e; margin: 1.6rem 0 1.1rem; font-size: .9rem; }
        .or::before, .or::after { content: ''; flex: 1; height: 1px; background: var(--line); }
        .links a { color: var(--brand); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: .5rem; }
        .links a:hover { text-decoration: underline; }
        .links p { margin: 0 0 .55rem; }

        .alert { padding: .8rem 1rem; border-radius: .8rem; font-size: .92rem; margin-bottom: 1rem; }
        .alert-danger { background: #fdecee; color: #a72a35; border: 1px solid #f6c7cc; }
        .alert-success { background: #e9f7ee; color: #1c6b3a; border: 1px solid #c4e8d0; }

        @media (max-width: 991.98px) {
            .shell { grid-template-columns: 1fr; gap: 1.5rem; padding: 1.25rem 1rem 2rem; }
            .brand-panel { text-align: center; padding-top: 0; }
            .brand-panel h1, .brand-panel p.lead, .perks { display: none; }
            .brand-panel .brand { display: none; }
            .scene { width: 100vw; opacity: .55; }
            .signin-card { padding: 1.8rem 1.4rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="scene" aria-hidden="true"></div>

    <main class="shell">
        <section class="brand-panel" aria-hidden="false">
            <a class="brand" href="{{ route('home') }}" aria-label="FlatCare home">@include('partials.brand', ['height' => '56px'])</a>
            <h1>Simplifying <span>Society Living</span></h1>
            <p class="lead">Manage your society, residents, visitors, maintenance and more — all in one place.</p>
            <div class="perks">
                <div class="perk"><i class="bi bi-people-fill p1"></i>Manage Residents</div>
                <div class="perk"><i class="bi bi-wrench-adjustable p2"></i>Track Maintenance</div>
                <div class="perk"><i class="bi bi-credit-card-2-front-fill p3"></i>Collect Payments</div>
                <div class="perk"><i class="bi bi-shield-check p4"></i>Secure &amp; Reliable</div>
            </div>
        </section>

        <section class="card-wrap">
            <div class="signin-card">
                <div class="card-logo">@include('partials.brand', ['height' => '52px'])</div>
                <h2>@yield('card_title')</h2>
                <p class="sub">@yield('card_subtitle')</p>

                @if (session('error'))
                    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                @endif
                @if (session('success') || session('status'))
                    <div class="alert alert-success" role="status">{{ session('success') ?? session('status') }}</div>
                @endif

                @yield('form')

                <div class="or">or</div>
                <div class="links">
                    @yield('extra_links')
                    <p><a href="{{ route('home') }}"><i class="bi bi-arrow-left"></i> Back to homepage</a></p>
                </div>
            </div>
        </section>
    </main>

    <script src="{{ asset('js/login-ui.js') }}"></script>
</body>
</html>
