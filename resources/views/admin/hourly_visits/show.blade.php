@extends('admin.layout.indexs.index')
@section('page-title','تفاصيل الزيارة #'.$visit->id)
@section('content')
<div class="container-fluid" dir="rtl">
    <div class="card mb-3"><div class="card-body"><h3>تفاصيل الزيارة #{{ $visit->id }}</h3><a href="{{ route('admin.hourly-visits.index') }}">قائمة الزيارات</a> · <a href="{{ route('admin.hourly-orders.show',$order) }}">الطلب {{ $order->order_number }}</a>
        @include('admin.hourly_visits._delay')
        @include('admin.hourly._cash-collector')
        @if($visit->is_missed)
            <p class="alert alert-warning mt-2">لم يبدأ تنفيذ هذه الزيارة. يجب إعادة جدولتها أو إلغاؤها مع تسجيل السبب.
                @if(AdminGuardHasPerm('hourly_orders_create'))<a href="{{ route('admin.hourly-visits.index', ['search'=>$visit->id,'from'=>'','to'=>'']) }}">معالجة الزيارة</a>@endif
            </p>
        @endif
        <p class="mt-3">العميل: <a href="{{ route('clients.show',$order->user_id) }}">{{ $order->user?->name }}</a> · {{ $visit->contact_phone }}</p><p>{{ $visit->address_text }} <a href="{{ $visit->map_url }}" target="_blank" rel="noopener">الخريطة</a></p>
        <p>الموعد: {{ $visit->scheduled_start_at?->format('Y-m-d H:i')??'لم يحدد' }} — {{ $visit->scheduled_end_at?->format('H:i') }} · {{ $visit->planned_hours }} ساعات</p>
        <p>الخدمة: {{ $order->serviceCategory?->name }} · الحالة: {{ \App\Services\Hourly\VisitSearch::STATUSES[$visit->display_status]??$visit->status }} · السعر: {{ number_format($visit->visit_price,2) }} ر.س</p>
        <a class="btn btn-outline-primary" href="{{ route('admin.hourly-visits.invoice',$visit) }}" target="_blank" rel="noopener">فاتورة الزيارة</a>
    </div></div>
    <div class="card mb-3"><div class="card-body"><h5>التعيينات</h5><p>العاملات ({{ $visit->workers->count() }} / {{ $visit->workers_count }}): @forelse($visit->workers as $worker)<a href="{{ route('admin.hourly-maids.show',$worker) }}">{{ $worker->name }}</a> @empty غير مخصص @endforelse</p><p>سائق التوصيل: {{ $visit->deliveryDriver?->name??'غير مخصص' }} · سائق الاستلام: {{ $visit->pickupDriver?->name??'غير مخصص' }} · قائد الفريق: {{ $visit->teamLeader?->name??'غير مخصص' }}</p></div></div>
    <div class="card mb-3"><div class="card-body"><h5>المذكرات</h5>@forelse($notes as $note)<p>{{ $note['text'] }} <small class="text-muted">{{ $note['author']??'' }} · {{ $note['created_at']??'' }}</small></p>@empty<p>لا توجد مذكرات لهذه الزيارة.</p>@endforelse</div></div>
    <div class="card mb-3"><div class="card-body"><h5>سجل الحالات والإجراءات والتعيينات</h5><div class="table-responsive"><table class="table"><thead><tr><th>التاريخ</th><th>الإجراء</th><th>المستخدم</th><th>السبب</th><th>التغيير</th></tr></thead><tbody>
    @forelse($logs as $log)
        @php $before=json_decode($log->before,true)??[];$after=json_decode($log->after,true)??[]; @endphp
        <tr><td>{{ $log->created_at }}</td><td>{{ \App\Services\Hourly\VisitBatchService::ACTIONS[$log->action]??['bulk_note'=>'مذكرة','bulk_collect'=>'تحصيل','refund'=>'استرداد'][$log->action]??$log->action }}</td><td>{{ $log->admin_name??'النظام / التطبيق' }}</td><td>{{ $log->reason }}</td><td>
            @foreach(['status'=>'الحالة','worker_ids'=>'معرّفات العاملات','delivery_driver_id'=>'سائق التوصيل','pickup_driver_id'=>'سائق الاستلام','scheduled_start_at'=>'الموعد','visit_price'=>'السعر','amount'=>'المبلغ','payment_id'=>'رقم الدفعة'] as $key=>$label)
                @if(array_key_exists($key,$after) && ($before[$key]??null)!==$after[$key])<p>{{ $label }}: {{ is_array($before[$key]??null)?implode('، ',$before[$key]):($before[$key]??'—') }} ← {{ is_array($after[$key])?implode('، ',$after[$key]):($after[$key]??'—') }}</p>@endif
            @endforeach
        </td></tr>
    @empty<tr><td colspan="5">لا توجد إجراءات مسجلة لهذه الزيارة.</td></tr>@endforelse
    </tbody></table></div>{{ $logs->links() }}</div></div>
    <div class="card"><div class="card-body"><h5>الدفعات</h5>@include('admin.hourly_visits._payments')</div></div>
</div>
@endsection
