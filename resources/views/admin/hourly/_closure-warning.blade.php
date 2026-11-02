@php
    $closureConflict = app(\App\Services\Hourly\ClosureImpact::class)->conflict($visit,$order);
@endphp
@if($closureConflict)
<div class="alert alert-warning m-2" role="alert">
    <strong>تعارض مع إغلاق الخدمة / إجازة</strong>
    <span>{{ $closureConflict['message'] }}</span>
    @if($visit->has_started_execution)
        <span>بدأ تنفيذ الزيارة؛ راجع مسؤول التشغيل قبل أي معالجة.</span>
    @else
        <a href="{{ route('admin.hourly-orders.show',$order) }}#visit-{{ $visit->id }}">فتح الطلب #{{ $order->order_number }} لمعالجة الموعد</a>
    @endif
</div>
@endif
