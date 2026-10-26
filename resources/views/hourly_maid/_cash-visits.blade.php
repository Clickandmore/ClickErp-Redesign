{{-- زيارات اليوم المحدد للعاملة: الوصول، التحصيل النقدي، والانتهاء (مع تنبيه الإنهاء المبكر). يتوقع $tasks و $selectedDate من الصفحة. --}}
@php
    // تُعرض أيضًا منفردة (الاختبارات)، فتحسب بياناتها إن لم تمررها الصفحة.
    $selectedDate ??= \Carbon\Carbon::parse($cashDate ?? now()->toDateString());
    $tasks ??= collect(\Illuminate\Support\Facades\Schema::hasTable('hourly_visit_workers') ? app(\App\Services\Hourly\WorkerAppService::class)->today(\App\Models\ingaz\Maid::findOrFail($maid->id), $selectedDate->toDateString()) : []);
    $dayCounts ??= collect();
    $workerStatus = [
        'assigned' => ['مجدولة', 'is-planned'], 'en_route' => ['في الطريق إليك', 'is-route'], 'delivered' => ['الخدمة جارية', 'is-live'],
        'finished' => ['انتهت · بانتظار العودة', 'is-done'], 'picked_up' => ['في طريق العودة', 'is-done'], 'returned' => ['مكتملة', 'is-done'],
        'completed' => ['مكتملة', 'is-done'], 'cancelled' => ['ملغاة', 'is-off'], 'no_show' => ['لم تُنفذ', 'is-off'],
    ];
    $paymentLabels = ['paid' => 'مسددة', 'partial' => 'مسددة جزئيًا', 'unpaid' => 'غير مسددة'];
    $hoursLabel = fn ($h) => rtrim(rtrim(number_format((float) $h, 2), '0'), '.');
@endphp
<section class="dp-panel wp-visits" aria-labelledby="wp-visits-title">
    <div class="dp-panel-head">
        <div class="dp-title"><span class="dp-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11 12 4l9 7M5 10v10h14V10M10 20v-6h4v6"/></svg></span>
            <div><span class="wp-kicker">الزيارات والتحصيل النقدي</span><h2 id="wp-visits-title">زيارات {{ $selectedDate->translatedFormat('l، j F') }}</h2></div></div>
        <span class="dp-pill">{{ $tasks->count() }} زيارة</span>
    </div>

    {{-- شريط الأيام: أسبوع حول اليوم المحدد مع عدد الزيارات --}}
    <nav class="dp-days" aria-label="التنقل بين الأيام">
        @for($offset = -3; $offset <= 3; $offset++)
            @php $d = $selectedDate->copy()->addDays($offset); $key = $d->toDateString(); @endphp
            <a class="{{ $offset === 0 ? 'is-selected' : '' }} {{ $d->isToday() ? 'is-today' : '' }}" href="{{ route('hourly-maid.home', ['date' => $key]) }}" @if($offset === 0) aria-current="date" @endif>
                <small>{{ $d->translatedFormat('D') }}</small><b>{{ $d->day }}</b>
                @if($dayCounts[$key] ?? 0)<i>{{ $dayCounts[$key] }}</i>@endif
            </a>
        @endfor
        <form class="dp-date-pick" method="GET" action="{{ route('hourly-maid.home') }}">
            <input type="date" name="date" value="{{ $selectedDate->toDateString() }}" aria-label="اختيار التاريخ" onchange="this.form.submit()">
            <button class="dp-sr">عرض</button>
        </form>
    </nav>

    @if($tasks->isEmpty())
        <div class="dp-empty">
            <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg></span>
            <b>لا توجد زيارات في هذا اليوم.</b>
            <small>اختاري يومًا آخر من الشريط، أو تصفحي الزيارات المتاحة للحجز.</small>
        </div>
    @else
    <ol class="dp-timeline">
    @foreach($tasks as $task)
        @php
            [$statusLabel, $statusClass] = $workerStatus[$task['status']] ?? [$task['status'], 'is-planned'];
            $done = in_array($statusClass, ['is-done', 'is-off'], true);
            $active = $task['can_arrive'] || $task['can_finish'] || ($task['can_return'] ?? false) || ($task['cash']['can_collect'] ?? false);
            $financial = $task['financial'] ?? null;
            $plannedEnd = !empty($task['planned_end_at']) ? \Carbon\Carbon::parse($task['planned_end_at']) : null;
            $early = $plannedEnd && $plannedEnd->isFuture();
            // تقدّم وقت الخدمة من الوصول حتى النهاية المحجوزة.
            $progress = null;
            if ($plannedEnd && $task['planned_hours'] > 0) {
                $total = $task['planned_hours'] * 3600;
                $elapsed = $total - max(0, now()->diffInSeconds($plannedEnd, false));
                $progress = (int) max(0, min(100, round($elapsed / $total * 100)));
            }
            $mapUrl = ($task['latitude'] && $task['longitude']) ? 'https://www.google.com/maps?q='.$task['latitude'].','.$task['longitude'] : null;
        @endphp
        <li class="dp-task wp-task {{ $statusClass }} {{ $done ? 'is-done' : '' }} {{ $active ? 'is-active' : '' }}" id="visit-{{ $task['id'] }}">
            <div class="dp-when">
                <b dir="ltr">{{ $task['start_time'] }}</b>
                <span class="dp-role">{{ $hoursLabel($task['planned_hours']) }} س</span>
                @if($task['end_time'])<small dir="ltr">حتى {{ $task['end_time'] }}</small>@endif
            </div>
            <article class="dp-card">
                <header class="dp-card-head">
                    <div>
                        <h3>زيارة {{ $task['visit_number'] }} · {{ $task['client']['name'] }}</h3>
                        <span class="dp-ref"><span dir="ltr">{{ $task['start_time'] }} — {{ $task['end_time'] ?: '—' }}</span>@if($task['extra_hours'] > 0) · تشمل {{ $hoursLabel($task['extra_hours']) }} س إضافية @endif</span>
                    </div>
                    <span class="dp-status wp-status {{ $statusClass }}">{{ $statusLabel }}</span>
                </header>

                @if($task['incident_status'])<p class="dp-alert is-soft" role="alert">لهذه الزيارة بلاغ تتابعه الإدارة؛ لا تبدئي التنفيذ قبل تأكيدها.</p>@endif

                @if($progress !== null)
                    <div class="wp-progress {{ $early ? '' : 'is-complete' }}">
                        <div class="wp-progress-head"><span>{{ $early ? 'وقت الخدمة جارٍ' : 'اكتمل وقت الخدمة المحجوز' }}</span><b dir="ltr">{{ $plannedEnd->format('H:i') }}</b></div>
                        <div class="wp-bar" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"><span style="width: {{ $progress }}%"></span></div>
                        <small>{{ $early ? 'ينتهي الوقت المحجوز '.$plannedEnd->format('H:i').' · متبقٍ '.now()->diffForHumans($plannedEnd, \Carbon\CarbonInterface::DIFF_ABSOLUTE) : 'يمكنك تسجيل الانتهاء الآن.' }}</small>
                    </div>
                @endif

                <dl class="dp-info">
                    <div class="is-wide"><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg> العنوان</dt><dd>{{ $task['address'] ?: '—' }}</dd></div>
                    <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17h2l2-5h10l2 5h2M7 17h10M8 12l1.5-5h5L16 12"/></svg> التوصيل</dt><dd>{{ $task['needs_transport'] ? ($task['driver']['name'] ?? 'لم يُحدد السائق بعد') : 'تصلين بنفسك' }}</dd></div>
                    @if($financial)
                        <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18v10H3zM12 9.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5"/></svg> الدفع</dt>
                            <dd><span class="wp-pay is-{{ $financial['payment_status'] ?? 'unpaid' }}">{{ $paymentLabels[$financial['payment_status'] ?? 'unpaid'] }}</span>@if(!empty($financial['is_overdue'])) <span class="wp-pay is-late">متأخرة</span>@endif
</dd></div>
                    @endif
                    @if(!empty($task['cash']['collector']['label']))
                        <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11a4 4 0 1 0-8 0M4 20a8 8 0 0 1 16 0"/></svg> مسؤول التحصيل</dt><dd>{{ $task['cash']['collector']['label'] }}</dd></div>
                    @endif
                </dl>

                @if($mapUrl)
                    <div class="dp-quick"><a class="dp-btn is-ghost" href="{{ $mapUrl }}" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2Zm0 0v14m6-12v14"/></svg> فتح الموقع</a></div>
                @endif

                @if(!empty($task['arrival_blocked_reason']))
                    <p class="dp-muted" role="status" data-arrival-blocked>لا يمكن تسجيل الوصول: {{ $task['arrival_blocked_reason'] }}</p>
                @endif
                @if($task['can_arrive'])
                    <form class="dp-action" method="POST" action="{{ route('hourly-maid.visits.action', $task['id']) }}">@csrf
                        <input type="hidden" name="action" value="arrived">
                        @if(!empty($task['requires_delivery_code']))
                            <label class="dp-field"><span>رمز التسليم من العميل</span><input type="text" name="delivery_code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required placeholder="اطلبي الرمز من العميل" dir="ltr"></label>
                        @endif
                        <button class="dp-btn is-primary"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg> وصلت</button>
                    </form>
                @endif

                @if($task['cash']['can_collect'] ?? false)
                    <form class="dp-action is-cash" method="POST" action="{{ route('hourly-maid.cash.collect', $task['id']) }}">@csrf
                        <input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
                        <label class="dp-field"><span>النقد المستلم من العميل (ر.س) — المطلوب {{ number_format($task['cash']['cash_due'], 2) }}</span><input type="number" name="amount" min="0.01" max="{{ $task['cash']['cash_due'] }}" step="0.01" inputmode="decimal" required></label>
                        <button class="dp-btn is-cash"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18v10H3zM12 9.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5"/></svg> تأكيد استلام النقد</button>
                    </form>
                @endif

                @include('hourly_maid._service-time',['task'=>$task])
                @if($task['can_finish'])
                    <form class="dp-action {{ $early ? 'is-early' : '' }}" method="POST" action="{{ route('hourly-maid.visits.action', $task['id']) }}">@csrf
                        <input type="hidden" name="action" value="finish">
                        @if($early)
                            {{-- قبل اكتمال وقت الخدمة: الإنهاء مسموح لكنه يُسجَّل ويُسأل العميل عنه، فيُطلب سببه. --}}
                            <p class="wp-early-note" role="note"><b>لم يكتمل وقت الخدمة بعد</b> (حتى <span dir="ltr">{{ $plannedEnd->format('H:i') }}</span>). الإنهاء الآن يُسجَّل إنهاءً مبكرًا ويُسأل العميل عنه.</p>
                            <label class="dp-field"><span>سبب الإنهاء المبكر</span><select name="early_reason" required><option value="">اختاري السبب</option>@foreach($task['early_finish_reasons'] as $reasonKey => $reasonLabel)<option value="{{ $reasonKey }}">{{ $reasonLabel }}</option>@endforeach</select></label>
                            <label class="dp-field"><span>ملاحظة (اختيارية)</span><input name="note" maxlength="500"></label>
                        @endif
                        <button class="dp-btn is-primary {{ $early ? 'is-warn' : 'is-finish' }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg> انتهيت من الخدمة</button>
                    </form>
                @endif

                @if($task['can_return'] ?? false)
                    <form class="dp-action" method="POST" action="{{ route('hourly-maid.visits.action', $task['id']) }}" onsubmit="return confirm('هل وصلتِ فعليًا إلى الإيواء وتريدين تأكيد العودة؟')">@csrf
                        <input type="hidden" name="action" value="return">
                        <button class="dp-btn is-primary"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.5 12 4l9 6.5V20h-6v-6H9v6H3z"/><path d="m8 11 3 3 5-5"/></svg> وصلت إلى الإيواء — تأكيد العودة</button>
                        <small class="dp-muted">اضغطي بعد الوصول الفعلي؛ سيُسجّل وقت العودة ويُحدّث حالة الإيواء.</small>
                    </form>
                @endif
                @if(!$active)
                    <p class="dp-muted">{{ $done ? 'تم تنفيذ هذه الزيارة.' : ($task['needs_transport'] && in_array($task['status'], ['assigned', 'en_route'], true) ? 'يسجّل السائق وصولك عند التوصيل.' : 'لا يوجد إجراء مطلوب الآن.') }}</p>
                @endif
            </article>
        </li>
    @endforeach
    </ol>
    @endif
</section>
