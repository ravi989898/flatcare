@extends('layouts.auth-split')

@section('page_title', 'Sign in')
@section('card_title', 'Sign in to FlatCare')
@section('card_subtitle', 'Access the platform admin panel to manage societies and settings.')

@section('form')
    @include('partials.login-form', ['action' => route('login')])
@stop
