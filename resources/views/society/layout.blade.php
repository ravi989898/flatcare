{{--
    Society-portal shell. Rebuilt to sit on top of the same AdminLTE page
    (jeroennoten/laravel-adminlte, AdminLTE 3.2 / Bootstrap 4) that the
    Super Admin panel uses under resources/views/admin, rather than a
    separately hand-rolled Bootstrap 5 top-nav — see App\Http\Middleware\
    SetSocietyContext, which builds this request's sidebar menu (from the
    tenant user's role) and points config('adminlte.menu') /
    config('adminlte.logout_url') at it before this view ever renders, and
    switches the default auth guard to 'society' so AdminLTE's built-in
    top-bar user menu (which always reads Auth::user() with no guard
    argument) shows the right name and signs the right guard out.

    Every society/*.blade.php page still does @extends('society.layout')
    and fills @section('title') / @section('content_header') /
    @section('content') exactly as before — only this shared shell changed.
--}}
@extends('adminlte::page')

@section('adminlte_css_pre')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
@stop

@section('adminlte_css')
    <style>
        :root { --brand: #2f6f4f; --brand-dark: #234f38; --brand-light: #eaf5ee; }
        .stat-card { border: 1px solid #eceff1; border-radius: 1rem; }
        .stat-icon {
            width: 48px; height: 48px; border-radius: .8rem; background: var(--brand-light); color: var(--brand);
            display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
        }
        .btn-brand { background: var(--brand); border-color: var(--brand); color: #fff; }
        .btn-brand:hover { background: var(--brand-dark); border-color: var(--brand-dark); color: #fff; }
        .text-brand { color: var(--brand); }

        {{-- Bootstrap 4 (bundled with AdminLTE) has no gap-* utilities; the
             society pages use them on flex containers for tight, even
             spacing between buttons/icons. --}}
        .gap-2 { gap: .5rem !important; }
        .gap-3 { gap: 1rem !important; }
    </style>
    @stack('styles')
@stop

{{-- AdminLTE renders @stack('content') before @yield('content') inside the
     same wrapper, so pushing here never clashes with a child page's own
     @section('content') — see partials/cwrapper/cwrapper-default.blade.php. --}}
@push('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif
@endpush

{{-- CSP has no 'unsafe-inline' on script-src, so inline onchange/onclick
     attributes are silently blocked — see public/js/society-ui.js. --}}
@push('js')
    <script src="{{ asset('js/society-ui.js') }}"></script>
@endpush
