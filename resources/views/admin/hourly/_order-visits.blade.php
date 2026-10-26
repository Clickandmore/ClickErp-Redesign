<p class="text-muted small">ملاحظات الزيارة تخص الزيارة المحددة فقط.</p>
<div class="table-responsive"><table class="hs-table"><thead><tr><th>الزيارة</th><th>التاريخ والوقت</th><th>مقدمو الخدمة</th><th>التوصيل والاستلام</th><th>الحالة</th><th>الملاحظات</th></tr></thead><tbody>
@forelse($order->visits as $visit)
    <tr>
        <td><b>#{{ $visit->visit_number }}</b><small class="d-block text-muted">مرجع {{ $visit->id }}</small></td>
        <td><div class="d-flex flex-column align-items-start gap-1">
            <b dir="ltr">{{ $visit->scheduled_start_at?->format('Y-m-d') ?: 'لم يحدد' }}</b>
            <span dir="ltr" class="text-muted text-nowrap">{{ $visit->scheduled_start_at?->format('H:i') }} — {{ $visit->scheduled_end_at?->format('H:i') }}</span>
            <small>{{ (float) $visit->planned_hours }} ساعات</small>
        </div></td>
        <td>{{ $visit->workers->pluck('name')->join('، ') ?: 'غير مخصص' }}<small class="d-block text-muted">المطلوب: {{ $visit->workers_count }}</small></td>
        <td><small class="d-block">التوصيل: {{ $visit->deliveryDriver?->name ?: '—' }}</small><small class="d-block">الاستلام: {{ $visit->pickupDriver?->name ?: '—' }}</small></td>
        <td><span class="hs-badge">{{ __('hourly.'.$visit->display_status) }}</span>@include('admin.hourly._cash-collector',['visit'=>$visit])</td>
        <td>@foreach(collect($order->booking_metadata['notes'] ?? [])->where('visit_id',$visit->id) as $note)<div class="hs-note">{{ $note['text'] }}<small class="d-block text-muted">{{ $note['author'] }} — {{ $note['created_at'] }}</small></div>@endforeach<div class="hs-note-form">@include('admin.hourly._note-form',['visitId'=>$visit->id])</div></td>
    </tr>
@empty<tr><td colspan="6"><div class="hs-empty">لا توجد زيارات على الطلب.</div></td></tr>@endforelse
</tbody></table></div>
