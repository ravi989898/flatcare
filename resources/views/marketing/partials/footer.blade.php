@php($contact = \App\Models\PlatformSetting::contact())
<footer class="pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="fs-4 fw-bold footer-brand-text mb-2">@include('partials.brand')</div>
                <p class="small mb-2">FlatCare is apartment and society management software for housing societies, apartment buildings and residential communities in India.</p>
                <p class="small mb-0">Maintenance billing, payments, residents, visitors and complaints in one web dashboard and mobile app.</p>
            </div>
            <div class="col-lg-3 col-6">
                <h2 class="h6 footer-brand-text">Solutions</h2>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('marketing.apartment-management-software') }}">Apartment management software</a></li>
                    <li><a href="{{ route('marketing.society-management-software') }}">Society management software</a></li>
                    <li><a href="{{ route('marketing.society-maintenance-software') }}">Society maintenance software</a></li>
                    <li><a href="{{ route('marketing.apartment-maintenance-management') }}">Apartment maintenance management</a></li>
                    <li><a href="{{ route('marketing.society-maintenance-billing') }}">Society maintenance billing</a></li>
                    <li><a href="{{ route('marketing.society-accounting-software') }}">Society accounting software</a></li>
                    <li><a href="{{ route('marketing.society-management-app') }}">Society management app</a></li>
                    <li><a href="{{ route('marketing.apartment-management-app') }}">Apartment management app</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-6">
                <h2 class="h6 footer-brand-text">Company</h2>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('marketing.features') }}">Features</a></li>
                    <li><a href="{{ route('marketing.pricing') }}">Pricing</a></li>
                    <li><a href="{{ route('marketing.about') }}">About FlatCare</a></li>
                    <li><a href="{{ route('marketing.contact') }}">Contact</a></li>
                    <li><a href="#" data-bs-toggle="modal" data-bs-target="#trialInquiryModal">Start free trial</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h2 class="h6 footer-brand-text">Stay in touch</h2>
                <p class="small mb-1">
                    <a href="mailto:{{ $contact['email'] }}" class="text-reset text-decoration-none">
                        <i class="bi bi-envelope-fill me-1"></i> {{ $contact['email'] }}
                    </a>
                </p>
                <p class="small mb-0">
                    @foreach ($contact['phones'] as $i => $phone)
                        <a href="https://wa.me/{{ $contact['whatsapp'][$i] }}" target="_blank" rel="noopener" class="text-reset text-decoration-none {{ $loop->last ? '' : 'me-3' }}">
                            <i class="bi bi-whatsapp me-1"></i> {{ $phone }}
                        </a>
                    @endforeach
                </p>
            </div>
        </div>
        <hr class="border-secondary my-4">
        <div class="d-flex justify-content-between flex-wrap small gap-2">
            <span>&copy; {{ date('Y') }} FlatCare – Apartment &amp; Society Management Software. All rights reserved.</span>
            <span>Made with care in India.</span>
        </div>
    </div>
</footer>
