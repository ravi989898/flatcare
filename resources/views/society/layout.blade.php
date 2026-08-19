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
            <ul class="navbar-nav me-auto ms-4">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('society.dashboard') ? 'active' : '' }}" href="{{ route('society.dashboard') }}">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('society.maintenance.*') ? 'active' : '' }}" href="{{ route('society.maintenance.index') }}">Maintenance</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('society.visitors.*') ? 'active' : '' }}" href="{{ route('society.visitors.index') }}">Visitors</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('society.complaints.*') ? 'active' : '' }}" href="{{ route('society.complaints.index') }}">Complaints</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('society.directory.*') ? 'active' : '' }}" href="{{ route('society.directory.index') }}">Directory</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('society.announcements.*') ? 'active' : '' }}" href="{{ route('society.announcements.index') }}">Announcements</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('society.events.*') ? 'active' : '' }}" href="{{ route('society.events.index') }}">Events</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('society.elections.*') ? 'active' : '' }}" href="{{ route('society.elections.index') }}">Elections</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('society.payments.*') ? 'active' : '' }}" href="{{ route('society.payments.index') }}">Payments</a>
                </li>
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
