{{--
    Society-portal sign-in. Same split-screen shell as the super-admin login
    (layouts/auth-split); only the guard/route it posts to differs.
--}}
@extends('layouts.auth-split')

@section('page_title', 'Society portal sign in')
@section('card_title', 'Sign in to your society portal')
@section('card_subtitle', 'Access your society\'s dashboard to manage everything in one place.')

@section('form')
    @include('partials.login-form', ['action' => route('society.login')])
@stop
