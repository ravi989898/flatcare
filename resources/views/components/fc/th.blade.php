{{--
    Table header cell; with sort="key" it links to ?sort=key&dir=… (the
    controller whitelists the key - App\Support\ListQuery). :default="true"
    marks the column the page is sorted by when no ?sort= is given, and
    first="desc" makes the first click sort high-to-low (dates, amounts).
--}}
@props(['sort' => null, 'default' => false, 'defaultDir' => 'desc', 'first' => 'asc'])
@php
    $active = false;
    $dir = null;
    if ($sort) {
        $requested = request('sort');
        if ($requested === $sort) {
            $active = true;
            $dir = request('dir') === 'asc' ? 'asc' : 'desc';
        } elseif ($default && blank($requested)) {
            $active = true;
            $dir = $defaultDir;
        }
        $next = $active ? ($dir === 'asc' ? 'desc' : 'asc') : $first;
        $url = request()->fullUrlWithQuery(['sort' => $sort, 'dir' => $next, 'page' => null]);
        $icon = $active ? ($dir === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down') : 'bi-arrow-down-up';
    }
@endphp
<th scope="col" {{ $attributes->class(['fc-sorted' => $active]) }} @if ($active) aria-sort="{{ $dir === 'asc' ? 'ascending' : 'descending' }}" @endif>
    @if ($sort)
        <a href="{{ $url }}" class="fc-sort">{{ $slot }}<i class="bi {{ $icon }}" aria-hidden="true"></i></a>
    @else
        {{ $slot }}
    @endif
</th>
