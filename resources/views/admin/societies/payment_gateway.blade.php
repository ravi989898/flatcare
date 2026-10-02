@extends('adminlte::page')

@section('title', 'Online Payments')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>{{ $society->name }} - Online Payments</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.societies.show', $society->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
@stop

@section('content')
    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Keys not saved:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            @include('admin.societies._razorpay_card')
        </div>

        <div class="col-lg-5">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Where to find the keys</h3>
                </div>
                <div class="card-body">
                    <ol class="pl-3 mb-3">
                        <li>Log in to the society's account at <strong>dashboard.razorpay.com</strong>.</li>
                        <li>Pick <strong>Test Mode</strong> or <strong>Live Mode</strong> with the switch at the top.</li>
                        <li><strong>Account &amp; Settings → API Keys → Generate Key</strong>.</li>
                        <li>Copy the Key ID and Key Secret here.</li>
                    </ol>
                    <p class="mb-1"><strong>Testing (rzp_test_ keys)</strong> - no real money moves. In the app's checkout use:</p>
                    <ul class="pl-3 mb-0">
                        <li>UPI ID <code>success@razorpay</code> (payment succeeds) or <code>failure@razorpay</code> (payment fails)</li>
                        <li>Netbanking: pick any bank, then tap <em>Success</em> on Razorpay's test page</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@stop
