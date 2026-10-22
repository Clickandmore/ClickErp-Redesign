{{-- محتوى تبويب واحد من ملف العاملة؛ يُعرض داخل الصفحة أو يُجلب وحده عند التنقل بين التبويبات دون إعادة تحميل. --}}
@php
    $mpTone = fn ($status) => match ($status) {
        'completed', 'active', 'paid' => 'mp-b-green',
        'cancelled', 'cancelled_by_client', 'expired', 'no_show', 'failed' => 'mp-b-red',
        'scheduled' => 'mp-b-blue',
        'en_route', 'delivered', 'picked_up' => 'mp-b-violet',
        'pending_confirmation', 'in_progress', 'worker_finished', 'unscheduled' => 'mp-b-amber',
        default => 'mp-b-grey',
    };
@endphp
@if($tab==='profile')
    @include('admin.hourly_maids._profile')
@elseif(($logs ?? null) !== null)
    <div class="table-responsive"><table class="mp-table mp-logs">
        <thead><tr><th>تاريخ الإنشاء</th><th>الحقل</th><th>القيمة القديمة</th><th>القيمة الجديدة</th><th>تم التغيير بواسطة</th><th>المصدر</th></tr></thead>
        <tbody>
        @forelse($logs as $row)
            <tr>
                <td class="mp-num" dir="ltr">{{ $row['at']?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                <td><b>{{ $row['field'] }}</b></td>
                <td><span class="mp-val mp-val-old">{{ $row['old'] }}</span></td>
                <td><span class="mp-val mp-val-new">{{ $row['new'] }}</span></td>
                <td>{{ $row['by'] }}</td>
                <td><span class="mp-badge mp-b-grey">{{ $row['source'] }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="mp-empty mp-logs-empty">لم يتم تسجيل أي تغييرات في الملف الشخصي لهذه العاملة بعد.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="mp-pager">{{ $logs->links() }}</div>
@elseif($tab==='calendar')
    @include('admin.hourly_maids._calendar')
@elseif($tab==='leaves')
    <h5 class="mp-pane-title"><i class="fa fa-umbrella-beach"></i> طلبات الإجازة</h5>
    <div class="table-responsive"><table class="mp-table">
        <thead><tr><th>النوع</th><th>المدة</th><th>الوصف</th><th>الحالة</th><th>الإجراء</th></tr></thead>
        <tbody>
        @forelse($leaves as $leave)
            @php($status = $leave->status ?? 'approved')
            <tr>
                <td>{{ ($leave->type ?? 'leave') === 'sick' ? 'مرضية' : 'عادية' }}</td>
                <td class="mp-num" dir="ltr">{{ \Carbon\Carbon::parse($leave->starts_at)->format('Y-m-d') }} — {{ \Carbon\Carbon::parse($leave->ends_at)->format('Y-m-d') }}</td>
                <td>{{ $leave->reason ?: '—' }}</td>
                <td><span class="mp-badge {{ $status === 'approved' ? 'mp-b-green' : ($status === 'rejected' ? 'mp-b-red' : 'mp-b-amber') }}">{{ ['approved'=>'معتمدة','rejected'=>'مرفوضة','pending'=>'قيد الاعتماد'][$status] ?? $status }}</span></td>
                <td>
                    @if($status === 'pending' && AdminGuardHasPerm('hourly_maids_edit'))
                        <div class="d-flex gap-1">
                            <form method="POST" action="{{ route('admin.hourly-maids.leaves.status',[$maid,$leave->id]) }}">@csrf @method('PUT')<input type="hidden" name="status" value="approved"><button class="btn btn-sm btn-success">اعتماد</button></form>
                            <form method="POST" action="{{ route('admin.hourly-maids.leaves.status',[$maid,$leave->id]) }}">@csrf @method('PUT')<input type="hidden" name="status" value="rejected"><button class="btn btn-sm btn-outline-danger">رفض</button></form>
                        </div>
                    @else — @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="mp-empty"><i class="fa fa-calendar-check"></i> لا توجد طلبات إجازة.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="mp-pager">{{ $leaves->links() }}</div>
@elseif($orders !== null)
    <h5 class="mp-pane-title"><i class="fa fa-cart-shopping"></i> الطلبات المسندة للعاملة</h5>
    <div class="table-responsive"><table class="mp-table">
        <thead><tr><th>الطلب</th><th>الحالة</th><th>تاريخ الإنشاء</th></tr></thead>
        <tbody>
        @forelse($orders as $order)
            <tr>
                <td>@if(AdminGuardHasPerm('hourly_orders_view'))<a class="mp-link" href="{{ route('admin.hourly-orders.show',$order) }}">#{{ $order->order_number ?? $order->id }}</a>@else #{{ $order->order_number ?? $order->id }} @endif</td>
                <td><span class="mp-badge {{ $mpTone($order->status) }}">{{ \App\Services\Hourly\HourlyOrderService::statusLabel($order) }}</span></td>
                <td class="mp-num" dir="ltr">{{ $order->created_at?->format('Y-m-d') }}</td>
            </tr>
        @empty
            <tr><td colspan="3"><div class="mp-empty"><i class="fa fa-cart-shopping"></i> لا توجد طلبات مسندة للعاملة.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="mp-pager">{{ $orders->links() }}</div>
@elseif($visits !== null)
    <h5 class="mp-pane-title"><i class="fa {{ $tab==='report' ? 'fa-chart-line' : 'fa-house-user' }}"></i> {{ $tabs[$tab] }}</h5>
    <div class="table-responsive"><table class="mp-table">
        <thead><tr><th>الزيارة / الطلب</th><th>الموعد</th><th>الساعات</th><th>الحالة</th></tr></thead>
        <tbody>
        @forelse($visits as $visit)
            <tr>
                <td class="mp-num">#{{ $visit->visit_number }} / @if(AdminGuardHasPerm('hourly_orders_view'))<a class="mp-link" href="{{ route('admin.hourly-orders.show',$visit->hourly_rental_order_id) }}">#{{ $visit->order?->order_number ?? $visit->hourly_rental_order_id }}</a>@else #{{ $visit->order?->order_number ?? $visit->hourly_rental_order_id }} @endif</td>
                <td class="mp-num" dir="ltr">{{ $visit->scheduled_start_at?->format('Y-m-d H:i') ?? 'لم يحدد' }} @if($visit->scheduled_end_at) — {{ $visit->scheduled_end_at->format('H:i') }} @endif</td>
                <td class="mp-num">{{ $visit->planned_hours }}</td>
                <td><span class="mp-badge {{ $mpTone($visit->status) }}">{{ __('hourly.'.$visit->status) }}</span></td>
            </tr>
        @empty
            <tr><td colspan="4"><div class="mp-empty"><i class="fa fa-calendar-xmark"></i> لا توجد زيارات مسندة للعاملة.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="mp-pager">{{ $visits->links() }}</div>
@else
    <div class="mp-empty mp-empty-lg"><i class="fa fa-link"></i><h5>{{ $tabs[$tab] }}</h5><p>هذا القسم قيد الربط ببيانات عاملة الساعات.</p></div>
@endif
