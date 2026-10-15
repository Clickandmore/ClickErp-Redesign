@php
    /* أزرار إجراءات الزيارة موزعة على أعمدة الجدول: $group يحدد المجموعة المعروضة. */
    $groups = [
        'money' => ['price' => ['تغيير السعر', 'om-btn-warn', 'fa-dollar-sign'], 'duration' => ['تغيير المدة', 'om-btn-info', 'fa-clock']],
        'staff' => ['assign_workers' => ['اختيار العاملات', 'om-btn-soft', 'fa-user-check'], 'add_worker' => ['إضافة عامل', 'om-btn-soft', 'fa-user-plus'], 'assign_driver' => ['تعيين السائق', 'om-btn-soft', 'fa-van-shuttle'], 'team_leader' => ['تعيين قائد الفريق', 'om-btn-green', 'fa-user-tie']],
        'row' => ['cancel_visit' => ['إلغاء الزيارة', 'om-btn-danger', 'fa-ban'], 'complete' => ['إكمال', 'om-btn-violet', 'fa-circle-check']],
    ];
    if ($visit->is_missed) {
        unset($groups['row']['complete']);
        $groups['row']['reschedule'] = ['إعادة جدولة الزيارة الفائتة', 'om-btn-warn', 'fa-calendar-days'];
    }
    $locked = in_array($visit->status, ['completed', 'cancelled', 'expired', 'no_show', 'cancelled_by_client']);
    $allowed = AdminGuardHasPerm('hourly_orders_create') && in_array($order->status, ['active', 'pending_confirmation']) && !$locked;
@endphp
@if($allowed)
    @foreach(isset($group) ? ($groups[$group] ?? []) : array_merge(...array_values($groups)) as $action => $button)
        @if(!in_array($action, ['price', 'duration', 'add_worker']) || AdminGuardHasPerm('hourly_orders_price_override'))
            <button type="button" class="om-btn {{ $button[1] }}" data-order-action="{{ $action }}" data-visit="{{ $visit->id }}"><i class="fa {{ $button[2] }}"></i> {{ $button[0] }}</button>
        @endif
    @endforeach
@endif
