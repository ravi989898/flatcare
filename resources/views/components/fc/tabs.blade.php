{{--
    Pill tabs above a table that set one query key (status, category…),
    keeping the other filters. Each tab is 'label' or ['label', count]:

    <x-fc.tabs name="status" :tabs="['' => ['All', 12], 'open' => ['Open', 3]]" />

    Pass the same key to the toolbar's :keep so a search keeps the tab.
--}}
@props(['name', 'tabs' => [], 'current' => null, 'label' => 'Filter'])
@php
    $current = (string) ($current ?? (is_string(request($name)) ? request($name) : ''));
@endphp
<nav {{ $attributes->class('fc-tabs') }} aria-label="{{ $label }}">
    @foreach ($tabs as $value => $tab)
        @php
            [$tabLabel, $tabCount] = is_array($tab) ? [$tab[0], $tab[1] ?? null] : [$tab, null];
            $isActive = $current === (string) $value;
        @endphp
        <a href="{{ request()->fullUrlWithQuery([$name => (string) $value === '' ? null : $value, 'page' => null]) }}"
            @class(['active' => $isActive]) @if ($isActive) aria-current="page" @endif>
            {{ $tabLabel }}@if ($tabCount !== null)<span class="fc-tab-count">{{ $tabCount }}</span>@endif
        </a>
    @endforeach
</nav>
