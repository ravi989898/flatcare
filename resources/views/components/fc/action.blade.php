{{--
    Colour-coded row action button (icon + short text) for a table row. A
    link when href is given, otherwise a <button> (pass type="submit",
    data-toggle="modal" data-target="#…", data-confirm="…" as needed).
    The colour comes from the icon so the same action looks the same on
    every listing (Edit = blue, View = teal, Bill/Print = purple,
    Deactivate/Reset = amber, Delete = red, Activate/other = green); `tone`
    only covers icons not in that map. The visible text is `text`, else a
    short word for a few icons (Edit, Locked), else `label`; when the label
    says more than the text it still shows as a hover tooltip.
--}}
@props(['href' => null, 'icon', 'label', 'text' => null, 'tone' => null, 'disabled' => false])
@php
    $colour = match ($icon) {
        'pencil' => 'fc-act-blue',
        'eye', 'bar-chart', 'box-arrow-up-right' => 'fc-act-teal',
        'receipt', 'printer' => 'fc-act-purple',
        'key', 'pause-circle', 'x-circle', 'box-arrow-right' => 'fc-act-amber',
        'trash' => 'fc-act-red',
        'lock' => 'fc-act-grey',
        default => match ($tone) {
            'danger' => 'fc-act-red',
            'warning' => 'fc-act-amber',
            default => 'fc-act-green',
        },
    };
    $text ??= ['pencil' => 'Edit', 'lock' => 'Locked'][$icon] ?? $label;
    $tip = $label !== $text ? $label : null;
@endphp
@if ($href && !$disabled)
    <a href="{{ $href }}" {{ $attributes->class(['fc-act', $colour]) }} @if ($tip) data-tip="{{ $tip }}" @endif aria-label="{{ $label }}"><i class="bi bi-{{ $icon }}" aria-hidden="true"></i><span>{{ $text }}</span></a>
@else
    <button type="{{ $attributes->get('type', 'button') }}" {{ $attributes->except('type')->class(['fc-act', $colour]) }} @if ($tip) data-tip="{{ $tip }}" @endif aria-label="{{ $label }}" @disabled($disabled)><i class="bi bi-{{ $icon }}" aria-hidden="true"></i><span>{{ $text }}</span></button>
@endif
