{{-- Page links for <x-fc.footer> ($paginator->links('components.fc.pagination')). --}}
@if ($paginator->hasPages())
    <nav aria-label="Pagination">
        <ul class="fc-pages">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="off" aria-disabled="true" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="gap">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="on" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                @else
                    <span class="off" aria-disabled="true" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
