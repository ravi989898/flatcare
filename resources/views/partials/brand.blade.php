{{--
    Shared brand mark used on every public/tenant-portal page that isn't
    inside the AdminLTE layout (which gets its logo from adminlte.logo_img,
    set dynamically in AdminMenuServiceProvider). Falls back to the icon +
    text mark when no super-admin-uploaded logo exists.

    Params: $height (CSS height, default 28px), $imgClass (extra classes)
--}}
@php
    $logoUrl = \App\Models\PlatformSetting::current()->logoUrl();
@endphp
@if ($logoUrl)
    <span class="d-inline-flex align-items-center gap-2">
        <img src="{{ $logoUrl }}" alt="{{ config('app.name', 'FlatCare') }}" style="height: {{ $height ?? '44px' }}; width: auto;" class="{{ $imgClass ?? '' }}">
        <span>{{ config('app.name', 'FlatCare') }}</span>
    </span>
@else
    <i class="bi bi-house-heart-fill"></i> FlatCare
@endif
