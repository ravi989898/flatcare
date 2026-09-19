{{-- Visible FAQ. The FAQPage JSON-LD (App\Support\Seo::faq) is built from the SAME array, so schema and page never disagree. --}}
@if (! empty($faqs))
    <section class="py-5" id="faq" aria-labelledby="faq-title">
        <div class="container" style="max-width: 900px;">
            <div class="text-center mb-4">
                <span class="section-eyebrow">FAQ</span>
                <h2 class="section-title" id="faq-title">{{ $faqTitle ?? 'Frequently asked questions' }}</h2>
            </div>
            <div class="d-flex flex-column gap-3">
                @foreach ($faqs as $faq)
                    <div class="card card-feature p-4 faq-item">
                        <h3 class="h6 fw-bold mb-2">{{ $faq[0] }}</h3>
                        <p class="text-muted-2 mb-0">{!! \App\Support\Seo::linkify($faq[1]) !!}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
