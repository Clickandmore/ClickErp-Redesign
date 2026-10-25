@php($recovery = app(\App\Services\Hourly\CustomerHourlyData::class)->visit($visit,$order))
@include('web.hourly._service-disruption')
@if(!empty($recovery['worker_replaced_at']))
    <p class="hr-notice">تم توفير عاملة بديلة لهذه الزيارة بنفس مواصفات الخدمة ودون تكلفة إضافية. بيانات العاملات المعروضة هي التعيينات الحالية.</p>
@endif
@if(!empty($recovery['coverage_recovery']))
    <p class="hr-notice">تعذّر جدولة الزيارة بسبب توقف التغطية في منطقتك. يمكنك طلب تنفيذها دون دفع جديد بعد توفير الخدمة، أو إلغائها واسترداد المدفوع عنها بعد مراجعة الإدارة. لا تعتمد الجدولة إلا بعد توفر التغطية والموعد.</p>
@endif
@if(!empty($recovery['recovery_request']))
    @php($recoveryRequest = $recovery['recovery_request'])
    <p class="hr-notice">طلب {{ ['refund'=>'الإلغاء والاسترداد','replacement'=>'العاملة البديلة'][$recoveryRequest['type']] ?? 'إعادة الجدولة' }}:
        {{ ['pending'=>'قيد مراجعة الإدارة','approved'=>'تمت الموافقة','rejected'=>'لم تتم الموافقة'][$recoveryRequest['status']] }}
        @if(isset($recoveryRequest['refunded_amount'])) · تمت إعادة {{ number_format($recoveryRequest['refunded_amount'],2) }} ر.س إلى المحفظة @endif
        @if(!empty($recoveryRequest['new_start'])) · الموعد الجديد {{ \Carbon\Carbon::parse($recoveryRequest['new_start'])->format('Y-m-d H:i') }} @endif
        @if(!empty($recoveryRequest['review_reason'])) · {{ $recoveryRequest['review_reason'] }} @endif
    </p>
@endif
@if(!empty($recovery['can_request_recovery']) && ($recovery['recovery_request']['status']??null)!=='pending')
    @if(!empty($recovery['can_request_replacement']))
    <details class="mt-2"><summary>طلب عاملة بديلة من الإدارة لنفس الموعد</summary>
        <form data-visit-recovery method="post" action="{{ route('web.hourly.visit.recovery',[$order->id,$visit->id]) }}" class="mt-2">
            @csrf<input type="hidden" name="type" value="replacement"><input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
            <label>ملاحظات أو تفضيلات<textarea class="form-control" name="note" maxlength="1000" placeholder="مثال: أفضّل عاملة تتحدث العربية"></textarea></label>
            <p>تبقى الزيارة في موعدها دون تكلفة إضافية. تبحث الإدارة عن بديلة بنفس مواصفات الخدمة وتبلغك بالنتيجة؛ إن تعذر التوفير يمكنك إعادة الجدولة أو الإلغاء والاسترداد.</p>
            <button type="submit" class="hr-btn secondary sm">إرسال طلب العاملة البديلة</button><p data-recovery-feedback role="status"></p>
        </form>
    </details>
    @endif
    <details class="mt-2"><summary>طلب إعادة الجدولة دون دفع جديد</summary>
        <form data-visit-recovery method="post" action="{{ route('web.hourly.visit.recovery',[$order->id,$visit->id]) }}" class="mt-2">
            @csrf<input type="hidden" name="type" value="reschedule"><input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
            <label>التاريخ<input class="form-control" data-recovery-date type="date" min="{{ now()->toDateString() }}" required data-slots-url="{{ route('web.hourly.visit.recovery-slots',[$order->id,$visit->id]) }}"></label>
            <label>الساعات المتاحة<select class="form-control" name="requested_start" data-recovery-times required disabled><option value="">اختر التاريخ أولًا</option></select></label>
            <p data-recovery-slots-feedback role="status"></p>
            <label>ملاحظات<textarea class="form-control" name="note" maxlength="1000"></textarea></label>
            <p>تحتفظ الزيارة بقيمتها. تعتمد الإدارة الموعد بعد التحقق من عدم التنفيذ والتوفر.</p>
            <button type="submit" class="hr-btn secondary sm">إرسال طلب إعادة الجدولة</button><p data-recovery-feedback role="status"></p>
        </form>
    </details>
    <details class="mt-2"><summary>طلب الإلغاء واسترداد قيمة الزيارة</summary>
        <form data-visit-recovery method="post" action="{{ route('web.hourly.visit.recovery',[$order->id,$visit->id]) }}" class="mt-2">
            @csrf<input type="hidden" name="type" value="refund"><input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
            <label>ملاحظات<textarea class="form-control" name="note" maxlength="1000"></textarea></label>
            <p>بعد التحقق من عدم تنفيذ الخدمة من طرف الشركة، تُلغى هذه الزيارة دون رسوم ويُعاد المبلغ المسدد لها إلى محفظتك. باقي زيارات الباقة مستمرة.</p>
            <label><input type="checkbox" required> أوافق على إعادة المبلغ إلى المحفظة.</label>
            <button type="submit" class="hr-btn secondary sm">إرسال طلب الإلغاء والاسترداد</button><p data-recovery-feedback role="status"></p>
        </form>
    </details>
@endif
