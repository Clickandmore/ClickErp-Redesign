@php
    $missedQuery = \App\Models\Hourly\HourlyVisit::where('status','scheduled')->where('scheduled_end_at','<=',now())
        ->whereHas('order',fn($q)=>$q->whereIn('status',['active','pending_confirmation']));
    $missedCount = (clone $missedQuery)->count();
    $missedRows = $missedQuery->orderBy('scheduled_end_at')->limit(20)->get();
@endphp
@if($missedCount)
<div class="alert alert-danger mx-3" role="alert" dir="rtl">
    <strong>{{ $missedCount }} زيارة فات موعدها دون تنفيذ وتحتاج إجراءً.</strong>
    <p>أعد جدولة الزيارة أو ألغها مع تحديد التسوية المالية وتسجيل السبب. لا تُحتسب الزيارة خدمةً منفّذة.</p>
    @foreach($missedRows as $missedVisit)
        <div><a href="{{ route('admin.hourly-visits.index',['search'=>$missedVisit->id,'from'=>'','to'=>'']) }}">معالجة زيارة #{{ $missedVisit->id }}</a>
            · {{ $missedVisit->scheduled_start_at?->format('Y-m-d H:i') }}</div>
    @endforeach
</div>
@endif
