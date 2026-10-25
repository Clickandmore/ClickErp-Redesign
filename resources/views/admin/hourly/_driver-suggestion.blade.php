@php
    /* زيارة متوقفة على سائق (DriverGaps): قبل الموعد نعرض المقترح بنفس قواعد الإسناد التلقائي ويُسند بضغطة عبر auto_driver المسجل في السجل؛
       بعد فوات الموعد لا إسناد تلقائي، فنبيّن التأخير ويُعيَّن السائق يدويًا أو يُعاد جدولة الزيارة. */
    $order = $order ?? $visit->order;
    $missingRoles = \App\Services\Hourly\DriverGaps::missingRoles($visit);
    $canSuggest = $missingRoles && in_array($order?->status, ['active', 'pending_confirmation'], true) && AdminGuardHasPerm('hourly_orders_create');
    $driverLate = $canSuggest && $visit->scheduled_start_at->isPast();
    $driverPicks = $canSuggest && !$driverLate ? app(\App\Services\Hourly\NearestDriverAssignment::class)->suggest($visit, 15, $missingRoles) : [];
    $assignableRoles = array_keys(array_filter($driverPicks));
@endphp
@if($canSuggest)
    <div class="hr-driver-suggest {{ $driverLate ? 'is-late' : '' }}">
        @if($driverLate)
            <span class="hr-ds-none"><i class="fa fa-triangle-exclamation"></i> متأخرة {{ \App\Services\Hourly\DriverGaps::startsIn($visit->scheduled_start_at) }} بدون سائق {{ collect($missingRoles)->map(fn ($role) => \App\Services\Hourly\DriverGaps::roleLabel($role))->implode(' و') }} — عيّن السائق يدويًا أو أعد جدولة الزيارة</span>
        @elseif(!$assignableRoles)
            <span class="hr-ds-none"><i class="fa fa-triangle-exclamation"></i> لا يوجد سائق متاح من فريق الزيارة في هذا الموعد</span>
        @else
            <span class="hr-ds-text"><i class="fa fa-lightbulb"></i> مقترح:
                @foreach($driverPicks as $role => $pick)
                    {{ \App\Services\Hourly\DriverGaps::roleLabel($role) }} <b>{{ $pick['driver']->name ?? 'لا يوجد متاح' }}</b>@if(!$loop->last) · @endif
                @endforeach
            </span>
            <form method="POST" action="{{ route('admin.hourly-orders.operations', $order) }}"
                  data-swal-confirm="إسناد السائق المقترح؟" data-swal-text="زيارة {{ $visit->visit_number }} — {{ collect($driverPicks)->filter()->map(fn ($pick, $role) => \App\Services\Hourly\DriverGaps::roleLabel($role).': '.$pick['driver']->name)->implode(' · ') }}" data-swal-ok="نعم، أسند">
                @csrf
                <input type="hidden" name="action" value="auto_driver">
                <input type="hidden" name="visit_ids[]" value="{{ $visit->id }}">
                <input type="hidden" name="driver_direction" value="{{ count($assignableRoles) === 2 ? 'together' : $assignableRoles[0] }}">
                <input type="hidden" name="reason" value="إسناد السائق المقترح">
                <button type="submit" class="hr-ds-btn"><i class="fa fa-check"></i> إسناد المقترح</button>
            </form>
        @endif
    </div>
    @include('admin.hourly._swal-confirm')
    @once
        <style>
            .hr-driver-suggest{display:flex;flex-wrap:wrap;align-items:center;gap:.4rem .6rem;margin-top:.45rem;padding:.4rem .6rem;border:1px dashed #9cc7ec;border-radius:9px;background:#f4f9ff;font-size:.8rem;color:#245a86}
            .hr-driver-suggest.is-late{border-color:#f5b4b4;background:#fff5f5}
            .hr-driver-suggest form{margin:0}
            .hr-ds-btn{border:0;border-radius:7px;background:#1674bd;color:#fff;padding:.2rem .65rem;font-size:.78rem;font-weight:700}
            .hr-ds-btn:hover{background:#105f9c}
            .hr-ds-none{color:#b42a2e}
        </style>
    @endonce
@endif
