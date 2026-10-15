@extends('hourly_driver.layout')
@section('title', 'مهامي ومشاويري')
@section('portal', true)
@section('content')
@php
    $statusLabels = __('hourly_routing.visit_statuses');
    $firstWeekday = $month->copy()->startOfMonth()->dayOfWeek;
    $daysInMonth = $month->daysInMonth;
    $previousMonth = $month->copy()->subMonth();
    $nextMonth = $month->copy()->addMonth();
    $taskCount = $visits->sum(fn($visit) => ((int)$visit->delivery_driver_id === (int)$driver->id ? 1 : 0) + ((int)$visit->pickup_driver_id === (int)$driver->id ? 1 : 0));
@endphp
<section class="hero">
    <div class="hero-copy">
        <span class="hero-kicker">{{ now()->translatedFormat('l، j F Y') }}</span>
        <h1>مرحبًا، {{ $driver->name }}</h1>
        <p>تابع مهام التوصيل والاستلام وحدّث حالة كل مشوار من هنا.</p>
    </div>
    <div class="driver-meta">
        <div class="stat-pill"><span>مهام اليوم المحدد</span><strong>{{ $taskCount }} مهمة</strong></div>
        <div class="stat-pill"><span>رقم الموظف</span><strong>{{ $driver->employee_number }}</strong></div>
        <div class="stat-pill"><span>سعة المركبة</span><strong>{{ $driver->vehicle_capacity }} راكبًا</strong></div>
    </div>
</section>
<div class="grid">
<aside class="panel">
    <div class="panel-head">
        <div class="panel-title"><span class="panel-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/></svg></span><h2>{{ $month->translatedFormat('F Y') }}</h2></div>
        <div class="month-nav"><a aria-label="الشهر السابق" href="{{ route('hourly-driver.home', ['month'=>$previousMonth->format('Y-m'),'date'=>$previousMonth->startOfMonth()->format('Y-m-d')]) }}">›</a><a aria-label="الشهر التالي" href="{{ route('hourly-driver.home', ['month'=>$nextMonth->format('Y-m'),'date'=>$nextMonth->startOfMonth()->format('Y-m-d')]) }}">‹</a></div>
    </div>
    <div class="week" aria-hidden="true">@foreach(['أحد','اثن','ثلا','أرب','خمي','جمع','سبت'] as $day)<span>{{ $day }}</span>@endforeach</div>
    <div class="calendar">
        @for($blank=0;$blank<$firstWeekday;$blank++)<span class="day is-empty"></span>@endfor
        @for($day=1;$day<=$daysInMonth;$day++)
            @php
                $date = $month->copy()->day($day);
                $dateKey = $date->format('Y-m-d');
            @endphp
            <a class="day {{ $dateKey===$selectedDate->format('Y-m-d')?'is-selected':'' }} {{ $dateKey===now()->toDateString()?'is-today':'' }}" href="{{ route('hourly-driver.home',['date'=>$dateKey,'month'=>$month->format('Y-m')]) }}" aria-label="{{ $date->translatedFormat('j F') }}{{ ($monthCounts[$dateKey]??0)?'، '.($monthCounts[$dateKey]).' مهام':'' }}">{{ $day }}@if($monthCounts[$dateKey]??0)<span class="day-count">{{ $monthCounts[$dateKey] }}</span>@endif</a>
        @endfor
    </div>
    <details class="schedule"><summary>جدول العمل الأسبوعي</summary><table><tbody>
    @foreach(\App\Models\Hourly\HourlyDriver::DAYS as $day=>$label)
    @php $schedule=$driver->work_schedule[$day]??['mode'=>'off']; @endphp
    <tr><td>{{ $label }}</td><td>{{ ($schedule['mode']??'off')==='hours' ? ($schedule['start'].' – '.$schedule['end']) : (($schedule['mode']??'off')==='all_day'?'طوال اليوم':'إجازة') }}</td></tr>@endforeach
    </tbody></table></details>
</aside>
<section class="panel">
    <div class="panel-head"><div class="panel-title"><span class="panel-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17h2l2-5h10l2 5h2M5 17v3m14-3v3M8 12l1.5-5h5L16 12M7 17h10"/></svg></span><h2>مشاوير {{ $selectedDate->translatedFormat('l، j F Y') }}</h2></div><span class="pill">{{ $taskCount }} مهمة</span></div>
    <form class="date-picker" method="GET" action="{{ route('hourly-driver.home') }}"><input type="date" name="date" value="{{ $selectedDate->format('Y-m-d') }}" aria-label="اختيار التاريخ"><button>عرض</button></form>
    @if(session('success'))<p class="flash" role="status">{{ session('success') }}</p>@endif @if($errors->any())<p class="error" role="alert">{{ $errors->first() }}</p>@endif
    @if($visits->isEmpty())<div class="empty">لا توجد مشاوير مسندة إليك في هذا اليوم.</div>@else
    <div class="task-list">@foreach($visits as $visit)
        @php
            $isDelivery=(int)$visit->delivery_driver_id===(int)$driver->id;$isPickup=(int)$visit->pickup_driver_id===(int)$driver->id;$action=null;$actionLabel=null;
            if($isDelivery&&$visit->status==='scheduled'&&\App\Services\Hourly\DeliveryStartWindow::allows($visit)){$action='start_delivery';$actionLabel='بدء مشوار التوصيل';}
            elseif($isDelivery&&$visit->status==='en_route'){$action='confirm_delivery';$actionLabel='تأكيد التوصيل';}
            elseif($isPickup&&$visit->status==='worker_finished'){$action='confirm_pickup';$actionLabel='تأكيد استلام العاملات';}
            elseif($isPickup&&$visit->status==='picked_up'){$action='complete';$actionLabel='إنهاء المشوار';}
        @endphp
        <article class="task">
            <div class="task-top"><div><div class="task-title">طلب {{ $visit->order?->order_number?:'#'.$visit->hourly_rental_order_id }} · زيارة {{ $visit->visit_number }}</div><div class="task-time">{{ $visit->scheduled_start_at?->format('H:i') }} — {{ $visit->scheduled_end_at?->format('H:i') }}</div></div><span class="status">{{ $statusLabels[$visit->status]??$visit->status }}</span></div>
            <div class="roles">@if($isDelivery)<span class="role">توصيل {{ $visit->delivery_at?->format('H:i')?:$visit->scheduled_start_at?->format('H:i') }}</span>@endif @if($isPickup)<span class="role pickup">استلام {{ $visit->pickup_at?->format('H:i')?:$visit->scheduled_end_at?->format('H:i') }}</span>@endif</div>
            <div class="task-info"><span><strong>العميل:</strong> {{ $visit->order?->user?->name?:'—' }}</span><span><strong>الخدمة:</strong> {{ $visit->order?->serviceCategory?->name?:'—' }}</span><span><strong>العاملات:</strong> {{ $visit->workers->pluck('name')->join('، ')?:'لم يتم التعيين' }}</span><span><strong>العنوان:</strong> {{ $visit->address_text?:'—' }}</span></div>
            @include('admin.hourly._visit-financial',['order'=>$visit->order,'visit'=>$visit])
            <div class="task-actions"><a class="map-link" href="{{ $visit->map_url }}" target="_blank" rel="noopener">فتح الموقع</a>@if($visit->contact_phone)<a class="phone-link" dir="ltr" href="tel:{{ $visit->contact_phone }}">{{ $visit->contact_phone }}</a>@endif
            @if($action)<form method="POST" action="{{ route('hourly-driver.visits.action',$visit) }}">@csrf<input type="hidden" name="action" value="{{ $action }}"><input type="hidden" name="date" value="{{ $selectedDate->format('Y-m-d') }}"><input type="hidden" name="month" value="{{ $month->format('Y-m') }}">@if($action==='confirm_delivery' && \App\Services\Hourly\CashCollectionService::ready() && in_array($visit->order->payment_method,['cash','cash_and_card']))
    @php $cashPolicy=app(\App\Services\Hourly\CashCollectionService::class)->collector($visit); @endphp
    @if($cashPolicy['type']==='driver' && $cashPolicy['id']===(int)$driver->id)
        <label>النقد المستلم من العميل (ر.س)</label><input type="number" name="cash_amount" min="0" step="0.01" max="{{ app(\App\Services\Hourly\PaymentScheduleService::class)->collectible($visit->order,$visit) }}">
        <input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
    @endif
@endif
<button class="action-btn">{{ $actionLabel }}</button></form>@else<span class="muted">لا يوجد إجراء مطلوب الآن</span>@endif</div>
            @if($isDelivery && \App\Services\Hourly\CashCollectionService::ready() && $action!=='confirm_delivery')
            @php $cashInfo=app(\App\Services\Hourly\CashCollectionService::class)->info($visit,'driver',(int)$driver->id); @endphp
            @if($cashInfo['can_collect'])
            <form method="POST" action="{{ route('hourly-driver.cash.collect',$visit->id) }}">@csrf
            <input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
            <label>تحصيل المتبقي نقدًا (ر.س)</label><input type="number" name="amount" min="0.01" step="0.01" max="{{ $cashInfo['cash_due'] }}" required>
            <button class="action-btn">تأكيد استلام النقد</button></form>
            @endif
            @endif
        </article>
    @endforeach</div>@endif
</section></div>
@php $locationState = !$tracking['enabled'] ? 'off' : ($trackingVisit ? 'waiting' : 'idle'); @endphp
<section class="panel location-panel" id="driver-location" data-state="{{ $locationState }}">
    <div class="panel-head">
        <div class="panel-title"><span class="panel-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg></span><h2>مشاركة الموقع أثناء المشوار</h2></div>
        <span class="pill location-pill" id="driver-location-pill">{{ ['off' => 'معطّل', 'waiting' => 'بانتظار الإذن', 'idle' => 'غير نشط'][$locationState] }}</span>
    </div>
    <div class="location-body">
        <div class="location-status"><span class="location-dot" aria-hidden="true"></span><p id="driver-location-status" role="status">{{ !$tracking['enabled'] ? 'التتبع معطّل من الإدارة.' : ($trackingVisit ? 'جارٍ طلب إذن الموقع…' : 'تبدأ مشاركة الموقع تلقائيًا عند بدء مشوار.') }}</p></div>
        <button type="button" class="location-btn" id="driver-location-retry" {{ !$tracking['enabled'] || !$trackingVisit ? 'hidden' : '' }}>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg> تفعيل الموقع / إعادة المحاولة
        </button>
    </div>
    <p class="location-hint"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/></svg> أبقِ هذه الصفحة مفتوحة واسمح بالوصول للموقع. قد يوقف المتصفح التحديث عند إغلاق الصفحة أو قفل الهاتف.</p>
</section>
@if(\App\Services\Hourly\CashCollectionService::ready())
@php $driverCash=app(\App\Services\Hourly\CashCollectionService::class)->custody('driver',(int)$driver->id); @endphp
<section class="panel" style="padding:20px;margin-top:20px"><h2>العهدة النقدية</h2><p>المتبقي للتوريد: {{ number_format($driverCash['outstanding'],2) }} ر.س</p>
@foreach($driverCash['collections'] as $entry)<p><a target="_blank" rel="noopener" href="{{ route('hourly-driver.cash.receipt',$entry['collection_id']) }}">{{ $entry['receipt_number'] }}</a> · متبقي للتوريد {{ number_format($entry['custody_due'],2) }} ر.س</p>@endforeach
<p>سجّل الموظف المختص التوريد بعد استلامه النقد منك؛ لا تسجل دفعة جديدة على العميل.</p></section>
@endif
@endsection
@push('scripts')
@php $locationConfig = ['enabled'=>$tracking['enabled'],'visit'=>$trackingVisit?->id,'interval'=>$tracking['interval_seconds'],'url'=>route('hourly-driver.location'),'csrf'=>csrf_token()]; @endphp
<script type="application/json" id="driver-location-config">@json($locationConfig)</script>
<script src="{{ asset('js/hourly-driver-location.js') }}?v={{ filemtime(public_path('js/hourly-driver-location.js')) }}" defer></script>
@endpush
