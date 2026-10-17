{{--
    Search + filter bar on top of a listing table. A GET form that keeps the
    current sort / rows-per-page; selects inside it submit on change and the
    search box shortly after typing stops (public/js/fc-table.js).

    <x-fc.toolbar placeholder="Search flat, block…" count="6 readings">
        <x-fc.select name="status" placeholder="Any status" :options="[...]" />
    </x-fc.toolbar>

    :search="false" hides the search box; :keep="['tab']" carries other query
    keys the page needs through a filter change and "Clear filters".
--}}
@props([
    'action' => null,
    'search' => 'search',
    'placeholder' => 'Search…',
    'count' => null,
    'keep' => [],
])
@php
    $action ??= url()->current();
    $carried = ['sort', 'dir', 'per_page', ...$keep];
    $hasFilters = collect(request()->query())
        ->except(['page', ...$carried])
        ->contains(fn ($value) => filled($value));
    $clearQuery = http_build_query(array_filter(request()->only($keep), 'is_string'));
@endphp
<form method="GET" action="{{ $action }}" {{ $attributes->class('fc-toolbar js-fc-filter') }} role="search">
    @if ($search !== false)
        <label class="fc-search">
            <span class="sr-only">{{ $placeholder }}</span>
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="{{ $search }}" value="{{ is_string(request($search)) ? request($search) : '' }}"
                class="form-control" placeholder="{{ $placeholder }}" data-fc-search autocomplete="off" maxlength="100">
        </label>
    @endif

    {{ $slot }}

    @foreach ($carried as $key)
        @if (is_string(request($key)) && filled(request($key)))
            <input type="hidden" name="{{ $key }}" value="{{ request($key) }}">
        @endif
    @endforeach

    @if ($hasFilters)
        <a href="{{ $action }}{{ $clearQuery !== '' ? '?'.$clearQuery : '' }}" class="fc-clear"><i class="bi bi-x-circle" aria-hidden="true"></i> Clear filters</a>
    @endif

    @if (filled($count))
        <span class="fc-count">{{ $count }}</span>
    @endif
</form>
