{{--
    Empty table state. When the list is empty only because of the current
    search/filters, says so and offers to clear them instead of the
    "add the first …" call to action.
--}}
@props(['icon' => 'inbox', 'title' => 'Nothing here yet', 'text' => null, 'actionUrl' => null, 'actionLabel' => null, 'keep' => []])
@php
    $filtered = collect(request()->query())
        ->except(['page', 'sort', 'dir', 'per_page', ...$keep])
        ->contains(fn ($value) => filled($value));
    $clearQuery = http_build_query(array_filter(request()->only($keep), 'is_string'));
@endphp
<div class="fc-empty">
    <span class="fc-empty-icon"><i class="bi bi-{{ $filtered ? 'search' : $icon }}" aria-hidden="true"></i></span>
    @if ($filtered)
        <h3>No results found</h3>
        <p>Nothing matches your search or filters.</p>
        <a href="{{ url()->current() }}{{ $clearQuery !== '' ? '?'.$clearQuery : '' }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i> Clear filters</a>
    @else
        <h3>{{ $title }}</h3>
        @if ($text)
            <p>{{ $text }}</p>
        @endif
        @if ($actionUrl)
            <a href="{{ $actionUrl }}" class="btn btn-brand btn-sm"><i class="bi bi-plus-lg"></i> {{ $actionLabel }}</a>
        @endif
        {{ $slot }}
    @endif
</div>
