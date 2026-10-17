{{-- Rounded initials (or a photo) at the start of a name cell. --}}
@props(['name' => '', 'src' => null, 'round' => false])
@php
    $initials = collect(preg_split('/[\s\-\/]+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn ($word) => mb_substr($word, 0, 1))
        ->implode('') ?: '?';
@endphp
<span {{ $attributes->class(['fc-avatar', 'round' => $round]) }} aria-hidden="true">
    @if ($src)
        <img src="{{ $src }}" alt="">
    @else
        {{ $initials }}
    @endif
</span>
