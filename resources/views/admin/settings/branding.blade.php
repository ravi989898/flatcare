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
                        <form action="{{ route('admin.settings.branding.destroy') }}" method="POST" onsubmit="return confirm('Remove the current logo and revert to the default brand mark?')">
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
                <form action="{{ route('admin.settings.branding.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="logo">Logo File <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" name="logo" id="logo" accept=".png,.jpg,.jpeg,.svg,.webp"
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
@stop

@section('js')
    <script>
        // Bootstrap 4 custom-file input doesn't show the chosen filename by default.
        document.getElementById('logo')?.addEventListener('change', function (e) {
            const label = e.target.nextElementSibling;
            if (label) {
                label.textContent = e.target.files.length ? e.target.files[0].name : 'Choose file…';
            }
        });
    </script>
@stop
