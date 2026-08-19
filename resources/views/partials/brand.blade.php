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
    <img src="{{ $logoUrl }}" alt="FlatCare" style="height: {{ $height ?? '28px' }}; width: auto;" class="{{ $imgClass ?? '' }}">
@else
    <i class="bi bi-house-heart-fill"></i> FlatCare
@endif
