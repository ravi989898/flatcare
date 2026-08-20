<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — {{ $currentSociety->name ?? 'FlatCare' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root { --brand: #2f6f4f; --brand-dark: #234f38; --brand-light: #eaf5ee; --ink: #17241d; }
        body { font-family: 'Inter', system-ui, sans-serif; color: var(--ink); background: #f7f9f8; }
        .navbar-brand { font-weight: 800; letter-spacing: -.02em; color: var(--brand) !important; }
        .nav-link.active { color: var(--brand) !important; font-weight: 600; }
        .stat-card { border: 1px solid #eceff1; border-radius: 1rem; }
        .stat-icon {
            width: 48px; height: 48px; border-radius: .8rem; background: var(--brand-light); color: var(--brand);
            display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
        }
        .btn-brand { background: var(--brand); border-color: var(--brand); color: #fff; }
        .btn-brand:hover { background: var(--brand-dark); border-color: var(--brand-dark); color: #fff; }
        a { color: var(--brand); }
    </style>
    @stack('styles')
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand" href="{{ route('society.dashboard') }}">
            @include('partials.brand')
        </a>
        <div class="collapse navbar-collapse">
            {{-- Built from the logged-in user's role: Super Admin configures which
                 items each role sees under Settings -> Menu Settings. --}}
            <ul class="navbar-nav me-auto ms-4">
                @foreach (($visibleMenuItems ?? []) as $item)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs($item->active_pattern) || request()->routeIs($item->active_pattern . '.*') ? 'active' : '' }}"
                           href="{{ route($item->route_name) }}">
                            @if ($item->icon)
                                <i class="bi {{ $item->icon }}"></i>
                            @endif
                            {{ $item->label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="ms-auto d-flex align-items-center">
            <span class="text-muted me-3 d-none d-sm-inline">{{ $currentSociety->name ?? '' }}</span>
            <form action="{{ route('society.logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Sign out
                </button>
            </form>
        </div>
    </div>
</nav>

<div class="container py-4">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
