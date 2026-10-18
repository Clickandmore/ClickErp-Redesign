@foreach($recoveryOrders as $recoveryOrder)
@foreach(($recoveryOrder->booking_metadata['missed_visit_requests']??[]) as $recoveryId=>$recoveryRequest)
<article class="card p-3 mb-3">
    @if(($recoveryRequest['reason_code']??null)==='coverage_unavailable')<p class="alert alert-warning">زيارة غير مستخدمة تعذرت جدولتها بسبب التغطية. لاعتماد التنفيذ أعد توفير التغطية أولًا؛ تُمدد الصلاحية للموعد المعتمد دون دفع جديد. الاسترداد يخص المدفوع لهذه الزيارة فقط.</p>@endif
    <h6>الطلب {{ $recoveryOrder->order_number }} · مرجع الزيارة #{{ $recoveryId }} · {{ $recoveryRequest['type']==='refund'?'طلب إلغاء واسترداد للمحفظة':'طلب إعادة جدولة دون رسوم' }}</h6>
    <p>الحالة: {{ ['pending'=>'قيد المراجعة','approved'=>'موافق عليه','rejected'=>'مرفوض'][$recoveryRequest['status']] }} · تاريخ الطلب: {{ $recoveryRequest['requested_at'] }}</p>
    <p>{{ $recoveryRequest['note']??'' }}</p>
    @if($recoveryRequest['status']==='pending' && AdminGuardHasPerm('hourly_orders_create'))
    <form method="post" action="{{ route('admin.hourly-recovery.review',[$recoveryOrder->id,$recoveryId]) }}">
        @csrf<input type="hidden" name="request_id" value="{{ $recoveryRequest['id'] }}">
        @if($recoveryRequest['type']==='reschedule')<label>الموعد الجديد<input class="form-control" type="datetime-local" name="new_start" value="{{ $recoveryRequest['requested_start'] }}"></label>@endif
        <label class="d-block"><input type="checkbox" name="verified_non_execution" value="1"> تحققت من عدم تنفيذ الخدمة من طرف الشركة؛ أوافق على المعالجة دون رسوم.</label>
        <label>سبب القرار — يظهر للعميل؛ لا تكتب تفاصيل صحية أو داخلية<textarea class="form-control" name="reason" maxlength="2000" required></textarea></label>
        @if($recoveryRequest['type']!=='refund' || AdminGuardHasPerm('hourly_orders_collect'))<button type="submit" name="decision" value="approve" class="btn btn-success">اعتماد وتنفيذ</button>@endif
        <button type="submit" name="decision" value="reject" class="btn btn-outline-danger">رفض مع ذكر السبب</button>
    </form>
    @else
        <p>{{ $recoveryRequest['review_reason']??'' }}</p>
        @if(isset($recoveryRequest['refunded_amount']))<p>المسترد إلى المحفظة: {{ number_format($recoveryRequest['refunded_amount'],2) }} ر.س</p>@endif
    @endif
</article>
@endforeach
@endforeach
