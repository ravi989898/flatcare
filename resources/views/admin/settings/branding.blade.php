@extends('adminlte::page')

@section('title', 'Branding')

@section('content_header')
    <h1>Branding</h1>
@stop

@section('content')
    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Current Logo</h3>
                </div>
                <div class="card-body text-center">
                    @if ($settings->logoUrl())
                        <img src="{{ $settings->logoUrl() }}" alt="Current logo" style="max-width: 220px; max-height: 220px;" class="img-fluid mb-3">
                        <p class="text-muted small mb-0">Shown across the super-admin panel, the society portal, and the public site.</p>
                    @else
                        <div class="py-4">
                            <i class="fas fa-house-user fa-4x text-muted"></i>
                            <p class="text-muted small mt-3 mb-0">No custom logo set — using the default "FlatCare" text brand mark.</p>
                        </div>
                    @endif
                </div>
                @if ($settings->logoUrl())
                    <div class="card-footer">
                        <form action="{{ route('admin.settings.branding.destroy') }}" method="POST" data-confirm="Remove the current logo and revert to the default brand mark?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-block">
                                <i class="fas fa-trash"></i> Remove Logo
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upload New Logo</h3>
                </div>
                <form action="{{ route('admin.settings.branding.update') }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="logo">Logo File <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" name="logo" id="logo" accept=".png,.jpg,.jpeg,.svg,.webp"
                                    data-validate="file" data-allowed-ext="png,jpg,jpeg,svg,webp" data-max-size-kb="2048"
                                    class="custom-file-input @error('logo') is-invalid @enderror" required>
                                <label class="custom-file-label" for="logo">Choose file…</label>
                                @error('logo')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <small class="form-text text-muted">
                                PNG, JPG, SVG or WEBP. Max 2MB. A square or wide rectangular image works best — it's shown at a small size in navigation bars.
                            </small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload &amp; Apply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Current App Icon</h3>
                </div>
                <div class="card-body text-center">
                    @if ($settings->iconUrl())
                        <img src="{{ $settings->iconUrl() }}" alt="Current app icon" style="max-width: 140px; max-height: 140px;" class="img-fluid mb-3 rounded">
                        <p class="text-muted small mb-0">Used as the browser tab icon everywhere — the public site, this admin panel and the sign-in pages.</p>
                    @else
                        <div class="py-4">
                            <i class="fas fa-icons fa-4x text-muted"></i>
                            <p class="text-muted small mt-3 mb-0">No custom icon set — using the default FlatCare app icon.</p>
                        </div>
                    @endif
                </div>
                @if ($settings->iconUrl())
                    <div class="card-footer">
                        <form action="{{ route('admin.settings.branding.icon.destroy') }}" method="POST" data-confirm="Remove the current app icon and revert to the default?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-block">
                                <i class="fas fa-trash"></i> Remove Icon
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upload New App Icon</h3>
                </div>
                <form action="{{ route('admin.settings.branding.icon.update') }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="icon">Icon File <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" name="icon" id="icon" accept=".png,.jpg,.jpeg,.webp"
                                    data-validate="file" data-allowed-ext="png,jpg,jpeg,webp" data-max-size-kb="2048"
                                    class="custom-file-input @error('icon') is-invalid @enderror" required>
                                <label class="custom-file-label" for="icon">Choose file…</label>
                                @error('icon')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <small class="form-text text-muted">
                                PNG, JPG or WEBP. Max 2MB. Use a square image — it's automatically cropped to a
                                square and resized to every size a browser tab or home-screen icon needs.
                                Browsers cache favicons aggressively, so it can take a hard refresh (or a new
                                tab) to see the change.
                            </small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload &amp; Apply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="{{ asset('js/admin-branding.js') }}"></script>
@stop
