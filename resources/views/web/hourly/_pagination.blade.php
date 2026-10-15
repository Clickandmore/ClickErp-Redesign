{{-- ترقيم صفحات موقع الساعات. الاستخدام: $paginator->onEachSide(1)->links('web.hourly._pagination') --}}
@if ($paginator->hasPages())
<nav class="hr-pager" role="navigation" aria-label="التنقل بين الصفحات">
    <ul>
        @if ($paginator->onFirstPage())
            <li class="nav is-disabled" aria-disabled="true"><span><i class="fa-light fa-chevron-right"></i> السابق</span></li>
        @else
            <li class="nav"><a href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="fa-light fa-chevron-right"></i> السابق</a></li>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="dots" aria-disabled="true"><span>{{ $element }}</span></li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="is-active" aria-current="page"><span>{{ $page }}</span></li>
                    @else
                        <li><a href="{{ $url }}" aria-label="الصفحة {{ $page }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <li class="nav"><a href="{{ $paginator->nextPageUrl() }}" rel="next">التالي <i class="fa-light fa-chevron-left"></i></a></li>
        @else
            <li class="nav is-disabled" aria-disabled="true"><span>التالي <i class="fa-light fa-chevron-left"></i></span></li>
        @endif
    </ul>
    <p>عرض {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} من {{ $paginator->total() }}</p>
</nav>
@endif
