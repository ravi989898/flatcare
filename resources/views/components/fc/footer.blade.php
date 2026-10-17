{{--
    Bottom of a listing card: "Showing 1–25 of 80", the rows-per-page picker
    (?per_page=, see App\Support\ListQuery::PER_PAGE) and page links. Given a
    plain collection instead of a paginator it just shows the total.
--}}
@props(['paginator', 'perPage' => true, 'noun' => null])
@php
    $isPaged = $paginator instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $total = $isPaged ? $paginator->total() : count($paginator);
@endphp
@if ($total > 0)
    <div class="fc-footer">
        <div class="d-flex flex-wrap align-items-center" style="gap: .9rem;">
            @if ($isPaged)
                <span>Showing <b>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</b> of <b>{{ number_format($total) }}</b>{{ $noun ? ' '.$noun : '' }}</span>
                @if ($perPage && $total > min(\App\Support\ListQuery::PER_PAGE))
                    <form method="GET" action="{{ url()->current() }}" class="js-fc-filter">
                        @foreach (request()->except(['page', 'per_page']) as $key => $value)
                            @if (is_string($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label for="fc-per-page" class="mb-0 font-weight-normal">Rows per page</label>
                        @php $choices = collect(\App\Support\ListQuery::PER_PAGE)->push($paginator->perPage())->unique()->sort(); @endphp
                        <select id="fc-per-page" name="per_page" class="custom-select custom-select-sm">
                            @foreach ($choices as $choice)
                                <option value="{{ $choice }}" @selected($choice === $paginator->perPage())>{{ $choice }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            @else
                <span><b>{{ number_format($total) }}</b> {{ $noun ?? 'total' }}</span>
            @endif
        </div>
        @if ($isPaged && $paginator->hasPages())
            {{ $paginator->onEachSide(1)->links('components.fc.pagination') }}
        @endif
    </div>
@endif
