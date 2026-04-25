@if ($paginator->hasPages())
    <div class="pagination-outer">
        <div class="pagination-style1">
            <ul class="clearfix">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="prev disabled">
                        <span><i class="fa fa-angle-right"></i></span>
                    </li>
                @else
                    <li class="prev">
                        <a href="{{ $paginator->previousPageUrl() }}&type={{ request('type', 'all') }}">
                            <span><i class="fa fa-angle-right"></i></span>
                        </a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- Dots --}}
                    @if (is_string($element))
                        <li><span><i class="fa fa-ellipsis-h"></i></span></li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="active"><a href="javascript:;">{{ $page }}</a></li>
                            @else
                                <li><a href="{{ $url }}&type={{ request('type', 'all') }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="next">
                        <a href="{{ $paginator->nextPageUrl() }}&type={{ request('type', 'all') }}">
                            <span><i class="fa fa-angle-left"></i></span>
                        </a>
                    </li>
                @else
                    <li class="next disabled">
                        <span><i class="fa fa-angle-left"></i></span>
                    </li>
                @endif
            </ul>
        </div>
    </div>
@endif
