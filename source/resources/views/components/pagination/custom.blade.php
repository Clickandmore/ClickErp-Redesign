@if ($paginator->hasPages())
    <div class="paginations">

        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <div class="prev paginations_btn disabled">
                <i class="fa-regular fa-chevron-right"></i>
            </div>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="prev paginations_btn">
                <i class="fa-regular fa-chevron-right"></i>
            </a>
        @endif

        <ul>
            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li><span>{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="active"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </ul>

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="next paginations_btn">
                <i class="fa-regular fa-chevron-left"></i>
            </a>
        @else
            <div class="next paginations_btn disabled">
                <i class="fa-regular fa-chevron-left"></i>
            </div>
        @endif
    </div>
@endif
