@php
    // Reuse the same timing rules as visit alerts, without querying each worker's visit again.
    $currentVisit = new \App\Models\Hourly\HourlyVisit([
        'status' => $trip['status'], 'scheduled_end_at' => $trip['until'],
    ]);
    $tripDelay = \App\Services\Hourly\VisitDelay::state($currentVisit);
    [$phaseClass, $phaseIcon, $phaseLabel] = match ($trip['status']) {
        'en_route' => ['mh-now-transit', 'fa-truck', 'في الطريق إلى العميل'],
        'delivered' => ['mh-now-visit', 'fa-location-dot', 'لدى العميل'],
        'in_progress' => ['mh-now-visit', 'fa-broom', 'تؤدي الخدمة'],
        'worker_finished' => ['mh-now-waiting', 'fa-clock', 'بانتظار الاستلام'],
        'picked_up' => ['mh-now-transit', 'fa-house-user', 'في طريق العودة للإيواء'],
        default => ['mh-now-visit', 'fa-person-walking-arrow-right', 'في زيارة ساعات'],
    };
@endphp
<span class="mh-now {{ $phaseClass }}" title="العاملة خارج الإيواء حتى تأكيد عودتها"><i class="fa {{ $phaseIcon }}" aria-hidden="true"></i> {{ $phaseLabel }}</span>
@if($tripDelay)
    @php
        $delayLabel = match (true) {
            $tripDelay['stage'] === 'ended' => 'انتهى وقت الخدمة',
            $tripDelay['stage'] === 'escalated' => 'تصعيد التأخير',
            $trip['status'] === 'picked_up' => 'متأخرة عن العودة',
            $trip['status'] === 'worker_finished' => 'متأخرة عن الاستلام',
            default => 'تجاوز موعد الزيارة',
        };
    @endphp
    <div class="mt-1"><span class="mh-now mh-now-{{ $tripDelay['stage'] }}"><i class="fa fa-triangle-exclamation" aria-hidden="true"></i> {{ $delayLabel }}</span></div>
    <div class="mh-now-meta">{{ $tripDelay['minutes'] }} دقيقة بعد الموعد</div>
@endif
