{{-- روابط الصفحات لشاشة تحويلات مساند؛ يلتقطها hourly-ajax-filter.js عبر .pagination فتُجلب بدون إعادة تحميل. --}}
@if ($paginator->hasPages())
    <nav aria-label="التنقل بين الصفحات">
        <ul class="pagination mt-pagination">
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true"><span class="page-link"><i class="fas fa-angle-double-right"></i></span></li>
                <li class="page-item disabled" aria-disabled="true"><span class="page-link"><i class="fas fa-angle-right"></i></span></li>
            @else
                <li class="page-item"><a class="page-link" href="{{ $paginator->url(1) }}" title="الصفحة الأولى"><i class="fas fa-angle-double-right"></i></a></li>
                <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" title="السابقة"><i class="fas fa-angle-right"></i></a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" title="التالية"><i class="fas fa-angle-left"></i></a></li>
                <li class="page-item"><a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}" title="الصفحة الأخيرة"><i class="fas fa-angle-double-left"></i></a></li>
            @else
                <li class="page-item disabled" aria-disabled="true"><span class="page-link"><i class="fas fa-angle-left"></i></span></li>
                <li class="page-item disabled" aria-disabled="true"><span class="page-link"><i class="fas fa-angle-double-left"></i></span></li>
            @endif
        </ul>
    </nav>
@endif
