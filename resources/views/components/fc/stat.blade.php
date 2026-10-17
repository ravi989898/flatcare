{{-- Summary card for the <div class="fc-stats"> row above a table. --}}
@props(['icon', 'value', 'label', 'tone' => 'brand', 'href' => null])
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class('fc-stat') }}>
@else
    <div {{ $attributes->class('fc-stat') }}>
@endif
    <span class="fc-stat-icon fc-tone-{{ $tone }}"><i class="bi bi-{{ $icon }}" aria-hidden="true"></i></span>
    <span>
        <span class="fc-stat-value">{{ $value }}</span>
        <span class="fc-stat-label">{{ $label }}</span>
    </span>
@if ($href)
    </a>
@else
    </div>
@endif
