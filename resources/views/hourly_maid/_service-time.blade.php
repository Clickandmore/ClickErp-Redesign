@php($timeCheck=$task['service_time']??[])
@if(!empty($timeCheck['requires_reason']))
<details style="margin:12px 0;padding:12px;border:1px solid #f0d499;border-radius:10px;background:#fff9ed">
    <summary>تجاوزت مدة الخدمة — سجّلي سبب التأخير</summary>
    <p>تجاوز الوقت {{ $timeCheck['overrun_minutes'] }} دقيقة. لا يعني ذلك تحميل العميل مبلغًا إضافيًا، ولا يُغني هذا البلاغ عن تسجيل الانتهاء أو العودة.</p>
    <form method="post" action="{{ route('hourly-maid.visits.time-overrun',$task['id']) }}">@csrf
        {{-- «نسيت تسجيل الانتهاء» يتطلب وقت الانتهاء الفعلي في الخادم؛ الحقل يظهر ويصبح إلزاميًا عند اختياره فقط. --}}
        <label>السبب<select name="reason" required onchange="var t=this.form.elements.actual_finished_at,f=this.value==='forgot_finish';t.required=f;t.closest('label').hidden=!f;if(!f)t.value='';"><option value="">اختاري السبب</option>@foreach($timeCheck['reason_options'] as $key=>$label)<option value="{{ $key }}" {{ old('reason')===$key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></label>
        <label @if(old('reason')!=='forgot_finish') hidden @endif>وقت الانتهاء الفعلي الذي نسيتِ تسجيله<input type="datetime-local" name="actual_finished_at" value="{{ old('actual_finished_at') }}" max="{{ now()->format('Y-m-d\TH:i') }}" @if(old('reason')==='forgot_finish') required @endif></label>
        <label>توضيح داخلي للإدارة<textarea name="note" maxlength="1000"></textarea></label><button type="submit">إرسال السبب للمراجعة</button>
    </form>
</details>
@elseif(($timeCheck['review_status']??null)==='awaiting_admin')
<p role="status">تم تسجيل سبب تجاوز المدة، وبانتظار مراجعة الإدارة. لا توجد رسوم إضافية تلقائية.</p>
@endif