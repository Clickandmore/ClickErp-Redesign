{{-- أزرار التصدير بالفترة والفلاتر الحالية (بلا page). المتغيرات: $exportRoute
     زران مستقلان لا قائمة منسدلة: قوائم لوحة التحكم مثبّتة position:fixed عالمياً. --}}
@php $exportQuery = request()->except(['page', 'format']); @endphp
<div class="pg-export" title="{{ __('payment_gateways.export.hint') }}">
    <a href="{{ route($exportRoute, $exportQuery + ['format' => 'xlsx']) }}" class="pg-export-btn">
        <i class="fa fa-file-excel"></i> {{ __('payment_gateways.export.excel') }}
    </a>
    <a href="{{ route($exportRoute, $exportQuery + ['format' => 'pdf']) }}" class="pg-export-btn">
        <i class="fa fa-file-pdf"></i> {{ __('payment_gateways.export.pdf') }}
    </a>
</div>
