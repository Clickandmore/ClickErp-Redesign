@php
    $missedQuery = \App\Models\Hourly\HourlyVisit::where('status','scheduled')->where('scheduled_end_at','<=',now())
        ->whereHas('order',fn($q)=>$q->whereIn('status',['active','pending_confirmation']));
    $missedCount = (clone $missedQuery)->count();
    $missedRows = $missedQuery->with('order.user:id,name')->orderBy('scheduled_end_at')->limit(20)->get();
@endphp
@if($missedCount)
@include('admin.hourly_visits._alert-style')
<section class="va" role="alert" aria-labelledby="va-missed-title">
    <div class="va-head">
        <span class="va-icon" aria-hidden="true"><i class="fa fa-calendar-xmark"></i></span>
        <div class="va-text">
            <h6 id="va-missed-title"><span class="va-count">{{ $missedCount }}</span> {{ $missedCount === 1 ? 'زيارة فات موعدها' : 'زيارات فات موعدها' }} دون تنفيذ وتحتاج إجراءً</h6>
            <p>أعد جدولة الزيارة أو ألغها مع تحديد التسوية المالية وتسجيل السبب. لا تُحتسب الزيارة خدمةً منفّذة.</p>
        </div>
        <a class="va-all" href="{{ route('admin.hourly-visits.past', ['from' => '', 'to' => '']) }}">كل الزيارات الفائتة <i class="fa fa-arrow-left"></i></a>
    </div>
    <div class="va-list">
        @foreach($missedRows as $missedVisit)
            <div class="va-item" @if($loop->index >= 6) hidden data-va-extra @endif>
                <div class="va-item-main">
                    <b>#{{ $missedVisit->id }} · {{ $missedVisit->order?->user?->name ?: 'عميل محذوف' }}</b>
                    <span dir="ltr" class="text-end">{{ $missedVisit->scheduled_start_at?->format('Y-m-d H:i') }}</span>
                    @if($missedVisit->scheduled_start_at)<span class="va-ago">موعدها كان {{ $missedVisit->scheduled_start_at->diffForHumans() }}</span>@endif
                </div>
                <a class="va-go" href="{{ route('admin.hourly-visits.index',['search'=>$missedVisit->id,'from'=>'','to'=>'']) }}"><i class="fa fa-screwdriver-wrench"></i> معالجة</a>
            </div>
        @endforeach
        @if($missedRows->count() > 6)
            <button type="button" class="va-more" onclick="this.parentElement.querySelectorAll('[data-va-extra]').forEach(el => el.hidden = false); this.remove();">عرض الباقي ({{ $missedRows->count() - 6 }})</button>
        @endif
    </div>
</section>
@endif
