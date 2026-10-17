{{-- A filter dropdown for <x-fc.toolbar>; selected from the query string. --}}
@props(['name', 'options' => [], 'placeholder' => null, 'value' => null, 'label' => null])
@php
    $current = (string) ($value ?? (is_string(request($name)) ? request($name) : ''));
@endphp
<select name="{{ $name }}" aria-label="{{ $label ?? $placeholder ?? $name }}" {{ $attributes->class('custom-select') }}>
    @if ($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach ($options as $optionValue => $optionLabel)
        <option value="{{ $optionValue }}" @selected($current === (string) $optionValue)>{{ $optionLabel }}</option>
    @endforeach
</select>
