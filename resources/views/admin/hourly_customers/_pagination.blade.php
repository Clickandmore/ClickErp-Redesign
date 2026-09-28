{{-- ترقيم صفحات «عملاء الساعات» بتصميم الوحدة (بدل قالب Tailwind الافتراضي غير المنسق في لوحة Bootstrap). --}}
@if($paginator->hasPages() || $paginator->total() > 0)
@php
    $p = fn (string $key, array $replace = []) => __('hourly_customers.pagination.'.$key, $replace);
    $rtl = app()->getLocale() === 'ar';
@endphp
<nav class="hc-pager" aria-label="{{ $p('label') }}">
    <span class="hc-pager-info">{{ $p('showing', ['from' => number_format($paginator->firstItem() ?? 0), 'to' => number_format($paginator->lastItem() ?? 0), 'total' => number_format($paginator->total())]) }}</span>
    @if($paginator->hasPages())
        <ul>
            @if($paginator->onFirstPage())
                <li><span class="is-disabled" aria-disabled="true" aria-label="{{ $p('prev') }}"><i class="fa fa-chevron-{{ $rtl ? 'right' : 'left' }}"></i></span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ $p('prev') }}"><i class="fa fa-chevron-{{ $rtl ? 'right' : 'left' }}"></i></a></li>
            @endif
            @foreach($elements as $element)
                @if(is_string($element))
                    <li><span class="is-gap">…</span></li>
                @elseif(is_array($element))
                    @foreach($element as $page => $url)
                        @if($page == $paginator->currentPage())
                            <li><span class="is-active" aria-current="page">{{ number_format($page) }}</span></li>
                        @else
                            <li><a href="{{ $url }}" aria-label="{{ $p('page', ['page' => $page]) }}">{{ number_format($page) }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ $p('next') }}"><i class="fa fa-chevron-{{ $rtl ? 'left' : 'right' }}"></i></a></li>
            @else
                <li><span class="is-disabled" aria-disabled="true" aria-label="{{ $p('next') }}"><i class="fa fa-chevron-{{ $rtl ? 'left' : 'right' }}"></i></span></li>
            @endif
        </ul>
    @endif
</nav>
@endif
