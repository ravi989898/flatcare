{{-- The society's own Razorpay account (Admin\SocietyPaymentGatewayController). Used on the society page and the Online Payments page. --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-credit-card"></i> Online Payments (Razorpay)</h3>
        <div class="card-tools">
            @if (!$society->hasRazorpay())
                <span class="badge badge-secondary">Not set up - online payment off</span>
            @elseif ($society->razorpayTestMode())
                <span class="badge badge-warning">Test mode - no real money</span>
            @else
                <span class="badge badge-success">Live - real payments</span>
            @endif
        </div>
    </div>
    <form action="{{ route('admin.societies.payment_gateway.update', $society->id) }}" method="POST" autocomplete="off">
        @csrf
        @method('PUT')
        <div class="card-body">
            <p class="text-muted small mb-3">
                Residents' bill payments go straight into this society's own Razorpay account
                (dashboard.razorpay.com → Account &amp; Settings → API Keys). Keys are checked with Razorpay before saving.
            </p>

            <div class="form-group">
                <label for="razorpay_key_id">Key ID</label>
                <input type="text" name="razorpay_key_id" id="razorpay_key_id"
                       class="form-control @error('razorpay_key_id') is-invalid @enderror"
                       value="{{ old('razorpay_key_id', $society->razorpay_key_id) }}"
                       placeholder="rzp_test_XXXXXXXXXXXX" required>
                @error('razorpay_key_id')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group mb-0">
                <label for="razorpay_key_secret">Key Secret</label>
                <input type="password" name="razorpay_key_secret" id="razorpay_key_secret"
                       class="form-control @error('razorpay_key_secret') is-invalid @enderror"
                       placeholder="{{ $society->razorpay_key_secret ? 'Saved - leave blank to keep it' : 'Key Secret' }}"
                       autocomplete="new-password" @unless ($society->razorpay_key_secret) required @endunless>
                @error('razorpay_key_secret')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
                <small class="form-text text-muted">
                    Stored encrypted and never shown again. <code>rzp_test_</code> keys = testing (no real money), <code>rzp_live_</code> = real payments.
                </small>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Check &amp; Save Keys
            </button>
            @if ($society->hasRazorpay())
                <button type="submit" form="remove-razorpay-keys" class="btn btn-outline-danger">
                    <i class="fas fa-ban"></i> Remove keys
                </button>
            @endif
        </div>
    </form>
    @if ($society->hasRazorpay())
        <form id="remove-razorpay-keys" action="{{ route('admin.societies.payment_gateway.destroy', $society->id) }}" method="POST"
              onsubmit="return confirm('Turn off online payment for this society? Residents will no longer be able to pay bills in the app.');">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>
