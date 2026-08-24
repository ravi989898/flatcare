{{--
    Society-portal sign-in screen, on the same AdminLTE auth-page shell as
    the main app's login (resources/views/auth/login.blade.php) so both
    look identical apart from which guard/route they post to.
--}}
@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('adminlte_css_pre')
    <link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
@stop

@section('css')
    <style>
        .btn-brand { background: #2f6f4f; border-color: #2f6f4f; color: #fff; }
        .btn-brand:hover { background: #234f38; border-color: #234f38; color: #fff; }
    </style>
@stop

@section('auth_header', 'Sign in to your society portal')

@section('auth_body')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('society.login') }}" method="post" novalidate>
        @csrf

        <div class="input-group mb-3">
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}" placeholder="Email" autofocus autocomplete="username">
            <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-envelope"></span></div>
            </div>
            @error('email')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="input-group mb-3">
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                placeholder="Password" autocomplete="current-password">
            <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-lock"></span></div>
            </div>
            @error('password')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="row">
            <div class="col-7">
                <div class="icheck-primary">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember">Remember Me</label>
                </div>
            </div>
            <div class="col-5">
                <button type="submit" class="btn btn-block btn-flat btn-brand">
                    <span class="fas fa-sign-in-alt"></span> Sign In
                </button>
            </div>
        </div>
    </form>
@stop

@section('auth_footer')
    <p class="my-0">
        <a href="{{ route('home') }}">&laquo; Back to homepage</a>
    </p>
@stop
