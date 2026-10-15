@include('admin.hourly_visits._missed-alerts')
@php
    // Includes previous days, independently of the list's date filters.
    $delayedVisits = \App\Models\Hourly\HourlyVisit::whereIn('status', \App\Services\Hourly\ShelterPresence::OUT_STATUSES)
        ->where('scheduled_end_at', '<=', now())->whereHas('order', fn($q) => $q->where('status','active'));
    $delayedCount = (clone $delayedVisits)->count();
    $delayedRows = $delayedVisits->orderBy('scheduled_end_at')->limit(20)->get();
@endphp
@if($delayedCount)
<div class="alert alert-warning mx-3" role="alert" dir="rtl">
    <strong>{{ $delayedCount }} زيارة انتهى وقتها والعاملات لم تُسجّل عودتهن للإيواء.</strong>
    <p>تابع العاملة وسائق العودة. انتهاء الوقت لا يؤكد الرجوع للإيواء.</p>
    @foreach($delayedRows as $delayedVisit)
        <div class="mb-2"><a href="{{ route('admin.hourly-visits.show', $delayedVisit) }}">زيارة #{{ $delayedVisit->id }}</a>
            @include('admin.hourly_visits._delay', ['visit' => $delayedVisit])
        </div>
    @endforeach
</div>
@endif
