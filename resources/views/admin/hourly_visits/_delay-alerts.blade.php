@include('admin.hourly_visits._missed-alerts')
@php
    // Includes previous days, independently of the list's date filters.
    $delayedVisits = \App\Models\Hourly\HourlyVisit::whereIn('status', \App\Services\Hourly\ShelterPresence::OUT_STATUSES)
        ->where('scheduled_end_at', '<=', now())->whereHas('order', fn($q) => $q->where('status','active'));
    $delayedCount = (clone $delayedVisits)->count();
    $delayedRows = $delayedVisits->with('order.user:id,name')->orderBy('scheduled_end_at')->limit(20)->get();
@endphp
@if($delayedCount)
@include('admin.hourly_visits._alert-style')
<section class="va is-warn" role="alert" aria-labelledby="va-delay-title">
    <div class="va-head">
        <span class="va-icon" aria-hidden="true"><i class="fa fa-house-circle-exclamation"></i></span>
        <div class="va-text">
            <h6 id="va-delay-title"><span class="va-count">{{ $delayedCount }}</span> {{ $delayedCount === 1 ? 'زيارة انتهى وقتها' : 'زيارات انتهى وقتها' }} والعاملات لم تُسجَّل عودتهن للإيواء</h6>
            <p>تابع العاملة وسائق العودة. انتهاء الوقت لا يؤكد الرجوع للإيواء.</p>
        </div>
    </div>
    <div class="va-list">
        @foreach($delayedRows as $delayedVisit)
            <div class="va-item" @if($loop->index >= 6) hidden data-va-extra @endif>
                <div class="va-item-main">
                    <b>#{{ $delayedVisit->id }} · {{ $delayedVisit->order?->user?->name ?: 'عميل محذوف' }}</b>
                    @if($delayedVisit->scheduled_end_at)<span class="va-ago">انتهى وقتها {{ $delayedVisit->scheduled_end_at->diffForHumans() }}</span>@endif
                    @include('admin.hourly_visits._delay', ['visit' => $delayedVisit])
                </div>
                <a class="va-go" href="{{ route('admin.hourly-visits.show', $delayedVisit) }}"><i class="fa fa-eye"></i> متابعة</a>
            </div>
        @endforeach
        @if($delayedRows->count() > 6)
            <button type="button" class="va-more" onclick="this.parentElement.querySelectorAll('[data-va-extra]').forEach(el => el.hidden = false); this.remove();">عرض الباقي ({{ $delayedRows->count() - 6 }})</button>
        @endif
    </div>
</section>
@endif
