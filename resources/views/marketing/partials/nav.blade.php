{{-- Public site navigation. Same look as before; links now also reach the SEO pages. --}}
<a class="visually-hidden-focusable position-absolute p-2 bg-white" href="#main">Skip to content</a>

<nav class="navbar navbar-expand-lg navbar-light navbar-glass sticky-top py-3" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand text-brand" href="{{ route('home') }}" aria-label="FlatCare – apartment and society management software">
            @include('partials.brand')
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link px-3" href="{{ route('marketing.features') }}">Features</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="{{ route('home') }}#how-it-works">How it works</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link px-3 dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Solutions</a>
                    <ul class="dropdown-menu border-0 shadow">
                        <li><a class="dropdown-item" href="{{ route('marketing.apartment-management-software') }}">Apartment management software</a></li>
                        <li><a class="dropdown-item" href="{{ route('marketing.society-management-software') }}">Society management software</a></li>
                        <li><a class="dropdown-item" href="{{ route('marketing.society-maintenance-billing') }}">Maintenance billing</a></li>
                        <li><a class="dropdown-item" href="{{ route('marketing.society-accounting-software') }}">Society accounting</a></li>
                        <li><a class="dropdown-item" href="{{ route('marketing.society-management-app') }}">Society management app</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link px-3" href="{{ route('marketing.pricing') }}">Pricing</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="{{ route('marketing.about') }}">About</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="{{ route('marketing.contact') }}">Contact</a></li>
            </ul>
            <div class="d-flex gap-2">
                <a href="{{ config('flatcare.apk_url') ?? '/downloads/flatcare-app.apk' }}" class="btn btn-outline-brand" rel="nofollow">
                    <i class="bi bi-download"></i> Download App
                </a>
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
