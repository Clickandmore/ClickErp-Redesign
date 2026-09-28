{{-- تقويم زيارات العميل: شهري أو أسبوعي، والأسبوع يبدأ السبت. --}}
@php
    $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace);
    $sep = app()->getLocale() === 'ar' ? '، ' : ', ';
    $link = fn (array $query) => route('admin.hourly-customers.show', [$customer->id, 'calendar']).'?'.http_build_query($query);
    $step = $view === 'week' ? ['subWeek', 'addWeek'] : ['subMonthNoOverflow', 'addMonthNoOverflow'];
    $prev = $anchor->copy()->{$step[0]}()->toDateString();
    $next = $anchor->copy()->{$step[1]}()->toDateString();
    $title = $view === 'week' ? $from->translatedFormat('d M').' – '.$to->translatedFormat('d M Y') : $anchor->translatedFormat('F Y');
    $statusClass = fn ($status) => in_array($status, ['completed', 'delivered_back', 'done'], true) ? 'is-completed' : (str_contains((string) $status, 'cancel') ? 'is-cancelled' : '');
@endphp
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="hc-seg" role="group" aria-label="{{ $c('show.tabs.calendar') }}">
        @foreach(['month', 'week'] as $mode)
            <a class="hc-btn is-sm {{ $view === $mode ? 'is-primary' : '' }}" href="{{ $link(['view' => $mode, 'date' => $anchor->toDateString()]) }}">{{ $c('calendar.'.$mode) }}</a>
        @endforeach
    </div>
    <strong>{{ $title }}</strong>
    <div class="d-flex gap-1">
        <a class="hc-btn is-sm" href="{{ $link(['view' => $view, 'date' => $prev]) }}" aria-label="{{ $c('calendar.prev') }}"><i class="fa fa-chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i></a>
        <a class="hc-btn is-sm" href="{{ $link(['view' => $view]) }}">{{ $c('calendar.today') }}</a>
        <a class="hc-btn is-sm" href="{{ $link(['view' => $view, 'date' => $next]) }}" aria-label="{{ $c('calendar.next') }}"><i class="fa fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"></i></a>
    </div>
</div>

<div class="hc-cal {{ $view === 'week' ? 'is-week' : '' }}">
    @foreach(__('hourly_customers.calendar.weekdays') as $weekday)<div class="hc-cal-head">{{ $weekday }}</div>@endforeach
    @for($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay())
        @php $visits = $byDay[$day->toDateString()] ?? collect(); @endphp
        <div class="hc-cal-day {{ $view === 'month' && $day->month !== $anchor->month ? 'is-out' : '' }} {{ $day->isToday() ? 'is-today' : '' }}">
            <span class="hc-cal-num">{{ $day->day }}<span class="d-md-none"> · {{ $day->translatedFormat('l') }}</span></span>
            @foreach($visits as $visit)
                <a class="hc-cal-visit {{ $statusClass($visit->status) }}" href="{{ route('admin.hourly-visits.show', $visit->id) }}"
                   title="{{ __('hourly_routing.visit_statuses.'.$visit->status) }}">
                    <b dir="ltr">{{ $visit->scheduled_start_at?->format('H:i') }}–{{ $visit->scheduled_end_at?->format('H:i') }}</b> · {{ $visit->visit_number }}
                    @if($visit->hourly_team_id && isset($teams[$visit->hourly_team_id]))<br><i class="fa fa-people-group"></i> {{ $teams[$visit->hourly_team_id] }}@endif
                    @if($visit->workers->isNotEmpty())<br><i class="fa fa-user"></i> {{ $visit->workers->pluck('name')->implode($sep) }}@endif
                </a>
            @endforeach
        </div>
    @endfor
</div>
@if($byDay->isEmpty())<div class="hc-empty mt-2">{{ $c('calendar.empty') }}</div>@endif
