<div class="table-responsive mt-3"><table class="table align-middle"><thead><tr><th>تحديد</th><th data-column="info">رقم الزيارة</th><th data-column="date">التاريخ</th><th data-column="customer">العميل</th><th>الخدمة</th>@if($mode==='unassigned')<th data-column="address">العنوان</th><th data-column="price">السعر</th>@endif<th data-column="duration">المدة</th><th>الإجراء</th></tr></thead><tbody>
@forelse($visits as $visit)<tr data-visit-card="{{ $visit->id }}">
    <td><input type="checkbox" data-select-visit value="{{ $visit->id }}" aria-label="تحديد زيارة {{ $visit->id }}"></td>
    <td data-column="info"><a href="{{ route('admin.hourly-visits.show',$visit) }}">#{{ $visit->id }}</a></td>
    <td data-column="date" dir="ltr">{{ $visit->scheduled_start_at?->format('Y-m-d H:i')??'لم يحدد' }} — {{ $visit->scheduled_end_at?->format('H:i') }}</td>
    <td data-column="customer"><a href="{{ route('clients.show',$visit->order->user_id) }}">{{ $visit->order->user?->name }}</a></td><td>{{ $visit->order->serviceCategory?->name }}</td>
    @if($mode==='unassigned')<td data-column="address">{{ $visit->address_text }}</td><td data-column="price">{{ number_format($visit->visit_price,2) }} ر.س</td>@endif
    <td data-column="duration">@include('admin.hourly_visits._delay') {{ $visit->planned_hours }} ساعات</td>
    <td>@if($canEdit)
        @if($mode==='unassigned')<button class="btn btn-sm btn-outline-primary" data-single="offer" data-id="{{ $visit->id }}">{{ $visit->offered_to_workers?'إعداد العرض للعاملات':'عرض للعاملات' }}</button><button class="btn btn-sm btn-light" data-single="assign_all" data-id="{{ $visit->id }}">تعيين تلقائي</button>@if($visit->offered_to_workers)<small class="d-block text-success">معروضة للعاملات المؤهلات</small>@endif
        @else @if($visit->is_missed)<button class="btn btn-sm btn-warning" data-single="reschedule" data-id="{{ $visit->id }}">إعادة الجدولة</button>@else<button class="btn btn-sm btn-success" data-single="complete" data-id="{{ $visit->id }}">إكمال</button>@endif<button class="btn btn-sm btn-outline-danger" data-single="cancel_visit" data-id="{{ $visit->id }}">إلغاء</button>@endif
    @endif</td>
</tr>@empty<tr><td colspan="9" class="text-center py-5 text-muted">لا توجد زيارات مطابقة للفلاتر.</td></tr>@endforelse
</tbody></table></div>
