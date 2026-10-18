@extends('hourly_driver.layout')
@section('title', 'مهامي ومشاويري')
@section('portal', true)
@push('styles')
<link rel="stylesheet" href="{{ asset('css/hourly-driver-dashboard.css') }}?v={{ filemtime(public_path('css/hourly-driver-dashboard.css')) }}">
@endpush
@section('content')
@php
    $statusLabels = __('hourly_routing.visit_statuses');
    $firstWeekday = $month->copy()->startOfMonth()->dayOfWeek;
    $daysInMonth = $month->daysInMonth;
    $previousMonth = $month->copy()->subMonth();
    $nextMonth = $month->copy()->addMonth();
    $cashReady = \App\Services\Hourly\CashCollectionService::ready();
    $closed = ['cancelled', 'cancelled_by_client', 'expired', 'no_show'];
    $deliveredStatuses = ['delivered', 'in_progress', 'worker_finished', 'picked_up', 'completed'];

    // كل دور (توصيل أو استلام) مهمة مستقلة ببطاقتها، بنفس وحدة العدّ في التقويم والعدّاد.
    $tasks = $visits->flatMap(fn($visit) => collect([
        (int)$visit->delivery_driver_id === (int)$driver->id ? ["visit" => $visit, "role" => "delivery", "at" => $visit->delivery_at ?: $visit->scheduled_start_at] : null,
        (int)$visit->pickup_driver_id === (int)$driver->id ? ["visit" => $visit, "role" => "pickup", "at" => $visit->pickup_at ?: $visit->scheduled_end_at] : null,
    ])->filter())->sortBy(fn($task) => $task["at"]?->timestamp)->values();

    // الإجراء المتاح لكل مهمة وتنبيهاتها: نفس القواعد السابقة، تُحسب مرة واحدة للعرض والملخص.
    $tasks = $tasks->map(function ($task) {
        $visit = $task['visit']; $isDelivery = $task['role'] === 'delivery'; $isPickup = !$isDelivery;
        $action = null; $actionLabel = null;
        $blocked = $isDelivery && $visit->status === 'scheduled' ? \App\Services\Hourly\DeliveryStartWindow::blockedReason($visit) : null;
        if ($isDelivery && $visit->status === 'scheduled' && !$blocked) { $action = 'start_delivery'; $actionLabel = 'بدء مشوار التوصيل'; }
        elseif ($isDelivery && $visit->status === 'en_route') { $action = 'confirm_delivery'; $actionLabel = 'تأكيد التوصيل'; }
        elseif ($isPickup && $visit->status === 'worker_finished') { $action = 'confirm_pickup'; $actionLabel = 'تأكيد استلام العاملات'; }
        elseif ($isPickup && $visit->status === 'picked_up') { $action = 'complete'; $actionLabel = 'إنهاء المشوار'; }
        if ($visit->order?->status !== 'active') $action = null;
        if ($isDelivery && \App\Services\Hourly\ServiceIncident::waiting($visit->order, $visit->id)) {
            $action = null; $blocked = 'للزيارة بلاغ معتمد؛ راجع الإدارة قبل التوصيل أو التسليم وتابع العودة حسب حالة التنفيذ.';
        }
        return $task + ['action' => $action, 'actionLabel' => $actionLabel, 'blocked' => $blocked];
    });
    $isDone = fn ($task) => in_array($task['visit']->status, $closed, true)
        || ($task['role'] === 'delivery' ? in_array($task['visit']->status, $deliveredStatuses, true) : $task['visit']->status === 'completed');
    $taskCount = $tasks->count();
    $doneCount = $tasks->filter($isDone)->count();
    $nextTask = $tasks->first(fn ($task) => $task['action']) ?? $tasks->first(fn ($task) => !$isDone($task));
    $driverCash = $cashReady ? app(\App\Services\Hourly\CashCollectionService::class)->custody('driver', (int) $driver->id) : null;
    $actionIcons = ['start_delivery' => 'M5 12h14M13 6l6 6-6 6', 'confirm_delivery' => 'M5 13l4 4L19 7', 'confirm_pickup' => 'M16 11a4 4 0 1 0-8 0M4 20a8 8 0 0 1 16 0', 'complete' => 'M5 13l4 4L19 7'];
    $roleMeta = ['delivery' => ['توصيل', 'إلى العميل'], 'pickup' => ['استلام', 'العودة للإيواء']];
@endphp

{{-- الترويسة: التحية وملخص اليوم --}}
<section class="dp-hero">
    <div class="dp-hero-main">
        <span class="dp-kicker">{{ now()->translatedFormat('l، j F Y') }}</span>
        <h1>مرحبًا، {{ $driver->name }}</h1>
        <p>مهامي ومشاويري — حدّث حالة كل مشوار فور تنفيذه ليتابعك فريق التشغيل.</p>
        <div class="dp-chips">
            <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h10M4 17h7"/></svg> رقم الموظف <b dir="ltr">{{ $driver->employee_number }}</b></span>
            <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17h2l2-5h10l2 5h2M7 17h10M8 12l1.5-5h5L16 12"/></svg> سعة المركبة <b>{{ $driver->vehicle_capacity }}</b></span>
        </div>
    </div>
    <div class="dp-stats" aria-label="ملخص اليوم المحدد">
        <div class="dp-stat"><strong>{{ $taskCount }}</strong><span>مهام {{ $selectedDate->isToday() ? 'اليوم' : 'اليوم المحدد' }}</span></div>
        <div class="dp-stat is-done"><strong>{{ $doneCount }}</strong><span>منجزة</span></div>
        <div class="dp-stat is-left"><strong>{{ max(0, $taskCount - $doneCount) }}</strong><span>متبقية</span></div>
        @if($driverCash)<div class="dp-stat is-cash"><strong>{{ number_format($driverCash['outstanding'], 2) }}</strong><span>عهدة نقدية (ر.س)</span></div>@endif
    </div>
</section>

@if(session('success'))<p class="dp-flash" role="status">{{ session('success') }}</p>@endif
@if($errors->any())<p class="dp-error" role="alert">{{ $errors->first() }}</p>@endif

{{-- المهمة التالية: أهم ما يحتاجه السائق الآن --}}
@if($nextTask)
    @php $nv = $nextTask['visit']; @endphp
    <a class="dp-next is-{{ $nextTask['role'] }}" href="#task-{{ $nv->id }}-{{ $nextTask['role'] }}">
        <span class="dp-next-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
        <span class="dp-next-text">
            <small>{{ $nextTask['action'] ? 'المطلوب الآن' : 'المشوار التالي' }} · {{ $roleMeta[$nextTask['role']][0] }}</small>
            <b>{{ $nextTask['action'] ? $nextTask['actionLabel'] : ($nv->order?->user?->name ?: 'زيارة '.$nv->visit_number) }}</b>
            <span>{{ \Illuminate\Support\Str::limit($nv->address_text ?: '—', 70) }}</span>
        </span>
        <span class="dp-next-time" dir="ltr">{{ $nextTask['at']?->format('H:i') }}</span>
    </a>
@endif

<div class="dp-grid">
    {{-- المشاوير --}}
    <section class="dp-panel dp-tasks" aria-labelledby="dp-tasks-title">
        <div class="dp-panel-head">
            <div class="dp-title"><span class="dp-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17h2l2-5h10l2 5h2M5 17v3m14-3v3M8 12l1.5-5h5L16 12M7 17h10"/></svg></span>
                <h2 id="dp-tasks-title">مشاوير {{ $selectedDate->translatedFormat('l، j F') }}</h2></div>
            <span class="dp-pill">{{ $taskCount }} مهمة</span>
        </div>

        {{-- شريط الأيام: أسبوع حول اليوم المحدد مع عدد المهام --}}
        <nav class="dp-days" aria-label="التنقل بين الأيام">
            @for($offset = -3; $offset <= 3; $offset++)
                @php $d = $selectedDate->copy()->addDays($offset); $key = $d->format('Y-m-d'); $count = $d->format('Y-m') === $month->format('Y-m') ? ($monthCounts[$key] ?? 0) : null; @endphp
                <a class="{{ $offset === 0 ? 'is-selected' : '' }} {{ $d->isToday() ? 'is-today' : '' }}" href="{{ route('hourly-driver.home', ['date' => $key, 'month' => $d->format('Y-m')]) }}" @if($offset === 0) aria-current="date" @endif>
                    <small>{{ $d->translatedFormat('D') }}</small><b>{{ $d->day }}</b>
                    @if($count)<i>{{ $count }}</i>@endif
                </a>
            @endfor
            <form class="dp-date-pick" method="GET" action="{{ route('hourly-driver.home') }}">
                <input type="date" name="date" value="{{ $selectedDate->format('Y-m-d') }}" aria-label="اختيار التاريخ" onchange="this.form.submit()">
                <button class="dp-sr">عرض</button>
            </form>
        </nav>

        @if($tasks->isEmpty())
            <div class="dp-empty">
                <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg></span>
                <b>لا توجد مشاوير مسندة إليك في هذا اليوم.</b>
                <small>اختر يومًا آخر من الشريط أو التقويم.</small>
            </div>
        @else
        <ol class="dp-timeline">
        @foreach($tasks as $task)
            @php
                $visit = $task['visit']; $isDelivery = $task['role'] === 'delivery'; $action = $task['action']; $done = $isDone($task);
                $incident = \App\Services\Hourly\ServiceIncident::state($visit->order, $visit->id);
                $phone = $visit->contact_phone;
            @endphp
            <li class="dp-task is-{{ $task['role'] }} {{ $done ? 'is-done' : '' }} {{ $action ? 'is-active' : '' }}" id="task-{{ $visit->id }}-{{ $task['role'] }}">
                <div class="dp-when">
                    <b dir="ltr">{{ $task['at']?->format('H:i') ?: '—' }}</b>
                    <span class="dp-role">{{ $roleMeta[$task['role']][0] }}</span>
                    <small>{{ $roleMeta[$task['role']][1] }}</small>
                </div>
                <article class="dp-card">
                    <header class="dp-card-head">
                        <div>
                            <h3>{{ $visit->order?->user?->name ?: 'عميل' }}</h3>
                            <span class="dp-ref">طلب {{ $visit->order?->order_number ?: '#'.$visit->hourly_rental_order_id }} · زيارة {{ $visit->visit_number }} · <span dir="ltr">{{ $visit->scheduled_start_at?->format('H:i') }} — {{ $visit->scheduled_end_at?->format('H:i') }}</span></span>
                        </div>
                        <span class="dp-status {{ $done ? 'is-done' : '' }}">{{ $statusLabels[$visit->status] ?? $visit->status }}</span>
                    </header>

                    @if($visit->order?->status !== 'active')<p class="dp-alert" role="alert">الطلب غير نشط؛ تواصل مع الإدارة لمعالجة الزيارة الجارية وتسجيل العودة.</p>@endif
                    @if(($incident['status'] ?? null) === 'manual_review' && !empty($incident['return_recorded_at']))
                        <p class="dp-alert" role="alert">سُجلت عودة العاملة؛ ما زالت الزيارة في متابعة الإدارة للتسوية.</p>
                    @elseif(in_array($visit->status, \App\Services\Hourly\DriverAppService::ONGOING, true) && $visit->scheduled_end_at?->lte(now()))
                        <p class="dp-alert" role="alert">تجاوزت الزيارة موعد انتهائها ولم تعد العاملة بعد؛ تابع الاستلام والعودة وراجع الإدارة. موعد الزيارة الأصلي: {{ $visit->scheduled_start_at?->format('Y-m-d H:i') }}</p>
                    @endif
                    @if($task['blocked'])<p class="dp-alert is-soft" role="alert">{{ $task['blocked'] }}</p>@endif

                    <dl class="dp-info">
                        <div class="is-wide"><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg> العنوان</dt><dd>{{ $visit->address_text ?: '—' }}</dd></div>
                        <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v12H4zM9 7V5h6v2"/></svg> الخدمة</dt><dd>{{ $visit->order?->serviceCategory?->name ?: '—' }}</dd></div>
                        <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11a4 4 0 1 0-8 0M4 20a8 8 0 0 1 16 0"/></svg> العاملات</dt>
                            <dd>@forelse($visit->workers as $worker)<span class="dp-worker">{{ $worker->name }}</span>@empty لم يتم التعيين @endforelse</dd></div>
                    </dl>

                    <div class="dp-finance">@include('admin.hourly._visit-financial', ['order' => $visit->order, 'visit' => $visit])</div>

                    <div class="dp-quick">
                        <a class="dp-btn is-ghost" href="{{ $visit->map_url }}" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2Zm0 0v14m6-12v14"/></svg> فتح الموقع</a>
                        @if($phone)<a class="dp-btn is-ghost" href="tel:{{ $phone }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg> <span dir="ltr">{{ $phone }}</span></a>@endif
                    </div>

                    @if($action)
                        <form class="dp-action" method="POST" action="{{ route('hourly-driver.visits.action', $visit) }}" @if($action === 'start_delivery') data-delivery-start data-remaining-ms="{{ max(0, now()->diffInMilliseconds($visit->scheduled_end_at, false)) }}" @endif>
                            @csrf
                            <input type="hidden" name="action" value="{{ $action }}">
                            <input type="hidden" name="date" value="{{ $selectedDate->format('Y-m-d') }}">
                            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                            @if($action === 'confirm_delivery' && $cashReady && in_array($visit->order->payment_method, ['cash', 'cash_and_card']))
                                @php $cashPolicy = app(\App\Services\Hourly\CashCollectionService::class)->collector($visit); @endphp
                                @if($cashPolicy['type'] === 'driver' && $cashPolicy['id'] === (int) $driver->id)
                                    <label class="dp-field"><span>النقد المستلم من العميل (ر.س)</span><input type="number" name="cash_amount" min="0" step="0.01" inputmode="decimal" max="{{ app(\App\Services\Hourly\PaymentScheduleService::class)->collectible($visit->order, $visit) }}"></label>
                                    <input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
                                @endif
                            @endif
                            @if($action === 'confirm_delivery' && \App\Services\Hourly\DeliveryCode::required($visit))
                                <label class="dp-field" for="delivery-code-{{ $visit->id }}"><span>كود استلام العميل</span></label>
                                <input class="dp-code" id="delivery-code-{{ $visit->id }}" type="text" name="delivery_code" inputmode="numeric" autocomplete="one-time-code" maxlength="100" required aria-describedby="delivery-code-help-{{ $visit->id }}">
                                <small class="dp-help" id="delivery-code-help-{{ $visit->id }}">اطلب الكود من العميل عند تسليم العاملة. يظهر له في تفاصيل الزيارة.</small>
                            @endif
                            <button class="dp-btn is-primary"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $actionIcons[$action] }}"/></svg> {{ $task['actionLabel'] }}</button>
                        </form>
                    @else
                        <p class="dp-muted">{{ $done ? 'تم تنفيذ هذه المهمة.' : 'لا يوجد إجراء مطلوب الآن' }}</p>
                    @endif

                    @if($isDelivery && $cashReady && $action !== 'confirm_delivery')
                        @php $cashInfo = app(\App\Services\Hourly\CashCollectionService::class)->info($visit, 'driver', (int) $driver->id); @endphp
                        @if($cashInfo['can_collect'])
                            <form class="dp-action is-cash" method="POST" action="{{ route('hourly-driver.cash.collect', $visit->id) }}">@csrf
                                <input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
                                <label class="dp-field"><span>تحصيل المتبقي نقدًا (ر.س) — الحد {{ number_format($cashInfo['cash_due'], 2) }}</span><input type="number" name="amount" min="0.01" step="0.01" inputmode="decimal" max="{{ $cashInfo['cash_due'] }}" required></label>
                                <button class="dp-btn is-cash"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18v10H3zM12 9.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5"/></svg> تأكيد استلام النقد</button>
                            </form>
                        @endif
                    @endif
                    @include('hourly_driver._incident-report')
                </article>
            </li>
        @endforeach
        </ol>
        @endif
    </section>

    <aside class="dp-side">
        {{-- مشاركة الموقع --}}
        {{-- data-state تحدّثه hourly-driver-location.js: off / idle / waiting / active / error --}}
        @php $locationState = !$tracking['enabled'] ? 'off' : ($trackingVisit ? 'waiting' : 'idle'); @endphp
        <section class="dp-panel dp-location" id="driver-location" data-state="{{ $locationState }}">
            <div class="dp-panel-head">
                <div class="dp-title"><span class="dp-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg></span><h2>مشاركة الموقع</h2></div>
                <span class="dp-pill" id="driver-location-pill">{{ ['off' => 'معطّل', 'waiting' => 'بانتظار الإذن', 'idle' => 'غير نشط'][$locationState] }}</span>
            </div>
            <p class="dp-live"><i aria-hidden="true"></i><span id="driver-location-status" role="status">{{ !$tracking['enabled'] ? 'التتبع معطّل من الإدارة.' : ($trackingVisit ? 'جارٍ طلب إذن الموقع…' : 'تبدأ مشاركة الموقع تلقائيًا عند بدء مشوار.') }}</span></p>
            <p class="dp-muted">أبقِ هذه الصفحة مفتوحة واسمح بالوصول للموقع. قد يوقف المتصفح التحديث عند إغلاق الصفحة أو قفل الهاتف.</p>
            <button type="button" class="dp-btn is-ghost w-100" id="driver-location-retry" {{ !$tracking['enabled'] || !$trackingVisit ? 'hidden' : '' }}>تفعيل الموقع / إعادة المحاولة</button>
        </section>

        {{-- التقويم الشهري --}}
        <section class="dp-panel">
            <div class="dp-panel-head">
                <div class="dp-title"><span class="dp-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/></svg></span><h2>{{ $month->translatedFormat('F Y') }}</h2></div>
                <div class="month-nav"><a aria-label="الشهر السابق" href="{{ route('hourly-driver.home', ['month'=>$previousMonth->format('Y-m'),'date'=>$previousMonth->startOfMonth()->format('Y-m-d')]) }}">›</a><a aria-label="الشهر التالي" href="{{ route('hourly-driver.home', ['month'=>$nextMonth->format('Y-m'),'date'=>$nextMonth->startOfMonth()->format('Y-m-d')]) }}">‹</a></div>
            </div>
            <div class="week" aria-hidden="true">@foreach(['أحد','اثن','ثلا','أرب','خمي','جمع','سبت'] as $day)<span>{{ $day }}</span>@endforeach</div>
            <div class="calendar">
                @for($blank=0;$blank<$firstWeekday;$blank++)<span class="day is-empty"></span>@endfor
                @for($day=1;$day<=$daysInMonth;$day++)
                    @php $date = $month->copy()->day($day); $dateKey = $date->format('Y-m-d'); @endphp
                    <a class="day {{ $dateKey===$selectedDate->format('Y-m-d')?'is-selected':'' }} {{ $dateKey===now()->toDateString()?'is-today':'' }}" href="{{ route('hourly-driver.home',['date'=>$dateKey,'month'=>$month->format('Y-m')]) }}" aria-label="{{ $date->translatedFormat('j F') }}{{ ($monthCounts[$dateKey]??0)?'، '.($monthCounts[$dateKey]).' مهام':'' }}">{{ $day }}@if($monthCounts[$dateKey]??0)<span class="day-count">{{ $monthCounts[$dateKey] }}</span>@endif</a>
                @endfor
            </div>
            <details class="schedule"><summary>جدول العمل الأسبوعي</summary><table><tbody>
            @foreach(\App\Models\Hourly\HourlyDriver::DAYS as $day=>$label)
                @php $schedule=$driver->work_schedule[$day]??['mode'=>'off']; @endphp
                <tr><td>{{ $label }}</td><td>{{ ($schedule['mode']??'off')==='hours' ? ($schedule['start'].' – '.$schedule['end']) : (($schedule['mode']??'off')==='all_day'?'طوال اليوم':'إجازة') }}</td></tr>
            @endforeach
            </tbody></table></details>
        </section>

        {{-- العهدة النقدية --}}
        @if($driverCash)
            <section class="dp-panel dp-cash">
                <div class="dp-title"><span class="dp-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18v10H3zM12 9.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5"/></svg></span><h2>العهدة النقدية</h2></div>
                <div class="dp-cash-total {{ $driverCash['outstanding'] > 0 ? 'has-due' : '' }}"><span>المتبقي للتوريد</span><b>{{ number_format($driverCash['outstanding'], 2) }} <small>ر.س</small></b></div>
                @foreach($driverCash['collections'] as $entry)
                    <a class="dp-receipt" target="_blank" rel="noopener" href="{{ route('hourly-driver.cash.receipt', $entry['collection_id']) }}"><span>{{ $entry['receipt_number'] }}</span><b>{{ number_format($entry['custody_due'], 2) }} ر.س</b></a>
                @endforeach
                <p class="dp-muted">يسجّل الموظف المختص التوريد بعد استلامه النقد منك؛ لا تسجل دفعة جديدة على العميل.</p>
            </section>
        @endif
    </aside>
</div>
@endsection
@push('scripts')
@php($locationConfig = ['enabled'=>$tracking['enabled'],'visit'=>$trackingVisit?->id,'interval'=>$tracking['interval_seconds'],'url'=>route('hourly-driver.location'),'csrf'=>csrf_token()])
<script type="application/json" id="driver-location-config">@json($locationConfig)</script>
<script src="{{ asset('js/hourly-driver-location.js') }}?v={{ filemtime(public_path('js/hourly-driver-location.js')) }}" defer></script>
@endpush
