@php
    $reportable=\Illuminate\Support\Facades\Schema::hasTable('hourly_service_incidents') ? \App\Models\Hourly\HourlyVisit::whereHas('workers',fn($q)=>$q->where('maids.id',$maid->id))
        ->whereNotIn('status',['completed','cancelled','cancelled_by_client','expired','no_show'])->orderBy('scheduled_start_at')->limit(30)->get() : collect();
@endphp
@if($reportable->isNotEmpty())
<section class="dp-panel wp-incidents">
    <div class="dp-panel-head">
        <div class="dp-title"><span class="dp-title-icon is-danger"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg></span><h2>الإبلاغ عن ظرف يمنع تنفيذ زيارة</h2></div>
    </div>
    <p class="dp-muted wp-incidents-lead">مرض أو حادث أو ظرف طارئ؟ اختاري الزيارة وأرسلي البلاغ؛ تتحقق منه الإدارة وتعالج الزيارة مع العميل. التفاصيل لا تظهر للعميل.</p>
    @foreach($reportable as $reportedVisit)
        @php($existingReport=\App\Services\Hourly\ServiceIncident::openReport($reportedVisit->id,$maid->id))
        <details class="wp-incident {{ $existingReport ? 'has-report' : '' }}">
            <summary><span>زيارة #{{ $reportedVisit->id }}</span><bdi dir="ltr">{{ $reportedVisit->scheduled_start_at?->format('Y-m-d H:i') }}</bdi></summary>
            @if($existingReport)
                <p class="wp-incident-state" role="status"><b>بلاغ قائم #{{ $existingReport->id }}</b> — {{ $existingReport->status==='pending'?'بانتظار تحقق الإدارة؛ لا حاجة لإرسال بلاغ آخر.':'معتمد وتتابع الإدارة معالجته.' }}</p>
            @else
                <form method="post" action="{{ route('hourly-maid.visits.incidents',$reportedVisit->id) }}">
                    @csrf<input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
                    <label>نوع البلاغ<select name="category"><option value="illness">مرض</option><option value="accident">حادث</option><option value="emergency">ظرف طارئ</option></select></label>
                    <label>تفاصيل للإدارة فقط<textarea name="note" required maxlength="2000" rows="3"></textarea></label>
                    <button class="dp-btn wp-danger-btn">إرسال البلاغ للتحقق</button>
                </form>
            @endif
        </details>
    @endforeach
</section>
@endif
