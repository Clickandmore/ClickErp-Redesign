@extends('admin.layout.indexs.index')
@section('page-title','الإنهاء المبكر للزيارات')
@push('css')
<style>
    /* الإنهاء المبكر: العاملة أنهت قبل اكتمال وقت الخدمة ← سؤال العميل ← قرار الإدارة اليدوي */
    .ef { --brand: #2794eb; --brand-ink: #1b6fb8; --brand-deep: #14507f; --teal: #00ac9a; --ink: #16212e; --muted: #6b7a8c; --line: #e6ecf3; --soft: #f6f9fc; direction: rtl; color: var(--ink); }
    .ef-hero { position: relative; overflow: hidden; border-radius: 18px; padding: 1.3rem 1.4rem; margin-bottom: 1.25rem; color: #fff; background: linear-gradient(105deg, var(--brand-deep) 0%, var(--brand-ink) 45%, var(--brand) 80%, var(--teal) 140%); box-shadow: 0 10px 30px rgba(20, 80, 127, .12); }
    .ef-hero::after { content: ''; position: absolute; width: 220px; height: 220px; left: -60px; top: -110px; border-radius: 50%; border: 40px solid rgba(255, 255, 255, .07); }
    .ef-hero h3 { position: relative; margin: 0; font-size: 1.3rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: .55rem; }
    .ef-hero p { position: relative; margin: .35rem 0 0; font-size: .86rem; opacity: .9; max-width: 780px; }
    .ef-flow { position: relative; display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin-top: 1rem; }
    .ef-flow span { display: inline-flex; align-items: center; gap: .4rem; background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .22); border-radius: 99px; padding: .3rem .75rem; font-size: .78rem; font-weight: 600; }
    .ef-flow span b { width: 20px; height: 20px; border-radius: 50%; display: grid; place-items: center; background: #fff; color: var(--brand-ink); font-size: .7rem; }
    .ef-flow > i { opacity: .7; font-size: .75rem; }
    .ef-tabs { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1.1rem; }
    .ef-tabs a { display: inline-flex; align-items: center; gap: .45rem; padding: .5rem .9rem; border-radius: 11px; background: #fff; border: 1px solid var(--line); color: var(--muted); font-weight: 700; font-size: .84rem; text-decoration: none; }
    .ef-tabs a b { min-width: 22px; padding: 0 .4rem; border-radius: 99px; background: var(--soft); color: var(--ink); text-align: center; font-size: .74rem; }
    .ef-tabs a.active { background: var(--brand-ink); border-color: var(--brand-ink); color: #fff; }
    .ef-tabs a.active b { background: rgba(255, 255, 255, .22); color: #fff; }
    .ef-tabs a.is-review:not(.active) { border-color: #f7cdcf; color: #b42a2e; }
    .ef-card { background: #fff; border: 1px solid var(--line); border-inline-start: 4px solid var(--tone, #9fb0c2); border-radius: 14px; margin-bottom: .9rem; box-shadow: 0 4px 14px rgba(20, 80, 127, .05); }
    .ef-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem 1rem; flex-wrap: wrap; padding: .95rem 1.15rem; }
    .ef-title { display: flex; align-items: center; gap: .75rem; min-width: 0; }
    .ef-icon { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 12px; display: grid; place-items: center; background: var(--tint, #eef3f8); color: var(--tone, #7b8a9a); }
    .ef-title b { display: block; font-size: .98rem; }
    .ef-title b a { color: var(--brand-ink); text-decoration: none; }
    .ef-meta { display: flex; flex-wrap: wrap; gap: .25rem .9rem; font-size: .78rem; color: var(--muted); }
    .ef-meta i { color: #9fb0c2; margin-inline-end: .2rem; }
    .ef-badge { display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .7rem; border-radius: 99px; background: var(--tint); color: var(--tone); font-size: .76rem; font-weight: 700; white-space: nowrap; }
    .ef-body { padding: 0 1.15rem 1rem; }
    .ef-time { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .6rem; margin-bottom: .8rem; }
    .ef-time > div { padding: .6rem .8rem; border-radius: 10px; background: var(--soft); border: 1px solid var(--line); }
    .ef-time span { display: block; font-size: .72rem; color: var(--muted); font-weight: 700; }
    .ef-time b { font-size: .95rem; font-variant-numeric: tabular-nums; }
    .ef-bar { height: 8px; border-radius: 99px; background: #fdeeee; overflow: hidden; margin: .2rem 0 .8rem; }
    .ef-bar span { display: block; height: 100%; background: linear-gradient(90deg, #e08a17, #e5484d); border-radius: inherit; }
    .ef-notes { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: .6rem; }
    .ef-note { padding: .65rem .85rem; border-radius: 10px; border: 1px dashed var(--line); font-size: .84rem; line-height: 1.8; }
    .ef-note span { display: block; font-size: .72rem; color: var(--muted); font-weight: 700; }
    .ef-form { margin-top: .9rem; padding: 1rem; border-radius: 12px; background: #fbfcfe; border: 1px solid var(--line); }
    .ef-form-title { display: flex; align-items: center; gap: .45rem; margin: 0 0 .8rem; font-size: .86rem; font-weight: 700; }
    .ef-form-title i { color: var(--brand); }
    .ef-grid { display: grid; grid-template-columns: minmax(200px, 280px) minmax(0, 1fr); gap: .8rem 1rem; }
    @media (max-width: 767px) { .ef-grid { grid-template-columns: 1fr; } }
    .ef-field { display: grid; gap: .35rem; margin: 0; }
    .ef-field > span { font-size: .8rem; font-weight: 700; }
    .ef-field .form-control, .ef-field .form-select { min-height: 42px; border-radius: 10px; border-color: #dfe7ef; font-size: .88rem; }
    .ef-actions { display: flex; justify-content: space-between; align-items: center; gap: .6rem; flex-wrap: wrap; margin-top: .9rem; padding-top: .8rem; border-top: 1px dashed var(--line); }
    .ef-actions small { color: var(--muted); font-size: .78rem; }
    .ef-actions a { font-size: .8rem; font-weight: 700; color: var(--brand-ink); text-decoration: none; }
    .ef-btn { display: inline-flex; align-items: center; gap: .4rem; height: 40px; padding: 0 1.1rem; border-radius: 10px; border: 0; background: var(--brand-ink); color: #fff; font-weight: 700; font-size: .84rem; }
    .ef-decision { margin-top: .8rem; padding: .7rem .9rem; border-radius: 10px; background: #f0faf6; border: 1px solid #bfe9d9; font-size: .84rem; }
    .ef-decision span { display: block; font-size: .72rem; color: #0f6b4f; font-weight: 700; }
    .ef-empty { text-align: center; padding: 2.4rem 1rem; color: var(--muted); background: #fff; border: 1px dashed #cfd9e3; border-radius: 14px; }
    .ef-empty i { display: block; font-size: 1.8rem; color: #9fd8c5; margin-bottom: .5rem; }
    .t-amber { --tone: #e08a17; --tint: #fef5e7; } .t-red { --tone: #e5484d; --tint: #fdeeee; } .t-green { --tone: #17996f; --tint: #e8f7f1; } .t-blue { --tone: #2794eb; --tint: #eaf5fe; } .t-grey { --tone: #7b8a9a; --tint: #eef3f8; }
</style>
@endpush
@section('content')
@php
    $service = app(\App\Services\Hourly\EarlyFinish::class);
    $statusMeta = [
        'awaiting_customer' => ['بانتظار رد العميل', 't-blue', 'fa-hourglass-half'],
        'no_response' => ['لم يرد العميل — يحتاج قرارًا', 't-amber', 'fa-clock'],
        'disputed' => ['اعترض العميل — يحتاج قرارًا', 't-red', 'fa-triangle-exclamation'],
        'confirmed' => ['أكد العميل أنه بطلبه', 't-green', 'fa-circle-check'],
        'resolved' => ['تم القرار', 't-grey', 'fa-gavel'],
    ];
    $tabs = ['review' => 'تحتاج قرارًا', 'awaiting_customer' => 'بانتظار العميل', 'confirmed' => 'أكدها العميل', 'resolved' => 'تم القرار', 'all' => 'الكل'];
    $canAct = AdminGuardHasPerm('hourly_orders_create');
@endphp
<div class="container-fluid ef">
    @include('admin.hourly.messages')
    <header class="ef-hero">
        <h3><i class="fa fa-stopwatch"></i> الإنهاء المبكر للزيارات</h3>
        <p>العاملة أعلنت «انتهيت» قبل اكتمال وقت الخدمة محسوبًا من وقت وصولها. يُسأل العميل هل كان ذلك بطلبه؛ التأكيد يغلق السجل، والاعتراض أو عدم الرد خلال {{ \App\Services\Hourly\EarlyFinish::RESPONSE_HOURS }} ساعة يحيله إليك لقرار يدوي.</p>
        <div class="ef-flow" aria-label="مراحل الإنهاء المبكر">
            <span><b>1</b> العاملة تنهي مبكرًا مع السبب</span><i class="fa fa-arrow-left"></i>
            <span><b>2</b> العميل يؤكد أو يعترض</span><i class="fa fa-arrow-left"></i>
            <span><b>3</b> قرار الإدارة اليدوي</span><i class="fa fa-arrow-left"></i>
            <span><b>4</b> التنفيذ من صفحة الطلب</span>
        </div>
    </header>

    <nav class="ef-tabs" aria-label="تصفية حسب الحالة">
        @foreach($tabs as $key => $label)
            <a class="{{ $filter === $key ? 'active' : '' }} {{ $key === 'review' ? 'is-review' : '' }}" href="{{ route('admin.hourly-early-finishes.index', ['status' => $key]) }}">{{ $label }} <b>{{ $counts[$key] }}</b></a>
        @endforeach
    </nav>

    @forelse($finishes as $finish)
        @php
            $state = $service->effectiveStatus($finish);
            [$stateLabel, $tone, $stateIcon] = $statusMeta[$state] ?? [$state, 't-grey', 'fa-circle'];
            $share = $finish->planned_minutes ? min(100, round($finish->worked_minutes / $finish->planned_minutes * 100)) : 0;
        @endphp
        <article class="ef-card {{ $tone }}">
            <div class="ef-head">
                <div class="ef-title">
                    <span class="ef-icon"><i class="fa fa-stopwatch"></i></span>
                    <div>
                        <b>{{ $finish->worker_name ?: 'عاملة #'.$finish->maid_id }} · <a href="{{ route('admin.hourly-orders.show', $finish->order_id) }}#visit-{{ $finish->visit_id }}">طلب {{ $finish->order_number }}</a> — زيارة {{ $finish->visit_number }}</b>
                        <div class="ef-meta">
                            <span><i class="fa fa-comment"></i>{{ \App\Services\Hourly\EarlyFinish::REASONS[$finish->reason] ?? 'لم تحدد العاملة سببًا' }}</span>
                            <span dir="ltr"><i class="fa fa-clock"></i>{{ \Carbon\Carbon::parse($finish->finished_at)->format('Y-m-d H:i') }}</span>
                            @if($state === 'awaiting_customer')<span><i class="fa fa-hourglass-end"></i>مهلة الرد حتى <bdi>{{ \Carbon\Carbon::parse($finish->respond_until)->format('H:i') }}</bdi></span>@endif
                        </div>
                    </div>
                </div>
                <span class="ef-badge"><i class="fa {{ $stateIcon }}"></i> {{ $stateLabel }}</span>
            </div>
            <div class="ef-body">
                <div class="ef-time">
                    <div><span>بداية الخدمة{{ $finish->started_from_schedule ? ' (موعد الزيارة)' : '' }}</span><b dir="ltr">{{ \Carbon\Carbon::parse($finish->started_at)->format('H:i') }}</b></div>
                    <div><span>إعلان الانتهاء</span><b dir="ltr">{{ \Carbon\Carbon::parse($finish->finished_at)->format('H:i') }}</b></div>
                    <div><span>المدة المنفذة</span><b>{{ $service->duration((int) $finish->worked_minutes) }}</b></div>
                    <div><span>المدة المحجوزة</span><b>{{ $service->duration((int) $finish->planned_minutes) }}</b></div>
                    <div><span>غير المنفذ</span><b style="color:#b42a2e">{{ $service->duration(max(0, (int) $finish->planned_minutes - (int) $finish->worked_minutes)) }}</b></div>
                </div>
                <div class="ef-bar" role="img" aria-label="نُفذ {{ $share }}% من المدة"><span style="width: {{ $share }}%"></span></div>
                <div class="ef-notes">
                    <div class="ef-note"><span>ملاحظة العاملة</span>{{ $finish->worker_note ?: '—' }}</div>
                    <div class="ef-note"><span>رد العميل</span>@if($finish->customer_responded_at){{ $finish->status === 'confirmed' ? 'أكد أن الإنهاء بطلبه' : 'اعترض: '.($finish->customer_note ?: 'دون ملاحظة') }} <small class="text-muted" dir="ltr">{{ \Carbon\Carbon::parse($finish->customer_responded_at)->format('Y-m-d H:i') }}</small>@else{{ $state === 'awaiting_customer' ? 'لم يرد بعد' : 'لم يرد خلال المهلة' }}@endif</div>
                </div>

                @if($finish->status === 'resolved')
                    <div class="ef-decision"><span>قرار الإدارة · {{ $admins[$finish->reviewed_by] ?? '' }} · <bdi>{{ $finish->reviewed_at ? \Carbon\Carbon::parse($finish->reviewed_at)->format('Y-m-d H:i') : '' }}</bdi></span>
                        <b>{{ \App\Services\Hourly\EarlyFinish::DECISIONS[$finish->decision] ?? $finish->decision }}</b> — {{ $finish->decision_note }}</div>
                @elseif($canAct && $service->needsReview($finish))
                    <form class="ef-form" method="post" action="{{ route('admin.hourly-early-finishes.review', $finish->id) }}">
                        @csrf
                        <p class="ef-form-title"><i class="fa fa-gavel"></i> قرار الإدارة</p>
                        <div class="ef-grid">
                            <label class="ef-field"><span>القرار</span>
                                <select class="form-select" name="decision" required>
                                    <option value="">اختر القرار</option>
                                    @foreach(\App\Services\Hourly\EarlyFinish::DECISIONS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                                </select>
                            </label>
                            <label class="ef-field"><span>تفاصيل القرار وسببه</span>
                                <textarea class="form-control" name="decision_note" required maxlength="2000" rows="2" placeholder="مثال: تعويض العميل بـ 75% من قيمة الزيارة لأن الإنهاء لم يكن بطلبه"></textarea>
                            </label>
                        </div>
                        <div class="ef-actions">
                            <small><i class="fa fa-circle-info"></i> القرار يُسجَّل فقط؛ نفّذ التعويض أو الزيارة المجانية من صفحة الطلب.</small>
                            <div class="d-flex gap-3 align-items-center">
                                <a href="{{ route('admin.hourly-orders.show', $finish->order_id) }}#visit-{{ $finish->visit_id }}" target="_blank"><i class="fa fa-arrow-up-right-from-square"></i> فتح الطلب</a>
                                <button class="ef-btn"><i class="fa fa-check"></i> تسجيل القرار</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </article>
    @empty
        <div class="ef-empty"><i class="fa fa-circle-check"></i> لا توجد سجلات إنهاء مبكر في هذا التبويب.</div>
    @endforelse
    <div>{{ $finishes->links() }}</div>
</div>
@endsection
