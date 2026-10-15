<div class="hr-facts">
    <span><i class="fa-light fa-calendar-days"></i> <strong>{{ $data['visits_count'] }}</strong> زيارة</span>
    <span><i class="fa-light fa-clock"></i> <strong>{{ $data['hours_per_visit'] }}</strong> ساعات لكل زيارة</span>
    <span><i class="fa-light fa-users"></i> <strong>{{ $data['pricing']['workers'] }}</strong> عاملة لكل زيارة</span>
    <span><i class="fa-light fa-hourglass-half"></i> صالحة <strong>{{ $data['validity_days'] }}</strong> يومًا</span>
</div>
<div class="hr-includes">
    <span class="{{ $data['transport_included'] ? 'yes' : 'no' }}">{{ $data['transport_included'] ? 'النقل مشمول' : 'النقل حسب تفاصيل السعر' }}</span>
    <span class="{{ $data['materials_included'] ? 'yes' : 'no' }}">{{ $data['materials_included'] ? 'مواد التنظيف مشمولة' : 'مواد التنظيف غير مشمولة' }}</span>
</div>
@if ($data['description'])
    <p class="hr-desc">{{ $data['description'] }}</p>
@endif
<div class="hr-price-box">
    <p class="hr-price">{{ number_format($data['pricing']['display_amount'], 2) }} <small>ر.س {{ $data['pricing']['price_display'] === 'per_visit' ? 'للزيارة' : 'للباقة' }}</small></p>
    <p class="hr-muted">السعر شامل الضريبة. الإجمالي {{ number_format($data['pricing']['total_amount'], 2) }} ر.س</p>
</div>

@include('web.hourly._payment-policy-note')
