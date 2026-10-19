@php
    $fullyAssigned = $assignedCount >= $visit->workers_count;
    $remaining = max(0, $order->total_amount - $order->paid_amount);
    $paidMethods = implode('، ', array_map(fn ($m) => __('hourly.'.$m), $allocations[$visit->id]['methods'] ?? []));
    $free=$visit->is_free || (float)$visit->visit_price===0.0;
    $visitPaid=$allocations[$visit->id]['paid']??0;
    $visitPaymentStatus=$visitPaid>=(float)$visit->visit_price?'paid':($visitPaid>0?'partial':'unpaid');
@endphp
<article class="vl-card" data-visit-card="{{ $visit->id }}" style="--row-tone: {{ $toneHex }}">
    <header class="vl-card-head">
        <div class="vl-card-title">
            <input id="select-visit-{{ $visit->id }}" type="checkbox" data-select-visit data-started="{{ $visit->has_started_execution ? '1' : '0' }}" value="{{ $visit->id }}" aria-label="تحديد الزيارة {{ $visit->id }}">
            <a class="vl-card-id" href="{{ route('admin.hourly-visits.show', $visit) }}">زيارة {{ $visit->visit_number }}</a><small class="vl-muted">مرجع #{{ $visit->id }}</small>
            <span class="vl-card-service"><i class="fa fa-broom"></i> {{ $order->serviceCategory?->name ?? 'خدمة ساعات' }}</span>
        </div>
        <div class="vl-card-badges">
            @if($canEdit && $visit->is_missed)
                @if(!$visit->has_started_execution)<button type="button" class="vl-act" data-single="reschedule" data-id="{{ $visit->id }}">إعادة جدولة الزيارة الفائتة</button>@endif
                @if(!$visit->has_started_execution)<button type="button" class="vl-act" data-single="cancel_visit" data-id="{{ $visit->id }}">إلغاء الزيارة</button>@endif
            @endif
            @if($canEdit && in_array($visit->status, \App\Services\Hourly\ShelterPresence::OUT_STATUSES, true))
                <button type="button" class="vl-act" data-single="confirm_return" data-id="{{ $visit->id }}">تأكيد العودة للإيواء يدويًا</button>
            @endif
            @include('admin.hourly_visits._delay')
            <span class="vl-badge {{ $fullyAssigned ? 'vl-b-green' : ($assignedCount ? 'vl-b-amber' : 'vl-b-red') }}"><i class="fa fa-user-check"></i> {{ $fullyAssigned ? 'تعيين مكتمل' : ($assignedCount ? 'تعيين جزئي' : 'بدون عاملات') }}</span>
            <span class="vl-badge {{ $free || $visitPaymentStatus === 'paid' ? 'vl-b-green' : ($visitPaymentStatus === 'partial' ? 'vl-b-amber' : 'vl-b-red') }}"><i class="fa fa-wallet"></i> {{ $free?'مجانية':__('hourly.'.$visitPaymentStatus) }}</span>
            <span class="vl-badge {{ $toneClass }}"><i class="fa fa-circle"></i> {{ \App\Services\Hourly\VisitSearch::STATUSES[$visit->display_status] ?? $visit->status }}</span>
        </div>
    </header>

    <div class="vl-grid">
        {{-- الصف الأول: الطلب والعميل والعنوان والموعد --}}
        <section class="vl-cell" data-column="info">
            <h6><i class="fa fa-hashtag"></i> معلومات الزيارة</h6>
            <a class="vl-ellipsis" dir="ltr" href="{{ route('admin.hourly-orders.show', $order) }}" title="{{ $order->order_number }}">{{ $order->order_number }}</a>
            <div class="vl-kv"><span>رقم الزيارة</span><b>{{ $visit->visit_number }}</b></div>
            <div class="vl-kv"><span>العاملات المطلوبة</span><b>{{ $visit->workers_count }}</b></div>
        </section>
        <section class="vl-cell" data-column="customer">
            <h6><i class="fa fa-user"></i> العميل</h6>
            <a class="vl-strong" href="{{ route('clients.show', $order->user_id) }}">{{ $order->user?->name }}</a>
            <a class="vl-phone" dir="ltr" href="tel:{{ $order->user?->phone }}"><i class="fa fa-phone"></i> {{ $order->user?->phone }}</a>
            <div class="vl-kv"><span>الرصيد</span><b class="vl-num">{{ number_format($order->user?->hourly_wallet_balance ?? 0, 2) }} ر.س</b></div>
        </section>
        <section class="vl-cell" data-column="address">
            <h6><i class="fa fa-location-dot"></i> العنوان</h6>
            <p class="vl-address">{{ $visit->address_text }}</p>
            <div class="vl-acts">
                <a class="vl-act" href="{{ $visit->map_url }}" target="_blank" rel="noopener"><i class="fa fa-map-location-dot"></i> الخريطة</a>
                <a class="vl-act" dir="ltr" href="tel:{{ $visit->contact_phone }}"><i class="fa fa-phone"></i> {{ $visit->contact_phone }}</a>
            </div>
        </section>
        <section class="vl-cell" data-column="date">
            <h6><i class="fa fa-calendar"></i> موعد الزيارة</h6>
            @if($visit->scheduled_start_at)
                <div class="vl-when"><b>{{ $visit->scheduled_start_at->translatedFormat('l') }}</b> <span class="vl-num" dir="ltr">{{ $visit->scheduled_start_at->format('Y-m-d') }}</span></div>
                <div class="vl-time vl-num" dir="ltr">{{ $visit->scheduled_start_at->format('H:i') }} – {{ $visit->scheduled_end_at?->format('H:i') ?? '—' }}</div>
            @else
                <div class="vl-warn">لم يحدد الموعد</div>
            @endif
            @if($canEdit)
                <div class="vl-acts">
                    @if(!$visit->has_started_execution)<button type="button" class="vl-act" data-single="reschedule" data-id="{{ $visit->id }}"><i class="fa fa-calendar-days"></i> التاريخ</button>@endif
                    @if(!$visit->has_started_execution)<button type="button" class="vl-act" data-single="start_time" data-id="{{ $visit->id }}"><i class="fa fa-clock"></i> الوقت</button>@endif
                    <button type="button" class="vl-act" data-single="note" data-id="{{ $visit->id }}"><i class="fa fa-note-sticky"></i> مذكرة</button>
                </div>
            @endif
        </section>

        {{-- الصف الثاني: المدة والسعر والحالة والسداد --}}
        <section class="vl-cell" data-column="duration">
            <h6><i class="fa fa-hourglass-half"></i> المدة</h6>
            <div class="vl-big vl-num">{{ rtrim(rtrim(number_format((float) $visit->planned_hours, 2), '0'), '.') }} <small>ساعات</small></div>
            @if($canPrice && $canEdit)<div class="vl-acts">@if(!$visit->has_started_execution)<button type="button" class="vl-act" data-single="duration" data-id="{{ $visit->id }}"><i class="fa fa-pen"></i> تغيير المدة</button>@endif</div>@endif
        </section>
        <section class="vl-cell" data-column="price">
            <h6><i class="fa fa-tag"></i> السعر شامل الضريبة</h6>
            <div class="vl-big vl-num">{{ number_format($visit->visit_price, 2) }} <small>ر.س</small></div>
            @if($free)<span class="vl-badge vl-b-green">زيارة مجانية</span>@endif
            <div class="vl-kv"><span>مدفوع</span><b class="vl-num">{{ number_format($allocations[$visit->id]['paid'] ?? 0, 2) }} ر.س</b></div>
            <small class="vl-muted">{{ $paidMethods ?: 'لا توجد دفعات موزعة' }}</small>
            @if($canPrice && $canEdit)<div class="vl-acts"><button type="button" class="vl-act is-warn" data-single="price" data-id="{{ $visit->id }}"><i class="fa fa-pen"></i> تغيير السعر</button></div>@endif
        </section>
        <section class="vl-cell" data-column="status">
            <h6><i class="fa fa-signal"></i> حالة العملية</h6>
            <span class="vl-badge {{ $toneClass }}">{{ \App\Services\Hourly\VisitSearch::STATUSES[$visit->display_status] ?? $visit->status }}</span>
            <div class="vl-progress" aria-label="العاملات المعيّنات {{ $assignedCount }} من {{ $visit->workers_count }}">
                <span style="width: {{ $visit->workers_count ? min(100, round($assignedCount / $visit->workers_count * 100)) : 100 }}%"></span>
            </div>
            <small class="vl-muted vl-num">{{ $assignedCount }} / {{ $visit->workers_count }} عاملات معيّنات</small>
        </section>
        <section class="vl-cell" data-column="payment">
            <h6><i class="fa fa-wallet"></i> سداد الطلب</h6>
            <div class="vl-kv"><span>حالة الطلب</span><b>{{ __('hourly.'.$order->payment_status) }}</b></div>
            <div class="vl-kv"><span>المتبقي</span><b class="vl-num {{ $remaining > 0 ? 'vl-due' : '' }}">{{ number_format($remaining, 2) }} ر.س</b></div>
            <div class="vl-kv"><span>طريقة الدفع</span><b>{{ __('hourly.'.$order->payment_method) }}</b></div>
        </section>

        {{-- الصف الثالث: الفريق --}}
        <section class="vl-cell vl-cell-wide" data-column="workers">
            <h6><i class="fa fa-user-nurse"></i> العاملات</h6>
            <div class="vl-people">
                @forelse($visit->workers as $worker)
                    <span class="vl-person">
                        @if($canEdit)<button type="button" data-single="assign_workers" data-replace-worker="{{ $worker->id }}" data-id="{{ $visit->id }}" title="استبدال {{ $worker->name }}">{{ $worker->name }}</button>@else{{ $worker->name }}@endif
                        <a href="{{ route('admin.hourly-maids.show', $worker) }}" title="ملف العاملة" aria-label="ملف {{ $worker->name }}"><i class="fa fa-id-card"></i></a>
                    </span>
                @empty
                    <span class="vl-warn"><i class="fa fa-triangle-exclamation"></i> غير مخصص</span>
                @endforelse
            </div>
            @if($canEdit)
                <div class="vl-acts">
                    <button type="button" class="vl-act is-primary" data-single="assign_workers" data-id="{{ $visit->id }}"><i class="fa fa-user-plus"></i> تعيين عاملة</button>
                    <button type="button" class="vl-act" data-single="assign_all" data-id="{{ $visit->id }}"><i class="fa fa-wand-magic-sparkles"></i> تعيين تلقائي</button>
                    @if($assignedCount)<button type="button" class="vl-act is-danger" data-single="unassign_workers" data-id="{{ $visit->id }}"><i class="fa fa-user-minus"></i> إلغاء التعيين</button>@endif
                </div>
            @endif
        </section>
        <section class="vl-cell" data-column="leader">
            <h6><i class="fa fa-user-tie"></i> قائد الفريق</h6>
            @if($visit->teamLeader)<b>{{ $visit->teamLeader->name }}</b>@else<span class="vl-muted">غير مخصص</span>@endif
            @if($canEdit)<div class="vl-acts"><button type="button" class="vl-act" data-single="team_leader" data-id="{{ $visit->id }}"><i class="fa fa-user-tie"></i> {{ $visit->teamLeader ? 'تغيير القائد' : 'تعيين قائد' }}</button></div>@endif
        </section>
        <section class="vl-cell" data-column="drivers">
            <h6><i class="fa fa-van-shuttle"></i> السائقون</h6>
            @foreach(['delivery' => ['التوصيل', 'fa-arrow-right-to-bracket'], 'pickup' => ['الاستلام', 'fa-arrow-right-from-bracket']] as $role => [$label, $icon])
                @php
                    $driver = $role === 'delivery' ? $visit->deliveryDriver : $visit->pickupDriver;
                    $time = $visit->{$role.'_at'} ? \Carbon\Carbon::parse($visit->{$role.'_at'})->format('H:i') : ($role === 'delivery' ? $visit->scheduled_start_at?->format('H:i') : $visit->scheduled_end_at?->format('H:i'));
                @endphp
                <div class="vl-driver">
                    <span class="vl-driver-role"><i class="fa {{ $icon }}"></i> {{ $label }} <small class="vl-num" dir="ltr">{{ $time }}</small></span>
                    <span class="vl-driver-name">
                        @if($canEdit)<button type="button" class="{{ $driver ? '' : 'is-empty' }}" data-single="assign_driver" data-id="{{ $visit->id }}" data-role="{{ $role }}" data-time="{{ $time }}" data-driver="{{ $driver?->id }}">{{ $driver?->name ?? 'تعيين سائق' }}</button>@else{{ $driver?->name ?? 'غير مخصص' }}@endif
                        @if($driver)
                            <a href="{{ route('admin.hourly-drivers.edit', $driver) }}" title="ملف السائق" aria-label="ملف السائق"><i class="fa fa-id-card"></i></a>
                            @if($canEdit)<button type="button" class="vl-remove" title="إزالة سائق {{ $label }}" aria-label="إزالة سائق {{ $label }}" data-single="unassign_driver" data-id="{{ $visit->id }}" data-role="{{ $role }}"><i class="fa fa-xmark"></i></button>@endif
                        @endif
                    </span>
                </div>
            @endforeach
        </section>
    </div>

    <footer class="vl-card-foot">
        <div class="vl-foot-main">
            @if($canEdit && !in_array($visit->status, \App\Services\Hourly\VisitSearch::CLOSED))
                <button type="button" class="vl-btn is-success" data-single="complete" data-id="{{ $visit->id }}"><i class="fa fa-circle-check"></i> إكمال</button>
                @if(!$visit->has_started_execution)<button type="button" class="vl-btn is-danger" data-single="cancel_visit" data-id="{{ $visit->id }}"><i class="fa fa-ban"></i> إلغاء الزيارة</button>@endif
            @endif
            <div class="dropdown">
                <button type="button" class="vl-btn is-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-money-bill-wave"></i> الدفع</button>
                <ul class="dropdown-menu">
                    @if($canCollect)<li><button type="button" class="dropdown-item" data-single="collect" data-id="{{ $visit->id }}"><i class="fa fa-plus"></i> إضافة دفعة</button></li>@endif
                    <li><button type="button" class="dropdown-item" data-payments-url="{{ route('admin.hourly-visits.payments', $visit) }}"><i class="fa fa-clock-rotate-left"></i> سجل الدفعات</button></li>
                </ul>
            </div>
        </div>
        <div class="vl-foot-links">
            <a href="{{ route('admin.hourly-visits.invoice', $visit) }}" target="_blank" rel="noopener"><i class="fa fa-file-invoice"></i> فاتورة الزيارة</a>
            <a href="{{ route('admin.hourly-orders.invoice', $order) }}" target="_blank" rel="noopener"><i class="fa fa-file-invoice-dollar"></i> فاتورة الطلب</a>
        </div>
    </footer>
</article>
