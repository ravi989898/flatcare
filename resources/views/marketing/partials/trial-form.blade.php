{{--
    The free-trial / enquiry form (posts to the existing trial_inquiries.store
    route, which queues a lead for the Super Admin). Shared by the modal and
    the Contact page. Param $idp keeps element ids unique when both exist.
--}}
<form action="{{ route('trial_inquiries.store') }}" method="POST" class="{{ $formClass ?? 'modal-body px-4 pb-4 pt-2' }}" novalidate>
    @csrf
    <div class="mb-3">
        <label for="{{ $idp }}society_name" class="form-label small fw-semibold">Society name</label>
        <input type="text" name="society_name" id="{{ $idp }}society_name" value="{{ old('society_name') }}" class="form-control @error('society_name') is-invalid @enderror" maxlength="255" autocomplete="organization" required>
        @error('society_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label for="{{ $idp }}contact_name" class="form-label small fw-semibold">Your name</label>
        <input type="text" name="contact_name" id="{{ $idp }}contact_name" value="{{ old('contact_name') }}" class="form-control @error('contact_name') is-invalid @enderror" maxlength="255" autocomplete="name" required>
        @error('contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="row g-3 mb-3">
        <div class="col-sm-6">
            <label for="{{ $idp }}email" class="form-label small fw-semibold">Email address</label>
            <input type="email" name="email" id="{{ $idp }}email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" maxlength="255" autocomplete="email" required>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-6">
            <label for="{{ $idp }}phone" class="form-label small fw-semibold">Mobile number</label>
            <input type="tel" name="phone" id="{{ $idp }}phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" maxlength="30" autocomplete="tel" required>
            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="mb-4">
        <label for="{{ $idp }}address" class="form-label small fw-semibold">Address</label>
        <textarea name="address" id="{{ $idp }}address" rows="2" maxlength="500" class="form-control @error('address') is-invalid @enderror" autocomplete="street-address" required>{{ old('address') }}</textarea>
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <button type="submit" class="btn btn-brand btn-lg w-100 rounded-pill">Request my free trial</button>
    <p class="small text-muted-2 text-center mt-3 mb-0"><i class="bi bi-shield-check"></i> No credit card required · We'll contact you within one business day</p>
</form>
