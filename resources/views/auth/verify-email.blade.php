@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_header', 'Verify your email')

@section('auth_body')
    <p class="mb-3">
        Thanks for signing up! Before getting started, please check your email
        for a verification link.
    </p>

    @if (session('status') === 'verification-link-sent')
        <div class="alert alert-success">
            A new verification link has been sent to the email address you provided during registration.
        </div>
    @endif

    @if (session('status'))
        <div class="alert alert-info">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-block btn-flat btn-primary">
            Resend verification email
        </button>
    </form>
@stop

@section('auth_footer')
    <form method="POST" action="{{ route('logout') }}" class="m-0">
        @csrf
        <button type="submit" class="btn btn-link p-0">Sign out</button>
    </form>
@stop
