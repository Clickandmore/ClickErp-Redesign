@extends('admin.layout.indexs.index')
@section('page-title','متابعة تجاوز مدة الخدمة')
@section('style')
<style>
.st-page{max-width:1450px;margin:auto;padding:24px 14px;color:#17324b}.st-head{padding:24px;border-radius:16px;background:linear-gradient(110deg,#16344f,#217386);color:white;display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap;margin-bottom:20px}.st-head h3{color:#fff;margin:0 0 8px;font-weight:800}.st-head p{margin:0;font-size:13px;color:#dcebf1}.st-card{background:#fff;border:1px solid #e0e9ef;border-radius:14px;padding:20px;margin:15px 0}.st-top{display:flex;justify-content:space-between;gap:14px;align-items:center;flex-wrap:wrap}.st-card h5{font-size:16px;font-weight:700;margin:0}.st-meta{display:flex;flex-wrap:wrap;gap:12px 24px;color:#687f92;font-size:12px;margin:14px 0}.st-badge{border-radius:8px;padding:5px 12px;background:#fff1d7;color:#986822;font-size:12px}.st-note{background:#f5f8fb;border-radius:9px;padding:12px;font-size:13px;overflow-wrap:anywhere}.st-review{padding-top:15px;margin-top:15px;border-top:1px solid #e9eef4}.st-grid{display:grid;grid-template-columns:1fr 2fr;gap:15px}.st-review label{display:block;font-size:13px}.st-tabs{display:flex;gap:8px;flex-wrap:wrap}.st-tabs a{padding:9px 15px;border:1px solid #e2eaf1;border-radius:9px;background:#fff}.st-tabs a.active{background:#e6f3f1;color:#08796e;font-weight:bold}@media(max-width:700px){.st-grid{grid-template-columns:1fr}}
</style>
@endsection
@section('content')
<div class="st-page" dir="rtl">
    <header class="st-head"><div><h3>متابعة تجاوز مدة الخدمة</h3><p>{{ $pending }} حالة تنتظر التحقق. انتهاء الوقت وحده لا يولّد رسومًا أو يثبت أن العميل طلب تمديدًا.</p></div>
    @if(AdminGuardHasPerm('hourly_orders_create'))<form method="post" action="{{ route('admin.hourly-service-time.scan') }}">@csrf<button class="btn btn-light" type="submit">فحص الزيارات الآن</button></form>@endif</header>
    @include('admin.hourly.messages')
    <p class="text-muted">النهاية تُحسب من الوصول المسجّل + مدة الخدمة والساعات المعتمدة. الفحص اليدوي متاح عند توقف الكرون. القرار أدناه يوثّق المراجعة فقط؛ أي تسوية مالية تتطلب إجراءً مستقلًا موثّقًا وموافقة لازمة.</p>
    <nav class="st-tabs">@foreach(['open'=>'تحتاج مراجعة','resolved'=>'تمت المراجعة','all'=>'كل السجلات'] as $key=>$label)<a class="{{ $status===$key?'active':'' }}" href="{{ route('admin.hourly-service-time.index',['status'=>$key]) }}">{{ $label }}</a>@endforeach</nav>
    @forelse($checks as $check)
    <article class="st-card">
        <div class="st-top"><h5>{{ $check->worker_name }} · <a href="{{ route('admin.hourly-orders.show',$check->order_id) }}">{{ $check->order_number }}</a> · الزيارة #{{ $check->visit_id }}</h5><span class="st-badge">{{ ['reminded'=>'تم تذكير العميل','awaiting_reason'=>'بانتظار السبب','awaiting_admin'=>'بانتظار الإدارة','resolved'=>'تمت المراجعة','superseded'=>'تغيرت مدة الخدمة'][$check->status]??$check->status }}</span></div>
        <div class="st-meta"><span>بداية الخدمة: <bdi>{{ $check->started_at }}</bdi>{{ $check->started_from_schedule?' (وقت الموعد — لا يوجد وصول مسجّل)':'' }}</span><span>نهاية المدة: <bdi>{{ $check->expected_end_at }}</bdi></span>@if($check->finish_reported_at)<span>سُجل الانتهاء: <bdi>{{ $check->finish_reported_at }}</bdi></span>@endif</div>
        <div class="st-note"><strong>{{ \App\Services\Hourly\ServiceTime::REASONS[$check->reason]??'لم يصل توضيح السبب بعد' }}</strong>@if($check->worker_note)<p>{{ $check->worker_note }}</p>@endif @if($check->claimed_finished_at)<p>وقت الانتهاء الذي أبلغت عنه العاملة: <bdi>{{ $check->claimed_finished_at }}</bdi> — يحتاج تحققًا ولا يعدّل سجل التنفيذ تلقائيًا.</p>@endif</div>
        @if(in_array($check->status,['awaiting_reason','awaiting_admin'],true) && AdminGuardHasPerm('hourly_orders_create'))
        <form class="st-review" method="post" action="{{ route('admin.hourly-service-time.review',$check->id) }}">@csrf
            <div class="st-grid"><label>القرار<select class="form-control" name="decision" required>@foreach(\App\Services\Hourly\ServiceTime::DECISIONS as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
            <label>سبب القرار ومرجع التحقق أو التسوية<textarea class="form-control" name="note" maxlength="2000" required></textarea></label></div>
            <label class="my-3"><input type="checkbox" name="verified" value="1" required> تحققت من السبب وأي موافقة للعميل أو تسوية مستقلة. هذا القرار لا يخصم أو يعيد مبلغًا تلقائيًا.</label>
            <button type="submit" class="btn btn-primary">توثيق القرار وإغلاق المتابعة</button>
        </form>
        @elseif($check->status==='resolved')<p class="mt-3">{{ \App\Services\Hourly\ServiceTime::DECISIONS[$check->decision]??$check->decision }} — {{ $check->decision_note }}</p>@endif
    </article>
    @empty<div class="st-card text-center text-muted">لا توجد حالات مطابقة. استخدم «فحص الزيارات الآن» لتحديث المتابعة.</div>@endforelse
    {{ $checks->links() }}
</div>
@endsection