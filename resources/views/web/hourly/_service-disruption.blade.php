@php($disruption=$recovery['service_disruption']??null)
@if($disruption && $disruption['status']!=='resolved')
<div class="hr-notice" role="status">
    @if($disruption['status']==='manual_review')
        حدث ظرف طارئ أثناء التنفيذ. تتابع الإدارة الحالة والعودة والتسوية؛ تواصل مع خدمة العملاء.
    @else
        نعتذر، تعذر تنفيذ الزيارة لظرف طارئ. رصيد الزيارة محفوظ أثناء المعالجة.
        @if($disruption['customer_worker_selection'])
            اختر بديلة متاحة أو أرسل طلب إعادة الجدولة أو الإلغاء والاسترداد من الخيارات أدناه.
            @if(!empty($disruption['replacement_options']) && ($recovery['recovery_request']['status']??null)!=='pending')
            <form data-visit-recovery method="post" action="{{ route('web.hourly.visit.disruption.choose',[$order->id,$visit->id]) }}">
                @csrf<input type="hidden" name="disruption_id" value="{{ $disruption['id'] }}">
                <label>العاملة البديلة<select class="form-control" name="replacement_worker_id" required>
                    <option value="">اختر عاملة متاحة</option>
                    @foreach($disruption['replacement_options'] as $worker)
                        <option value="{{ $worker['id'] }}">{{ $worker['name'] }}</option>
                    @endforeach
                </select></label>
                <button type="submit" class="hr-btn secondary sm">تأكيد البديلة دون تكلفة إضافية</button><p data-recovery-feedback role="status"></p>
            </form>
            @else
                <p>إذا لم تتوفر بديلة، يمكنك طلب موعد آخر أو الإلغاء والاسترداد.</p>
            @endif
        @else
            تعمل الإدارة على توفير بديلة بنفس المواصفات دون تكلفة إضافية. يمكنك أيضًا طلب إعادة الجدولة أو الإلغاء والاسترداد.
        @endif
    @endif
</div>
@endif