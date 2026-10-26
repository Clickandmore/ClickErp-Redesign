@php
    /* بطاقة زيارة واحدة في صفحة إدارة الطلب: ترويسة للموعد والحالة، تنبيهات ما يحتاج إجراء، ثم الطاقم والنقل والدفع. */
    [$visitClass, $visitIcon] = $statusTone($visit->display_status);
    /* $finance و$free و$emptySlots و$alerts و$buckets تأتي من $visitState في order-manage. */
    $assigned = $visit->workers;
    $editable = AdminGuardHasPerm('hourly_orders_edit') && in_array($order->status, ['active', 'pending_confirmation']) && !in_array($visit->status, $closedStatuses);
    $payStatus = $free ? 'free' : ($finance['payment_status'] ?? 'unpaid');
    $charge = (float) ($finance['charge'] ?? $visit->visit_price);
    $paid = (float) ($finance['paid'] ?? 0);
    $paidShare = $charge > 0 ? min(100, round($paid / $charge * 100)) : 100;
    $visitNotes = $notes->where('visit_id', $visit->id);
@endphp
<article class="om-visit {{ str_replace('om-b-', 'om-tl-', $visitClass) }}" data-visit-card data-buckets="{{ implode(' ', $buckets) }}" id="visit-{{ $visit->id }}">
    <header class="om-v-head">
        @if($editable)
            <input class="form-check-input om-v-check" type="checkbox" data-visit-select data-started="{{ $visit->has_started_execution ? '1' : '0' }}" value="{{ $visit->id }}" data-number="{{ $visit->visit_number }}" data-start="{{ $visit->scheduled_start_at?->format('Y-m-d H:i') }}" data-end="{{ $visit->scheduled_end_at?->format('Y-m-d H:i') }}" data-delivery="{{ $visit->delivery_at }}" data-pickup="{{ $visit->pickup_at }}" aria-label="تحديد الزيارة {{ $visit->visit_number }} للإجراءات الجماعية" title="تحديد للإجراءات الجماعية">
        @endif
        <div class="om-v-no"><span>زيارة</span><b class="om-num">{{ $visit->visit_number }}</b><small class="om-ltr om-num">#{{ $visit->id }}</small></div>
        <div class="om-v-when">
            @if($visit->scheduled_start_at)
                <b>{{ $visit->scheduled_start_at->translatedFormat('l') }} <span class="om-ltr om-num">{{ $visit->scheduled_start_at->format('d/m/Y') }}</span>
                    @if($editable && !$visit->has_started_execution && $visit->scheduled_start_at->isFuture())
                        <button type="button" class="om-link-edit" data-order-action="start_time" data-visit="{{ $visit->id }}" title="تعديل الموعد" aria-label="تعديل موعد الزيارة {{ $visit->visit_number }}"><i class="fa fa-pen-to-square"></i></button>
                    @endif
                </b>
                <span><i class="fa fa-clock"></i> <span class="om-ltr om-num">{{ $visit->scheduled_start_at->format('H:i') }} – {{ $visit->scheduled_end_at?->format('H:i') }}</span> · {{ $hours($visit->planned_hours) }} ساعات</span>
            @else
                <b>لم يُحدَّد الموعد</b><span>{{ $hours($visit->planned_hours) }} ساعات</span>@if($editable && $visit->status==='unscheduled')<button type="button" class="om-schedule-btn" onclick="window.openHourlySchedule({{ $visit->id }})" title="{{ __('visit_schedule.title') }}" aria-label="{{ __('visit_schedule.title') }}"><i class="fa fa-calendar-plus"></i> {{ __('visit_schedule.title') }}</button>@endif
            @endif
        </div>
        <div class="om-v-tags">
            <span class="om-badge {{ $visitClass }}"><i class="fa {{ $visitIcon }}"></i> {{ __('hourly.'.$visit->display_status) }}</span>
            @if($free)
                <span class="om-badge om-b-green"><i class="fa fa-gift"></i> مجانية</span>
            @else
                @php [$payClass] = $statusTone($payStatus); @endphp
                <span class="om-badge {{ $payClass }}"><i class="fa fa-receipt"></i> {{ __('hourly.'.$payStatus) }}</span>
            @endif
        </div>
        <div class="om-v-price"><b class="om-num">{{ number_format((float) $visit->visit_price, 2) }}</b><small>{{ __('hourly.sar') }}</small></div>
    </header>

    @if(!empty(($order->booking_metadata['missed_visit_requests'] ?? [])[$visit->id]))
        <div class="px-3 pt-2">@include('admin.hourly._recovery-requests', ['recoveryOrders' => collect([$order]), 'recoveryVisitId' => $visit->id])</div>
    @endif
    @if(\App\Services\Hourly\VisitDelay::state($visit))
        <div class="px-3 py-2">@include('admin.hourly_visits._delay', ['visit' => $visit])</div>
    @endif
    @if($alerts)
        <div class="om-v-alerts">
            @foreach($alerts as [$class, $icon, $text])<span class="om-badge {{ $class }}"><i class="fa {{ $icon }}"></i> {{ $text }}</span>@endforeach
        </div>
    @endif

    <div class="om-v-body">
        <section>
            <h6><i class="fa fa-user-group"></i> الطاقم <small class="om-num">{{ $assigned->count() }}/{{ $visit->workers_count }}</small></h6>
            <ul class="om-v-list">
                @foreach($assigned as $worker)
                    <li>
                        <span class="om-v-avatar">{{ mb_substr($worker->name, 0, 1) }}</span>
                        <span class="om-v-name">{{ $worker->name }} <small class="om-ltr om-num">#{{ $worker->id }}</small></span>
                        @if($editable)
                            <button type="button" class="om-v-icon is-danger" data-order-action="unassign_worker" data-worker="{{ $worker->id }}" data-visit="{{ $visit->id }}" title="إلغاء تعيين {{ $worker->name }}" aria-label="إلغاء تعيين {{ $worker->name }}"><i class="fa fa-user-xmark"></i></button>
                        @endif
                    </li>
                @endforeach
                @for($slot = 1; $slot <= $emptySlots; $slot++)
                    <li class="is-empty">
                        <span class="om-v-avatar"><i class="fa fa-user-plus"></i></span>
                        @if($editable)
                            <button type="button" class="om-v-slot" data-order-action="add_worker" data-visit="{{ $visit->id }}">مقعد شاغر — اختر عاملة</button>
                            @if(AdminGuardHasPerm('hourly_orders_price_override'))
                                <button type="button" class="om-v-icon is-danger" data-order-action="remove_worker" data-visit="{{ $visit->id }}" title="إزالة المقعد وتقليل العدد" aria-label="إزالة المقعد وتقليل العدد"><i class="fa fa-user-minus"></i></button>
                            @endif
                        @else
                            <span class="om-v-name text-muted">مقعد بدون عاملة</span>
                        @endif
                    </li>
                @endfor
                @if($assigned->isEmpty() && !$emptySlots)<li class="is-empty"><span class="om-v-name text-muted">لا توجد مقاعد على هذه الزيارة</span></li>@endif
            </ul>
            <div class="om-v-row"><span><i class="fa fa-user-tie"></i> قائد الفريق</span><b class="{{ $visit->teamLeader ? '' : 'is-missing' }}">{{ $visit->teamLeader?->name ?: 'غير مخصص' }}</b></div>
        </section>

        <section>
            <h6><i class="fa fa-van-shuttle"></i> النقل</h6>
            <div class="om-v-row"><span><i class="fa fa-arrow-right-to-bracket"></i> توصيل</span><b class="{{ $visit->deliveryDriver ? '' : 'is-missing' }}">{{ $visit->deliveryDriver?->name ?: 'بدون سائق' }}</b><em class="om-ltr om-num">{{ $visit->delivery_at?->format('H:i') ?: $visit->scheduled_start_at?->format('H:i') }}</em></div>
            <div class="om-v-row"><span><i class="fa fa-arrow-right-from-bracket"></i> استلام</span><b class="{{ $visit->pickupDriver ? '' : 'is-missing' }}">{{ $visit->pickupDriver?->name ?: 'بدون سائق' }}</b><em class="om-ltr om-num">{{ $visit->pickup_at?->format('H:i') ?: $visit->scheduled_end_at?->format('H:i') }}</em></div>
            @include('admin.hourly._driver-suggestion')
        </section>

        <section class="om-v-pay">
            <h6><i class="fa fa-wallet"></i> الدفع</h6>
            @if($free)
                <p class="text-muted mb-0">زيارة مجانية لا يُحصَّل عنها مبلغ.</p>
            @else
                <div class="om-v-row"><span>قيمة الزيارة</span><b class="om-num">{{ $money($charge) }}</b></div>
                <div class="om-v-row"><span>المدفوع</span><b class="om-num">{{ $money($paid) }}</b></div>
                <div class="om-v-row"><span>المتبقي</span><b class="om-num {{ ($finance['remaining'] ?? 0) > 0 ? 'is-missing' : '' }}">{{ $money($finance['remaining'] ?? max(0, $charge - $paid)) }}</b></div>
                <div class="om-v-bar" role="progressbar" aria-valuenow="{{ $paidShare }}" aria-valuemin="0" aria-valuemax="100" aria-label="نسبة السداد"><span style="width: {{ $paidShare }}%"></span></div>
                @if(($finance['policy'] ?? null) === 'per_visit' && !($finance['is_due'] ?? false) && ($finance['remaining'] ?? 0) > 0)<small class="text-muted">لم يحِن موعد تحصيلها بعد.</small>@endif
            @endif
            @include('admin.hourly._cash-collector', ['hideFinancial' => true])
        </section>
    </div>

    @if($visitNotes->isNotEmpty())
        <div class="om-v-notes">
            @foreach($visitNotes as $note)
                <p><i class="fa fa-note-sticky"></i> {{ $note['text'] }} <small>— {{ $note['author'] }}</small></p>
            @endforeach
        </div>
    @endif

    @if($editable || AdminGuardHasPerm('hourly_orders_create'))
        <footer class="om-v-actions">
            <div>
                @include('admin.hourly._visit-actions', ['group' => 'staff'])
                @include('admin.hourly._visit-actions', ['group' => 'money'])
                @include('admin.hourly._note-form', ['visitId' => $visit->id])
            </div>
            <div>@include('admin.hourly._visit-actions', ['group' => 'row'])</div>
        </footer>
    @endif
</article>
