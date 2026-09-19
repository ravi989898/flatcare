{{-- "Start free trial" doesn't self-register an account — it sends a lead
     to Super Admin (Admin > Inquiries), who reaches out to set the society
     up. --}}
<div class="modal fade" id="trialInquiryModal" tabindex="-1" aria-labelledby="trialInquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            @if (session('trial_inquiry_sent'))
                <div class="modal-body p-5 text-center">
                    <div class="feature-icon mx-auto mb-3" style="font-size:1.8rem;"><i class="bi bi-check-lg"></i></div>
                    <h2 class="h4 fw-bold mb-2">Thanks — we've got it!</h2>
                    <p class="text-muted-2 mb-4">Our team will reach out shortly to set up your society on FlatCare.</p>
                    <button type="button" class="btn btn-brand rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                </div>
            @else
                <div class="modal-header border-0 px-4 pt-4">
                    <div>
                        <h2 class="modal-title h5 fw-bold" id="trialInquiryModalLabel">Start your free trial</h2>
                        <p class="small text-muted-2 mb-0">Tell us a bit about your society — our team will reach out to set you up.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                @include('marketing.partials.trial-form', ['idp' => 'trial_'])
            @endif
        </div>
    </div>
</div>
