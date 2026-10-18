@extends('hourly_driver.layout')
@section('title', 'بوابة عاملات الساعات')
@section('portal', true)
@push('styles')
<link rel="stylesheet" href="{{ asset('css/hourly-driver-dashboard.css') }}?v={{ filemtime(public_path('css/hourly-driver-dashboard.css')) }}">
<link rel="stylesheet" href="{{ asset('css/hourly-maid-dashboard.css') }}?v={{ filemtime(public_path('css/hourly-maid-dashboard.css')) }}">
@endpush
@section('content')
@php
    $workSchedule = $maid->hourly_settings['work_schedule'] ?? [];
    $workingDays = collect($workSchedule)->filter(fn ($day) => ($day['mode'] ?? 'off') !== 'off')->count();
    $scheduleLabel = fn ($day) => ($day['mode'] ?? 'off') === 'hours' ? (($day['start'] ?? '—').' – '.($day['end'] ?? '—')) : (($day['mode'] ?? 'off') === 'all_day' ? 'طوال اليوم' : 'إجازة');
    $todayKey = strtolower(now()->englishDayOfWeek);
    $selectedDate = \Carbon\Carbon::parse($cashDate ?? now()->toDateString());

    // زيارات اليوم المحدد بنفس بيانات تطبيق العاملة، فتتطابق الأزرار مع ما يسمح به الخادم.
    $ingazMaid = \App\Models\ingaz\Maid::findOrFail($maid->id);
    $hasVisits = \Illuminate\Support\Facades\Schema::hasTable('hourly_visit_workers');
    $tasks = collect($hasVisits ? app(\App\Services\Hourly\WorkerAppService::class)->today($ingazMaid, $selectedDate->toDateString()) : []);
    $cashReady = \App\Services\Hourly\CashCollectionService::ready();
    $custody = $cashReady ? app(\App\Services\Hourly\CashCollectionService::class)->custody('worker', (int) $maid->id) : null;
    $doneStatuses = ['finished', 'picked_up', 'returned', 'completed'];
    $closedStatuses = ['cancelled', 'no_show'];
    $isDone = fn ($task) => in_array($task['status'], array_merge($doneStatuses, $closedStatuses), true);
    $needsAction = fn ($task) => $task['can_arrive'] || $task['can_finish'] || ($task['cash']['can_collect'] ?? false);
    $doneCount = $tasks->filter($isDone)->count();
    $nextTask = $tasks->first($needsAction) ?? $tasks->first(fn ($task) => !$isDone($task));

    // عدد الزيارات لكل يوم في شريط الأيام (أسبوع حول اليوم المحدد).
    $stripFrom = $selectedDate->copy()->subDays(3)->startOfDay();
    $dayCounts = !$hasVisits ? collect() : \App\Models\Hourly\HourlyVisit::whereHas('workers', fn ($q) => $q->where('maids.id', $maid->id))
        ->whereBetween('scheduled_start_at', [$stripFrom, $selectedDate->copy()->addDays(3)->endOfDay()])
        ->whereNotIn('status', ['cancelled', 'cancelled_by_client', 'expired'])
        ->pluck('scheduled_start_at')->countBy(fn ($at) => \Carbon\Carbon::parse($at)->toDateString());
@endphp

{{-- الترويسة: التحية وملخص اليوم --}}
<section class="dp-hero wp-hero">
    <div class="dp-hero-main">
        <span class="dp-kicker">{{ now()->translatedFormat('l، j F Y') }}</span>
        <h1>مرحبًا، {{ $maid->name }}</h1>
        <p>زياراتي اليوم — سجّلي وصولك وانتهاءك من الخدمة في وقتها ليتابعك فريق التشغيل والعميل.</p>
        <div class="dp-chips">
            <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg> دوام اليوم <b><bdi dir="{{ ($workSchedule[$todayKey]['mode'] ?? 'off') === 'hours' ? 'ltr' : 'rtl' }}">{{ $scheduleLabel($workSchedule[$todayKey] ?? []) }}</bdi></b></span>
            <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11a4 4 0 1 0-8 0M4 20a8 8 0 0 1 16 0"/></svg> اسم المستخدم <b dir="ltr">{{ $maid->hourly_username }}</b></span>
        </div>
    </div>
    <div class="dp-stats" aria-label="ملخص اليوم المحدد">
        <div class="dp-stat"><strong>{{ $tasks->count() }}</strong><span>زيارات {{ $selectedDate->isToday() ? 'اليوم' : 'اليوم المحدد' }}</span></div>
        <div class="dp-stat is-done"><strong>{{ $doneCount }}</strong><span>منجزة</span></div>
        <div class="dp-stat is-left"><strong>{{ max(0, $tasks->count() - $doneCount) }}</strong><span>متبقية</span></div>
        @if($custody)<div class="dp-stat is-cash"><strong>{{ number_format($custody['outstanding'], 2) }}</strong><span>عهدة نقدية (ر.س)</span></div>@endif
    </div>
</section>

@if(session('success'))<p class="dp-flash" role="status">{{ session('success') }}</p>@endif
@if($errors->any())<p class="dp-error" role="alert">{{ $errors->first() }}</p>@endif

{{-- الزيارة التالية: أهم ما تحتاجه العاملة الآن --}}
@if($nextTask)
    @php
        $nextLabel = $nextTask['can_finish'] ? 'تسجيل الانتهاء من الخدمة' : ($nextTask['can_arrive'] ? 'تسجيل الوصول' : (($nextTask['cash']['can_collect'] ?? false) ? 'تحصيل المبلغ النقدي' : null));
    @endphp
    <a class="dp-next wp-next" href="#visit-{{ $nextTask['id'] }}">
        <span class="dp-next-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
        <span class="dp-next-text">
            <small>{{ $nextLabel ? 'المطلوب الآن' : 'الزيارة التالية' }} · زيارة {{ $nextTask['visit_number'] }}</small>
            <b>{{ $nextLabel ?: $nextTask['client']['name'] }}</b>
            <span>{{ \Illuminate\Support\Str::limit($nextTask['address'] ?: '—', 70) }}</span>
        </span>
        <span class="dp-next-time" dir="ltr">{{ $nextTask['start_time'] }}</span>
    </a>
@endif

<div class="dp-grid">
    @include('hourly_maid._cash-visits')

    <aside class="dp-side">
        {{-- العهدة النقدية --}}
        @if($custody)
            <section class="dp-panel dp-cash">
                <div class="dp-title"><span class="dp-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18v10H3zM12 9.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5"/></svg></span><h2>سندات التحصيل والعهدة</h2></div>
                <div class="dp-cash-total {{ $custody['outstanding'] > 0 ? 'has-due' : '' }}"><span>المتبقي معك للتوريد</span><b>{{ number_format($custody['outstanding'], 2) }} <small>ر.س</small></b></div>
                @foreach($custody['collections'] as $entry)
                    <a class="dp-receipt" target="_blank" rel="noopener" href="{{ route('hourly-maid.cash.receipt', $entry['collection_id']) }}"><span>{{ $entry['receipt_number'] }}</span><b>{{ number_format($entry['custody_due'], 2) }} ر.س</b></a>
                @endforeach
                <p class="dp-muted">سلّمي النقد للموظف المخوّل ليؤكد التوريد. إكمال الخدمة لا يعني تحصيل المبلغ؛ سجّلي فقط النقد الذي استلمتِه فعلًا.</p>
            </section>
        @endif

        {{-- الزيارات المتاحة للاختيار --}}
        <a class="dp-panel wp-offers" href="{{ route('hourly-maid.offers') }}">
            <span class="dp-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm3 10 2 2 4-5"/></svg></span>
            <span><b>الزيارات المتاحة</b><small>اختاري زيارات تناسب جدولك واحجزيها مباشرة.</small></span>
            <i aria-hidden="true">←</i>
        </a>

        @include('hourly_maid._incident-reports')

        {{-- جدول العمل الأسبوعي --}}
        <section class="dp-panel">
            <div class="dp-panel-head">
                <div class="dp-title"><span class="dp-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><h2>جدول العمل الأسبوعي</h2></div>
                <span class="dp-pill">{{ $workingDays }} أيام عمل</span>
            </div>
            <ul class="wp-week">
                @foreach(\App\Models\Hourly\HourlyDriver::DAYS as $day => $label)
                    @php $mode = $workSchedule[$day]['mode'] ?? 'off'; @endphp
                    <li class="{{ $mode === 'off' ? 'is-off' : '' }} {{ $day === $todayKey ? 'is-today' : '' }}"><span>{{ $label }}</span><b dir="{{ $mode === 'hours' ? 'ltr' : 'rtl' }}">{{ $scheduleLabel($workSchedule[$day] ?? []) }}</b></li>
                @endforeach
            </ul>
        </section>
    </aside>
</div>
@endsection
